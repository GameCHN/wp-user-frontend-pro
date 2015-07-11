<?php
/*
Plugin Name: WP User Frontend Pro
Plugin URI: http://wedevs.com/plugin/wp-user-frontend-pro/
Description: Create, edit, delete, manages your post, pages or custom post types from frontend. Create registration forms, frontend profile and more...
Author: Tareq Hasan
Version: 2.2.7
Author URI: http://tareq.weDevs.com
License: GPL2
TextDomain: wpuf
*/

define( 'WPUF_ROOT', dirname( __FILE__ ) );
define( 'WPUF_ROOT_URI', plugins_url( '', __FILE__ ) );
define( 'WPUF_ASSET_URI', WPUF_ROOT_URI . '/assets' );

require_once dirname( __FILE__ ) . '/wpuf-functions.php';
require_once dirname( __FILE__ ) . '/lib/gateway/paypal.php';
require_once dirname( __FILE__ ) . '/lib/gateway/bank.php';

if ( is_admin() ) {
    require_once dirname( __FILE__ ) . '/admin/settings-options.php';
    require_once dirname( __FILE__ ) . '/admin/coupon.php';
}

// add reCaptcha library if not found
if ( !function_exists( 'recaptcha_get_html' ) ) {
    require_once dirname( __FILE__ ) . '/lib/recaptchalib.php';
}

/**
 * Autoload class files on demand
 *
 * `WPUF_Form_Posting` becomes => form-posting.php
 * `WPUF_Dashboard` becomes => dashboard.php
 *
 * @param string $class requested class name
 */
function wpuf_autoload( $class ) {

    if ( stripos( $class, 'WPUF_' ) !== false ) {

        $admin = ( stripos( $class, '_Admin_' ) !== false ) ? true : false;

        if ( $admin ) {
            $class_name = str_replace( array('WPUF_Admin_', '_'), array('', '-'), $class );
            $filename = dirname( __FILE__ ) . '/admin/' . strtolower( $class_name ) . '.php';
        } else {
            $class_name = str_replace( array('WPUF_', '_'), array('', '-'), $class );
            $filename = dirname( __FILE__ ) . '/class/' . strtolower( $class_name ) . '.php';
        }


        if ( file_exists( $filename ) ) {
            require_once $filename;
        }
    }
}

spl_autoload_register( 'wpuf_autoload' );

/**
 * Main bootstrap class for WP User Frontend
 *
 * @package WP User Frontend
 */
class WP_User_Frontend {

    private static $_instance;
    private $version = '2.2';

    function __construct() {

        $this->instantiate();

        register_activation_hook( __FILE__, array($this, 'install') );
        register_deactivation_hook( __FILE__, array($this, 'uninstall') );

        add_action( 'admin_init', array($this, 'block_admin_access') );
        add_action( 'show_admin_bar', array($this, 'show_admin_bar') );

        add_action( 'init', array($this, 'load_textdomain') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );

        // do plugin upgrades
        add_action( 'plugins_loaded', array($this, 'plugin_upgrades') );
    }

    public static function init() {

        if ( !self::$_instance ) {
            self::$_instance = new WP_User_Frontend();
        }

        return self::$_instance;
    }

    /**
     * Instantiate the classes
     *
     * @return void
     */
    function instantiate() {

        // var_dump( get_option( 'wpuf_payment' ) );

        new WPUF_Upload();
        new WPUF_Frontend_Form_Profile();
        new WPUF_Payment();

        WPUF_Frontend_Form_Post::init(); // requires for form preview
        WPUF_Login::init();
        WPUF_Subscription::init();
        WPUF_Coupons::init();

        if ( is_admin() ) {
            WPUF_Admin_Settings::init();
            new WPUF_Admin_Form();
            new WPUF_Admin_Posting();
            new WPUF_Admin_Posting_Profile();
            new WPUF_Admin_Coupon();
            new WPUF_Updates();
            new WPUF_Admin_Subscription();
            new WPUF_Admin_Installer();
        } else {
            new WPUF_Frontend_Dashboard();
        }
    }

    /**
     * Create tables on plugin activation
     *
     * @global object $wpdb
     */
    function install() {
        global $wpdb;

        flush_rewrite_rules( false );

        $sql_transaction = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpuf_transaction (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) DEFAULT NULL,
            `status` varchar(255) NOT NULL DEFAULT 'pending_payment',
            `cost` varchar(255) DEFAULT '',
            `post_id` varchar(20) DEFAULT NULL,
            `pack_id` bigint(20) DEFAULT NULL,
            `payer_first_name` longtext,
            `payer_last_name` longtext,
            `payer_email` longtext,
            `payment_type` longtext,
            `payer_address` longtext,
            `transaction_id` longtext,
            `created` datetime NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $sql_transaction );

        update_option( 'wpuf_version', $this->version );
    }

    /**
     * Do plugin upgrades
     *
     * @since 2.2
     * @return void
     */
    function plugin_upgrades() {

        if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {
            return;
        }

        new WPUF_Upgrades( $this->version );
    }

    /**
     * Manage task on plugin deactivation
     *
     * @return void
     */
    function uninstall() {

    }

    /**
     * Enqueues Styles and Scripts when the shortcodes are used only
     *
     * @uses has_shortcode()
     * @since 0.2
     */
    function enqueue_scripts() {

        $scheme = is_ssl() ? 'https' : 'http';

        //wp_enqueue_script( 'google-maps', $scheme . '://maps.google.com/maps/api/js?sensor=true' );
        wp_enqueue_script( 'wpuf-form', WPUF_ASSET_URI . '/js/frontend-form.js', array('jquery') );
        wp_enqueue_script( 'wpuf-conditional-logic', WPUF_ASSET_URI . '/js/conditional-logic.js', array('jquery'), false, true );
        wp_enqueue_script( 'wpuf-subscriptions', WPUF_ASSET_URI . '/js/subscriptions.js', array('jquery'), false, true );

        wp_enqueue_style( 'wpuf-css', WPUF_ASSET_URI . '/css/frontend-forms.css' );

        if ( wpuf_get_option( 'load_script', 'wpuf_general', 'on') == 'on') {
            $this->plugin_scripts();
        } else if ( wpuf_has_shortcode( 'wpuf_form' ) || wpuf_has_shortcode( 'wpuf_edit' ) || wpuf_has_shortcode( 'wpuf_profile' ) || wpuf_has_shortcode( 'wpuf_dashboard' ) ) {
            $this->plugin_scripts();
        }
    }

    function plugin_scripts() {

        wp_enqueue_style( 'jquery-ui', WPUF_ASSET_URI . '/css/jquery-ui-1.9.1.custom.css' );

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_script( 'suggest' );
        wp_enqueue_script( 'jquery-ui-slider' );
        wp_enqueue_script( 'plupload-handlers' );
        wp_enqueue_script( 'jquery-ui-timepicker', WPUF_ASSET_URI . '/js/jquery-ui-timepicker-addon.js', array('jquery-ui-datepicker') );
        wp_enqueue_script( 'wpuf-upload', WPUF_ASSET_URI . '/js/upload.js', array('jquery', 'plupload-handlers') );

        wp_localize_script( 'wpuf-form', 'wpuf_frontend', array(
            'ajaxurl'       => admin_url( 'admin-ajax.php' ),
            'error_message' => __( 'Please fix the errors to proceed', 'wpuf' ),
            'nonce'         => wp_create_nonce( 'wpuf_nonce' )
        ) );

        wp_localize_script( 'wpuf-upload', 'wpuf_frontend_upload', array(
            'confirmMsg' => __( 'Are you sure?', 'wpuf' ),
            'nonce'      => wp_create_nonce( 'wpuf_nonce' ),
            'ajaxurl'    => admin_url( 'admin-ajax.php' ),
            'plupload'   => array(
                'url'              => admin_url( 'admin-ajax.php' ) . '?nonce=' . wp_create_nonce( 'wpuf_featured_img' ),
                'flash_swf_url'    => includes_url( 'js/plupload/plupload.flash.swf' ),
                'filters'          => array(array('title' => __( 'Allowed Files' ), 'extensions' => '*')),
                'multipart'        => true,
                'urlstream_upload' => true,
            )
        ));
    }

    /**
     * Block user access to admin panel for specific roles
     *
     * @global string $pagenow
     */
    function block_admin_access() {
        global $pagenow;

        // bail out if we are from WP Cli
        if ( defined( 'WP_CLI' ) ) {
            return;
        }

        $access_level = wpuf_get_option( 'admin_access', 'wpuf_general', 'read' );
        $valid_pages  = array('admin-ajax.php', 'admin-post.php', 'async-upload.php', 'media-upload.php');

        if ( ! current_user_can( $access_level ) && !in_array( $pagenow, $valid_pages ) ) {
            // wp_die( __( 'Access Denied. Your site administrator has blocked your access to the WordPress back-office.', 'wpuf' ) );
            wp_redirect( home_url() );
            exit;
        }
    }

    /**
     * Show/hide admin bar to the permitted user level
     *
     * @since 2.2.3
     * @return void
     */
    function show_admin_bar() {
        $access_level = wpuf_get_option( 'admin_access', 'wpuf_general', 'read' );

        return current_user_can( $access_level );
    }

    /**
     * Load the translation file for current language.
     *
     * @since version 0.7
     * @author Tareq Hasan
     */
    function load_textdomain() {
        load_plugin_textdomain( 'wpuf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * The main logging function
     *
     * @uses error_log
     * @param string $type type of the error. e.g: debug, error, info
     * @param string $msg
     */
    public static function log( $type = '', $msg = '' ) {
        if ( WP_DEBUG == true ) {
            $msg = sprintf( "[%s][%s] %s\n", date( 'd.m.Y h:i:s' ), $type, $msg );
            error_log( $msg, 3, dirname( __FILE__ ) . '/log.txt' );
        }
    }

}

WP_User_Frontend::init();
