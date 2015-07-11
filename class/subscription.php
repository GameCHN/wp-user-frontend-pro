<?php

/**
 * WPUF subscription manager
 *
 * @since 0.2
 * @author Tareq Hasan
 * @package WP User Frontend
 */
class WPUF_Subscription {

    private static $_instance;

    function __construct() {

        add_action( 'init', array($this, 'register_post_type') );
        add_filter( 'wpuf_add_post_args', array($this, 'set_pending'), 10, 1 );
        add_filter( 'wpuf_add_post_redirect', array($this, 'post_redirect'), 10, 4 );

        add_filter( 'wpuf_addpost_notice', array($this, 'force_pack_notice'), 20, 3 );
        add_filter( 'wpuf_can_post', array($this, 'force_pack_permission'), 20, 3 );
        add_action( 'wpuf_add_post_form_top', array($this, 'add_post_info'), 10, 2 );

        add_action( 'wpuf_add_post_after_insert', array($this, 'monitor_new_post'), 10, 3 );
        add_action( 'wpuf_payment_received', array($this, 'payment_received'), 10, 2 );

        add_shortcode( 'wpuf_sub_info', array($this, 'subscription_info') );
        add_shortcode( 'wpuf_sub_pack', array($this, 'subscription_packs') );

        add_action( 'add_meta_boxes_wpuf_subscription', array($this, 'add_meta_box_subscription_post') );

        add_action( 'save_post', array( $this, 'save_form_meta' ), 1, 3 );
        add_filter( 'enter_title_here', array( $this, 'change_default_title' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'subscription_script' ) );

        add_action( 'user_register', array( $this,'after_registration' ), 10, 1 );

        add_action( 'register_form',array( $this, 'register_form') );
        add_action( 'wpuf_add_post_form_top',array( $this, 'register_form') );
        add_filter( 'wpuf_user_register_redirect', array( $this, 'subs_redirect_pram' ), 10, 5 );

    }

    public static function init() {
        if ( !self::$_instance ) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Redirect a user to subscription page after signup
     *
     * @since 2.2
     *
     * @param  array $response
     * @param  int $user_id
     * @param  array $userdata
     * @param  int $form_id
     * @param  array $form_settings
     * @return array
     */
    function subs_redirect_pram( $response, $user_id, $userdata, $form_id, $form_settings ) {
        if ( ! isset( $_POST['wpuf_sub'] ) || $_POST['wpuf_sub'] != 'yes' ) {
            return $response;
        }

        if ( ! isset( $_POST['pack_id'] ) || empty( $_POST['pack_id'] ) ) {
            return $response;
        }

        $pack           = $this->get_subscription( $_POST['pack_id'] );
        $billing_amount = ( $pack->meta_value['billing_amount'] >= 0 && !empty( $pack->meta_value['billing_amount'] ) ) ? $pack->meta_value['billing_amount'] : false;

        if ( $billing_amount !== false ) {
            $pay_page = intval( wpuf_get_option( 'payment_page', 'wpuf_payment' ) );
            $redirect =  add_query_arg( array('action' => 'wpuf_pay', 'user_id' => $user_id, 'type' => 'pack', 'pack_id' => (int) $_POST['pack_id'] ), get_permalink( $pay_page ) );

            $response['redirect_to'] = $redirect;
            $response['show_message'] = false;
        }

        return $response;
    }

    /**
     * Insert hidden field on the register form based on selected package
     *
     * @since 2.2
     *
     * @return void
     */
    function register_form() {
        if ( !isset( $_GET['type'] ) || $_GET['type'] != 'wpuf_sub' ) {
            return;
        }

        if ( !isset( $_GET['pack_id'] ) || empty( $_GET['pack_id'] ) ) {
            return;
        }

        $pack_id = (int) $_GET['pack_id'];
        ?>
        <input type="hidden" name="wpuf_sub" value="yes" />
        <input type="hidden" name="pack_id" value="<?php echo $pack_id; ?>" />

        <?php
    }

    /**
     * Redirect to payment page or add free subscription after user registration
     *
     * @since 2.2
     *
     * @param  int $user_id
     * @return void
     */
    function after_registration( $user_id ) {

        if ( !isset( $_POST['wpuf_sub'] ) || $_POST['wpuf_sub'] != 'yes' ) {
            return $user_id;
        }

        if ( !isset( $_POST['pack_id'] ) || empty( $_POST['pack_id'] ) ) {
            return $user_id;
        }

        $pack_id        = isset( $_POST['pack_id'] ) ? intval( $_POST['pack_id'] ) : 0;
        $pack           = $this->get_subscription( $pack_id );
        $billing_amount = ( $pack->meta_value['billing_amount'] >= 0 && !empty( $pack->meta_value['billing_amount'] ) ) ? $pack->meta_value['billing_amount'] : false;

        if ( $billing_amount === false ) {
            $this->new_subscription( $user_id, $pack_id, null, false, 'free' );
            WPUF_Subscription::add_used_free_pack( $user_id, $pack_id );
        } else {
            $pay_page = intval( wpuf_get_option( 'payment_page', 'wpuf_payment' ) );
            $redirect = add_query_arg( array( 'action' => 'wpuf_pay', 'type' => 'pack', 'pack_id' => (int) $pack_id ), get_permalink( $pay_page ) );
        }
    }

    /**
     * Enqueue scripts and styles
     *
     * @since 2.2
     */
    function subscription_script() {
        wp_enqueue_script( 'wpuf-subscriptions', WPUF_ASSET_URI . '/js/subscriptions.js', array('jquery'), false, true );
    }

    /**
     * Get all subscription packs
     *
     * @return array
     */
    function get_subscriptions() {
        $args = array(
            'post_type'      => 'wpuf_subscription',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        $posts = get_posts( $args );

        if ( $posts ) {
            foreach ($posts as $key => $post) {
                $post->meta_value = $this->get_subscription_meta( $post->ID, $posts );
            }
        }

        return $posts;
    }

    /**
     * Set meta fields on a subscription pack
     *
     * @since 2.2
     *
     * @param  int $subscription_id
     * @param  \WP_Post $pack_post
     * @return array
     */
    public static function get_subscription_meta( $subscription_id,  $pack_post = null ) {

        $meta['post_content']         =  isset( $pack_post->post_content ) ? $pack_post->post_content : '';
        $meta['post_title']           =  isset( $pack_post->post_title ) ? $pack_post->post_title : '';
        $meta['billing_amount']       =  get_post_meta( $subscription_id, '_billing_amount', true );
        $meta['expiration_number']    =  get_post_meta( $subscription_id, '_expiration_number', true );
        $meta['expiration_period']    =  get_post_meta( $subscription_id, '_expiration_period', true );
        $meta['recurring_pay']        =  get_post_meta( $subscription_id, '_recurring_pay', true );
        $meta['billing_cycle_number'] =  get_post_meta( $subscription_id, '_billing_cycle_number', true );
        $meta['cycle_period']         =  get_post_meta( $subscription_id, '_cycle_period', true );
        $meta['billing_limit']        =  get_post_meta( $subscription_id, '_billing_limit', true );
        $meta['trial_status']         =  get_post_meta( $subscription_id, '_trial_status', true );
        $meta['trial_cost']           =  get_post_meta( $subscription_id, '_trial_cost', true );
        $meta['trial_duration']       =  get_post_meta( $subscription_id, '_trial_duration', true );
        $meta['trial_duration_type']  =  get_post_meta( $subscription_id, '_trial_duration_type', true );
        $meta['post_type_name']       =  get_post_meta( $subscription_id, '_post_type_name', true );

        return $meta;
    }

    /**
     * Get all post types
     *
     * @since 2.2
     * @return array
     */
    function get_all_post_type() {
        $post_types = get_post_types();

        unset(
            $post_types['attachment'],
            $post_types['revision'],
            $post_types['nav_menu_item'],
            $post_types['wpuf_forms'],
            $post_types['wpuf_profile'],
            $post_types['wpuf_subscription'],
            $post_types['wpuf_coupon'],
            $post_types['wpuf_input']
        );

        return apply_filters( 'wpuf_posts_type', $post_types );
    }

    /**
     * Post type name placeholder text
     *
     * @param  string $title
     * @return string
     */
    function change_default_title( $title ) {
        $screen = get_current_screen();

        if ( 'wpuf_subscription' == $screen->post_type ) {
            $title = __( 'Pack Name', 'wpuf' );
        }

        return $title;
    }

    /**
     * Save form data
     *
     * @param  int $post_ID
     * @param  \WP_Post $post
     * @return void
     */
    function save_form_meta( $subscription_id, $post ) {
        $post = $_POST;

        if ( !isset( $post['wpuf_subscription'] ) ) {
            return;
        }

        if ( !wp_verify_nonce( $post['wpuf_subscription'], 'wpuf_subscription_editor' ) ) {
            return;
        }

        // Is the user allowed to edit the post or page?
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return;
        }

        update_post_meta( $subscription_id, '_billing_amount', $post['billing_amount'] );
        update_post_meta( $subscription_id, '_expiration_number', $post['expiration_number'] );
        update_post_meta( $subscription_id, '_expiration_period', $post['expiration_period'] );
        update_post_meta( $subscription_id, '_recurring_pay', $post['recurring_pay'] );
        update_post_meta( $subscription_id, '_billing_cycle_number', $post['billing_cycle_number'] );
        update_post_meta( $subscription_id, '_cycle_period', $post['cycle_period'] );
        update_post_meta( $subscription_id, '_billing_limit', $post['billing_limit'] );
        update_post_meta( $subscription_id, '_trial_status', $post['trial_status'] );
        update_post_meta( $subscription_id, '_trial_cost', $post['trial_cost'] );
        update_post_meta( $subscription_id, '_trial_duration', $post['trial_duration'] );
        update_post_meta( $subscription_id, '_trial_duration_type', $post['trial_duration_type'] );
        update_post_meta( $subscription_id, '_post_type_name', $post['post_type_name'] );

        do_action( 'wpuf_update_subscription_pack', $subscription_id, $post );
    }

    /**
     * Subscription post types
     *
     * @return void
     */
    function register_post_type() {

        $capability = wpuf_admin_role();

        register_post_type( 'wpuf_subscription', array(
            'label'           => __( 'Subscription', 'wpuf' ),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => false,
            'hierarchical'    => false,
            'query_var'       => false,
            'supports'        => array('title'),
            'capability_type' => 'post',
            'capabilities'    => array(
                'publish_posts'       => $capability,
                'edit_posts'          => $capability,
                'edit_others_posts'   => $capability,
                'delete_posts'        => $capability,
                'delete_others_posts' => $capability,
                'read_private_posts'  => $capability,
                'edit_post'           => $capability,
                'delete_post'         => $capability,
                'read_post'           => $capability,
            ),
            'labels' => array(
                'name'               => __( 'Subscription', 'wpuf' ),
                'singular_name'      => __( 'Subscription', 'wpuf' ),
                'menu_name'          => __( 'Subscription', 'wpuf' ),
                'add_new'            => __( 'Add Subscription', 'wpuf' ),
                'add_new_item'       => __( 'Add New Subscription', 'wpuf' ),
                'edit'               => __( 'Edit', 'wpuf' ),
                'edit_item'          => __( 'Edit Subscription', 'wpuf' ),
                'new_item'           => __( 'New Subscription', 'wpuf' ),
                'view'               => __( 'View Subscription', 'wpuf' ),
                'view_item'          => __( 'View Subscription', 'wpuf' ),
                'search_items'       => __( 'Search Subscription', 'wpuf' ),
                'not_found'          => __( 'No Subscription Found', 'wpuf' ),
                'not_found_in_trash' => __( 'No Subscription Found in Trash', 'wpuf' ),
                'parent'             => __( 'Parent Subscription', 'wpuf' ),
            ),
        ) );
    }

    /**
     * Update users subscription
     *
     * Updates the pack when new re-curring payment IPN notification is being
     * sent from PayPal.
     *
     * @return void
     */
    function update_paypal_subscr_payment() {
        if ( !isset( $_POST['txn_type'] ) && $_POST['txn_type'] != 'subscr_payment'  ) {
            return;
        }

        if ( strtolower( $_POST['payment_status'] ) != 'completed' ) {
            return;
        }

        $pack  = $this->get_subscription( $pack_id );
        $payer = json_decode( stripcslashes( $_POST['custom'] ) );

        $this->update_user_subscription_meta( $payer->payer_id, $pack );
    }

    function add_meta_box_subscription_post() {
        add_meta_box( 'wpuf-metabox-subscription', __( 'Billing Details', 'wpuf' ), array($this, 'subscription_form_elements_post'), 'wpuf_subscription', 'normal', 'high' );
    }

    function subscription_form_elements_post() {
        require_once dirname(__FILE__) . '/../admin/subscription.php';
        ?>
        <div class="wrap">
            <?php WPUF_Admin_Subscription::getInstance()->form(); ?>
        </div>
        <?php
    }

    /**
     * Get a subscription row from database
     *
     * @global object $wpdb
     * @param int $sub_id subscription pack id
     * @return object|bool
     */
    public static function get_subscription( $sub_id ) {
        $pack = get_post( $sub_id );

        if ( ! $pack ) {
            return false;
        }

        $pack->meta_value = self::get_subscription_meta( $sub_id, $pack );

        return $pack;
    }



    /**
     * Set the new post status if charging is active
     *
     * @param string $postdata
     * @return string
     */
    function set_pending( $postdata ) {

        if ( wpuf_get_option( 'charge_posting', 'wpuf_payment' ) == 'yes' ) {
            $postdata['post_status'] = 'pending';
        }

        return $postdata;
    }

    /**
     * Checks the posting validity after a new post
     *
     * @global object $userdata
     * @global object $wpdb
     * @param int $post_id
     */
    function monitor_new_post( $post_id, $form_id, $form_settings ) {
        // check form if subscription is disabled
        if ( isset( $form_settings['subscription_disabled'] ) && $form_settings['subscription_disabled'] == 'yes' ) {
            return;
        }
        global $wpdb, $userdata;

        // bail out if charging is not enabled
        if ( wpuf_get_option( 'charge_posting', 'wpuf_payment', 'no' ) != 'yes' ) {
            return;
        }

        $userdata = get_userdata( get_current_user_id() );

        if ( self::has_user_error( $form_settings ) ) {
            //there is some error and it needs payment
            //add a uniqid to track the post easily
            $order_id = uniqid( rand( 10, 1000 ), false );
            update_post_meta( $post_id, '_wpuf_order_id', $order_id, true );

        } else {

            $sub_info    = self::get_user_pack( $userdata->ID );
            $post_type   = isset( $form_settings['post_type'] ) ? $form_settings['post_type'] : 'post';
            $count       = isset( $sub_info['posts'][$post_type] ) ? intval( $sub_info['posts'][$post_type] ) : 0;
            $post_status = isset( $form_settings['post_status'] ) ? $form_settings['post_status'] : 'publish';

            wp_update_post( array( 'ID' => $post_id , 'post_status' => $post_status) );

            // decrease the post count, if not umlimited
            if ( $count > 0 ) {
                $sub_info['posts'][$post_type] = $count - 1;
                $this->update_user_subscription_meta( $userdata->ID, $sub_info );
            }
        }

    }

    /**
     * Redirect to payment page after new post
     *
     * @param string $str
     * @param type $post_id
     * @return string
     */
    function post_redirect( $response, $post_id, $form_id, $form_settings ) {

        if ( self::has_user_error( $form_settings ) ) {

            $order_id = get_post_meta( $post_id, '_wpuf_order_id', true );

            // check if there is a order ID
            if ( $order_id ) {
                $response['show_message'] = false;
                $response['redirect_to']  = add_query_arg( array(
                    'action'  => 'wpuf_pay',
                    'type'    => 'post',
                    'post_id' => $post_id
                ), get_permalink( wpuf_get_option( 'payment_page', 'wpuf_payment' ) ) );

                return $response;
            }
        }

        return $response;
    }

    /**
     * Perform actions when a new payment is made
     *
     * @param array $info payment info
     */
    function payment_received( $info, $recurring ) {

        if ( $info['post_id'] ) {

            $this->handle_post_publish( $info['post_id'] );

        } else if ( $info['pack_id'] ) {
            $profile_id = isset( $info['profile_id'] ) ? $info['profile_id'] : null;
            $this->new_subscription( $info['user_id'], $info['pack_id'], $profile_id, $recurring, $info['status'] );
        }
    }

    /**
     * Store new subscription info on user profile
     *
     * if data = 0, means 'unlimited'
     *
     * @param int $user_id
     * @param int $pack_id subscription pack id
     */
    public function new_subscription( $user_id, $pack_id, $profile_id = null, $recurring, $status = null ) {
        $subscription = $this->get_subscription( $pack_id );

        if ( $user_id && $subscription ) {

            $user_meta = array(
                'pack_id' => $pack_id,
                'posts'   => $subscription->meta_value['post_type_name'],
                'status'  => $status
            );

            if ( $recurring ) {
                $totla_date =  date( 'd-m-Y', strtotime('+' . $cycle_number . $cycle_period . 's') );
                $user_meta['expire']     = '';
                $user_meta['profile_id'] = $profile_id;
                $user_meta['recurring']  = 'yes';
            } else {

                $period_type            = $subscription->meta_value['expiration_period'];
                $period_number          = $subscription->meta_value['expiration_number'];
                $date                   = date( 'd-m-Y', strtotime('+' . $period_number . $period_type . 's') );
                $expired                = ( empty( $period_number ) || ( $period_number == 0 ) ) ? 'unlimited' : wpuf_date2mysql( $date );
                $user_meta['expire']    = $expired;
                $user_meta['recurring'] = 'no';
            }

            $user_meta = apply_filters( 'wpuf_new_subscription', $user_meta, $user_id, $pack_id, $recurring );

            $this->update_user_subscription_meta( $user_id, $user_meta );
        }
    }

    public static function update_user_subscription_meta( $user_id, $user_meta ) {

        update_user_meta( $user_id, '_wpuf_subscription_pack', $user_meta );
    }

    public static function post_by_orderid( $order_id ) {
        global $wpdb;

        //$post = get_post( $post_id );
        $sql = $wpdb->prepare( "SELECT p.ID, p.post_status
            FROM $wpdb->posts p, $wpdb->postmeta m
            WHERE p.ID = m.post_id AND p.post_status <> 'publish' AND m.meta_key = '_wpuf_order_id' AND m.meta_value = %s", $order_id );

        return $wpdb->get_row( $sql );
    }

    /**
     * Publish the post if payment is made
     *
     * @param int $post_id
     */
    function handle_post_publish( $order_id ) {
        $post = self::post_by_orderid( $order_id );

        if ( $post && $post->post_status != 'publish' ) {
            $this->set_post_status( $post->ID );
        }
    }

    /**
     * Maintain post status from the form settings
     *
     * @since 2.1.9
     * @param int $post_id
     */
    function set_post_status( $post_id ) {
        $post_status = 'publish';
        $form_id     = get_post_meta( $post_id, '_wpuf_form_id', true );

        if ( $form_id ) {
            $form_settings = wpuf_get_form_settings( $form_id );
            $post_status   = $form_settings['post_status'];
        }

        $update_post = array(
            'ID'          => $post_id,
            'post_status' => $post_status
        );

        wp_update_post( $update_post );
    }

    /**
     * Generate users subscription info with a shortcode
     *
     * @global type $userdata
     */
    function subscription_info() {

        if ( wpuf_get_option( 'charge_posting', 'wpuf_payment' ) != 'yes' || !is_user_logged_in() ) {
            return;
        }

        global $userdata;

        ob_start();

        $userdata = get_userdata( $userdata->ID ); //wp 3.3 fix

        $user_sub = self::get_user_pack( $userdata->ID );
        if ( !isset( $user_sub['pack_id'] ) ) {
            return;
        }

        $pack = $this->get_subscription( $user_sub['pack_id'] );

        $details_meta = $this->get_details_meta_value();

        $billing_amount = ( intval( $pack->meta_value['billing_amount'] ) > 0 ) ? $details_meta['symbol'] . $pack->meta_value['billing_amount'] : __( 'Free', 'wpuf' );
        if ( $pack->meta_value['recurring_pay'] == 'yes' ) {
            $recurring_des = sprintf( 'For each %s %s', $pack->meta_value['billing_cycle_number'], $pack->meta_value['cycle_period'], $pack->meta_value['trial_duration_type'] );
            $recurring_des .= !empty( $pack->meta_value['billing_limit'] ) ? sprintf( ', for %s installments', $pack->meta_value['billing_limit'] ) : '';
            $recurring_des = $recurring_des;
        } else {
            $recurring_des = '';
        }

        ?>
        <div class="wpuf_sub_info">
            <h3><?php _e( 'Subscription Details', 'wpuf' ); ?></h3>
            <div class="wpuf-text">
                <div><strong><?php _e( 'Subcription Name: ','wpuf' ); ?></strong><?php echo $pack->post_title; ?></div>
                <div>
                    <strong><?php _e( 'Package & billing details: '); ?></strong>

                    <div class="wpuf-pricing-wrap">
                        <div class="wpuf-sub-amount">
                            <?php echo $billing_amount; ?>
                            <?php echo $recurring_des; ?>
                        </div>
                    </div>

                </div>
                <div>
                    <strong><?php _e( 'Remaining post: ', 'wpuf'); ?></strong>
                    <?php
                    foreach ($user_sub['posts'] as $key => $value) {
                        $value = intval( $value );

                        if ( $value === 0 ) {
                            continue;
                        }

                        $post_type_obj = get_post_type_object( $key );
                        if ( ! $post_type_obj ) {
                            continue;
                        }
                        ?>
                         <div><?php echo $post_type_obj->labels->name . ': ' . $value; ?></div>
                        <?php
                    }
                    ?>
                </div>
                <?php
                if ( $user_sub['recurring'] != 'yes' ) {
                    if ( !empty( $user_sub['expire'] ) ) {

                        $expire =  ( $user_sub['expire'] == 'unlimited' ) ? ucfirst( 'unlimited' ) : wpuf_date2mysql( $user_sub['expire'] );

                        ?>
                        <div class="wpuf-expire">
                            <strong><?php echo _e('Expire date:'); ?></strong> <?php echo wpuf_get_date( $expire ); ?>
                        </div>
                        <?php
                    }

                } ?>
            </div>
            <?php
            if ( $user_sub['recurring'] == 'yes' ) {
                $payment_page = get_permalink( wpuf_get_option( 'payment_page', 'wpuf_payment' ) );
                ?>
                <form action="" method="post">
                    <?php wp_nonce_field( '_wpnonce', 'wpuf_payment_cancel' ); ?>
                    <input type="hidden" name="user_id" value="<?php echo $userdata->ID; ?>">
                    <input type="hidden" name="action" value="wpuf_cancel_pay">
                    <input type="hidden" name="gateway" value="paypal">
                    <input type="submit" name="wpuf_payment_cancel_submit" value="cancel">
                </form>
                <?php $subscription_page = wpuf_get_option( 'subscription_page','wpuf_payment' ); ?>
                <a href="<?php echo get_permalink( $subscription_page ); ?>"><? _e( 'Change', 'wpuf'); ?></a>
                <?php
            }
        echo '</div>';

        $content = ob_get_clean();

        return apply_filters( 'wpuf_sub_info', $content, $userdata, $user_sub, $pack );
    }


    /**
     * Show the subscription packs that are built
     * from admin Panel
     */
    function subscription_packs() {
        $cost_per_post = wpuf_get_option( 'charge_posting', 'wpuf_payment' );

        if ( $cost_per_post != 'yes' ) {
            _e('Please enable force pack and charge posting from admin panel', 'wpuf' );
            return;
        }

        $packs = $this->get_subscriptions();
        $details_meta = $this->get_details_meta_value();
        ob_start();

        if ( $packs ) {
            echo '<ul class="wpuf_packs">';
            foreach ($packs as $pack) {
                $class = 'wpuf-pack-' . $pack->ID;
                ?>
                <li class="<?php echo $class; ?>">
                <?php $this->pack_details( $pack, $details_meta ); ?>
                </li>
                <?php
            }
            echo '</ul>';
        }

        $contents = ob_get_clean();

        return apply_filters( 'wpuf_subscription_packs', $contents, $packs );
    }

    function get_details_meta_value() {

        $meta['payment_page'] = get_permalink( wpuf_get_option( 'payment_page', 'wpuf_payment' ) );
        $meta['onclick'] = '';
        $meta['symbol'] = wpuf_get_option( 'currency_symbol', 'wpuf_payment' );

        return $meta;
    }

    function pack_details( $pack, $details_meta, $coupon_satus = false ) {

        $billing_amount = ( $pack->meta_value['billing_amount'] >= 0 && !empty( $pack->meta_value['billing_amount'] ) ) ? $pack->meta_value['billing_amount'] : '0.00';

        if ( $billing_amount && $pack->meta_value['recurring_pay'] == 'yes' ) {
            $recurring_des = sprintf( 'Every %s %s', $pack->meta_value['billing_cycle_number'], $pack->meta_value['cycle_period'], $pack->meta_value['trial_duration_type'] );
            $recurring_des .= !empty( $pack->meta_value['billing_limit'] ) ? sprintf( ', for %s installments', $pack->meta_value['billing_limit'] ) : '';
            $recurring_des = '<div class="wpuf-pack-cycle wpuf-nullamount-hide">'.$recurring_des.'</div>';
        } else {
            $recurring_des = '<div class="wpuf-pack-cycle wpuf-nullamount-hide">' . __( 'One time payment', 'wpuf' ) . '</div>';
        }

        if ( $billing_amount && $pack->meta_value['recurring_pay'] == 'yes' && $pack->meta_value['trial_status'] == 'yes' ) {

            $trial_cost = ( empty( $pack->meta_value['trial_cost'] ) || $pack->meta_value['trial_cost'] == 0 ) ? __( 'Free', 'wpuf' ) : $details_meta['symbol'].$pack->meta_value['trial_cost'];
            $trial_des = sprintf( '%s for the first %s %s', $trial_cost, $pack->meta_value['trial_duration'], $pack->meta_value['trial_duration_type']  );

        } else {
            $trial_des = '';
        }

        if (  ! is_user_logged_in()  ) {
            $button_name = __( 'Sign Up', 'wpuf' );
            $url = wp_login_url();
        } else if ( $billing_amount == '0.00' ) {
            $button_name = __( 'Free', 'wpuf' );
        } else {
            $button_name = __( 'Buy Now', 'wpuf' );
        }
        ?>
        <div class="wpuf-pricing-wrap">
            <h3><?php echo wp_kses_post( $pack->post_title ); ?> </h3>
            <div class="wpuf-sub-amount">

                <?php if ( $billing_amount != '0.00' ) { ?>
                    <sup class="wpuf-sub-symbol"><?php echo $details_meta['symbol']; ?></sup>
                    <span class="wpuf-sub-cost"><?php echo $billing_amount; ?></span>
                <?php } else { ?>
                    <span class="wpuf-sub-cost"><?php _e( 'Free', 'wpuf' ); ?></span>
                <?php } ?>

                <?php echo $recurring_des; ?>

            </div>
            <?php
            if ( $pack->meta_value['recurring_pay'] == 'yes' ) {
            ?>
                <div class="wpuf-sub-body wpuf-nullamount-hide">
                    <div class="wpuf-sub-terms"><?php echo $trial_des; ?></div>
                </div>
            <?php
            }
            ?>
        </div>
        <div class="wpuf-sub-desciption">
            <?php echo wpautop( wp_kses_post( $pack->post_content ) ); ?>
        </div>
        <?php

        if ( isset( $_GET['action'] ) && $_GET['action'] == 'wpuf_pay' || $coupon_satus ) {
            return;
        }
        if ( $coupon_satus === false && is_user_logged_in() ) {
            ?>
                <div class="wpuf-sub-button"><a href="<?php echo add_query_arg( array('action' => 'wpuf_pay', 'type' => 'pack', 'pack_id' => $pack->ID ), $details_meta['payment_page'] ); ?>" onclick="<?php echo esc_attr( $details_meta['onclick'] ); ?>"><?php echo $button_name; ?></a></div>
            <?php
        } else {
            ?>
                <div class="wpuf-sub-button"><a href="<?php echo add_query_arg( array( 'action' => 'register', 'type' => 'wpuf_sub', 'pack_id' => $pack->ID ), wp_registration_url() ); ?>" onclick="<?php echo esc_attr( $details_meta['onclick'] ); ?>"><?php echo $button_name; ?></a></div>
            <?php
            //wp_registration_url()
        }

    }

    /**
     * Show a info message when posting if payment is enabled
     */
    function add_post_info( $form_id, $form_settings ) {
        if ( self::has_user_error( $form_settings ) ) {
            ?>
            <div class="wpuf-info">
                <?php
                $text = sprintf( __( 'This will cost you <strong>%s</strong> to add a new post. ', 'wpuf' ), wpuf_get_option( 'currency_symbol', 'wpuf_payment' ) . wpuf_get_option( 'cost_per_post', 'wpuf_payment' ) );

                echo apply_filters( 'wpuf_ppp_notice', $text, $form_id, $form_settings );
                ?>
            </div>
            <?php
        }
    }

    public static function get_user_pack( $user_id, $status = true ) {
        return get_user_meta( $user_id, '_wpuf_subscription_pack', $status );
    }

    function force_pack_notice( $text, $id, $form_settings ) {
        $force_pack = wpuf_get_option( 'force_pack', 'wpuf_payment' );

        if ( $force_pack == 'yes' && WPUF_Subscription::has_user_error($form_settings) ) {
            $pack_page = get_permalink( wpuf_get_option( 'subscription_page', 'wpuf_payment' ) );

            $text = sprintf( __( 'You must <a href="%s">purchase a pack</a> before posting', 'wpuf' ), $pack_page );
        }

        return apply_filters( 'wpuf_pack_notice', $text, $id, $form_settings );
    }

    function force_pack_permission( $perm, $id, $form_settings ) {
        $force_pack = wpuf_get_option( 'force_pack', 'wpuf_payment' );

        if ( $force_pack == 'yes' && WPUF_Subscription::has_user_error( $form_settings ) ) {
            return 'no';
        }

        return $perm;
    }

    /**
     * Checks against the user, if he is valid for posting new post
     *
     * @global object $userdata
     * @return bool
     */
    public static function has_user_error( $form_settings = null ) {
        global $userdata;
        $user_id = isset( $userdata->ID ) ? $userdata->ID : '';
        // bail out if charging is not enabled
        if ( wpuf_get_option( 'charge_posting', 'wpuf_payment' ) != 'yes' ) {
            return false;
        }

        // check form if subscription is disabled
        if ( isset( $form_settings['subscription_disabled'] ) && $form_settings['subscription_disabled'] == 'yes' ) {
            return false;
        }

        $user_sub_meta  = self::get_user_pack( $user_id );
        $form_post_type = isset( $form_settings['post_type'] ) ? $form_settings['post_type'] : 'post';
        $post_count     = isset( $user_sub_meta['posts'][$form_post_type] ) ? $user_sub_meta['posts'][$form_post_type] : 0;

        if ( isset( $user_sub_meta['recurring'] ) && $user_sub_meta['recurring'] == 'yes' ) {

            // user has recurring subscription
            if ( $post_count > 0 || $post_count == '-1' ) {
                return false;
            } else {
                return true;
            }

        } else {
            $expire = isset( $user_sub_meta['expire'] ) ? $user_sub_meta['expire'] : 0;

            if ( strtolower( $expire ) == 'unlimited' || empty( $expire ) ) {
                $expire_status = false;
            } else if ( ( strtotime( date( 'Y-m-d', strtotime( $expire ) ) ) >= strtotime( date( 'Y-m-d', time() ) ) ) && ( $post_count > 0  || $post_count == '-1' ) ) {
                $expire_status = false;
            } else {
                $expire_status = true;
            }



            if ( $post_count > 0 || $post_count == '-1' ) {
                $post_count_status = false;
            } else {
                $post_count_status = true;
            }

            if( $expire_status || $post_count_status ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user has used a free pack before
     *
     * @since 2.1.8
     *
     * @param int $user_id
     * @param int $pack_id
     * @return boolean
     */
    public static function has_used_free_pack( $user_id, $pack_id ) {
        $has_used = get_user_meta( $user_id, 'wpuf_fp_used', true );

        if ( $has_used == '' ) {
            return false;
        }

        if ( is_array( $has_used ) && isset( $has_used[$pack_id] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Add a free used pack to the user account
     *
     * @since 2.1.8
     *
     * @param int $user_id
     * @param int $pack_id
     */
    public static function add_used_free_pack( $user_id, $pack_id ) {
        $has_used = get_user_meta( $user_id, 'wpuf_fp_used', true );
        $has_used = is_array( $has_used ) ? $has_used : array();

        $has_used[$pack_id] = $pack_id;
        update_user_meta( $user_id, 'wpuf_fp_used', $has_used );
    }

    function packdropdown( $packs, $selected = '' ) {
        $packs = isset( $packs ) ? $packs : array();
        foreach( $packs as $key => $pack ) {
            ?>
            <option value="<?php echo $pack->ID; ?>" <?php selected( $selected, $pack->ID ); ?>><?php echo $pack->post_title; ?></option>
            <?php
        }
    }

}