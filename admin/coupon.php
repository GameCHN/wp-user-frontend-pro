<?php

/**
 * Coupon Class
 *
 * @package WPUF
 */
class WPUF_Admin_Coupon {

    function __construct() {

        add_action( 'init', array( $this, 'register_post_type' ) );

        add_action( 'add_meta_boxes_wpuf_coupon', array( $this, 'add_meta_box_coupon_post' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'script_loader' ) );

        add_action( 'save_post', array( $this, 'save_form_meta' ), 1, 3 );

        add_filter( 'enter_title_here', array( $this, 'change_default_title' ) );
        add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

        add_filter( 'manage_wpuf_coupon_posts_columns', array( $this, 'coupon_columns_head' ) );
        add_action( 'manage_wpuf_coupon_posts_custom_column', array( $this, 'coupon_columns_content' ), 10, 2 );
    }

    /**
     * Load all the scripts
     *
     * @return void
     */
    function script_loader() {
        wp_enqueue_script( 'wpuf-chosen', plugins_url( '../assets/js/chosen.jquery.js', __FILE__ ), array( 'jquery' ), false, true );
        wp_enqueue_style( 'wpuf-chosen-style', plugins_url( '../assets/css/chosen/chosen.css', __FILE__ ) );
    }

    /**
     * Coupon list table column values
     *
     * @param  string $column_name
     * @param  int $post_ID
     * @return void
     */
    function coupon_columns_content( $column_name, $post_ID ) {
        switch ( $column_name ) {
            case 'coupon_type':
                $price = get_post_meta( $post_ID, '_type', true );

                if ( $price == 'amount' ) {
                    _e( 'Fixed Price', 'wpuf' );
                } else {
                    _e( 'Percentage', 'wpuf' );
                }

                break;

            case 'amount':
                
                $type        = get_post_meta( $post_ID, '_type', true );
                $currency    = ( $type != 'percent' ) ? wpuf_get_option( 'currency_symbol', 'wpuf_payment' ) : '';
                $symbol      = ( $type == 'percent' ) ? '%' : '';
                echo $currency . get_post_meta( $post_ID, '_amount', true ) . $symbol;
                break;

            case 'usage_limit':
                $usage_limit = get_post_meta( $post_ID, '_usage_limit', true );

                if ( intval( $usage_limit ) == 0 ) {
                    $usage_limit = __( '&infin;', 'wpuf' );
                }

                $use = intval( get_post_meta( $post_ID, '_coupon_used', true ) );
                echo $use . '/' . $usage_limit;
                break;

            case 'expire_date':

                $start_date = get_post_meta( $post_ID, '_start_date', true );
                $end_date   = get_post_meta( $post_ID, '_end_date', true );

                $start_date = !empty( $start_date ) ? date_i18n( 'M j, Y', strtotime( $start_date ) ) : '';
                $end_date   = !empty( $end_date ) ? date_i18n( 'M j, Y', strtotime( $end_date ) ) : '';

                echo $start_date . ' to ' . $end_date;
                break;
        }
    }

    /**
     * Coupon list table columns
     *
     * @param  array $head
     * @return array
     */
    function coupon_columns_head( $head ) {
        unset( $head['date'] );

        $head['title']       = __( 'Coupon Code', 'wpuf' );
        $head['coupon_type'] = __( 'Coupon Type', 'wpuf' );
        $head['amount']      = __( 'Amount', 'wpuf' );
        $head['usage_limit'] = __( 'Usage / Limit', 'wpuf' );
        $head['expire_date'] = __( 'Expire date', 'wpuf' );


        return $head;
    }

    /**
     * Custom post update message
     *
     * @param  array $messages
     * @return array
     */
    function updated_messages( $messages ) {
        $message = array(
            0  => '',
            1  => __( 'Coupon updated.' ),
            2  => __( 'Custom field updated.' ),
            3  => __( 'Custom field deleted.' ),
            4  => __( 'Coupon updated.' ),
            5  => isset( $_GET['revision'] ) ? sprintf( __( 'Coupon restored to revision from %s' ), wp_post_revision_title( ( int ) $_GET['revision'], false ) ) : false,
            6  => __( 'Coupon published.' ),
            7  => __( 'Coupon saved.' ),
            8  => __( 'Coupon submitted.' ),
            9  => '',
            10 => __( 'Coupon draft updated.' ),
        );

        $messages['wpuf_coupon'] = $message;

        return $messages;
    }

    /**
     * Placeholder text for coupon post title field
     *
     * @param  string $title
     * @return string
     */
    function change_default_title( $title ) {
        $screen = get_current_screen();

        if ( 'wpuf_coupon' == $screen->post_type ) {
            $title = __( 'Enter coupon code', 'wpuf' );
        }

        return $title;
    }

    /**
     * Register coupon post type
     *
     * @return void
     */
    function register_post_type() {
        $capability = wpuf_admin_role();

        register_post_type( 'wpuf_coupon', array(
            'label'           => __( 'Coupon', 'wpuf' ),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => false,
            'capability_type' => 'post',
            'hierarchical'    => false,
            'query_var'       => false,
            'supports'        => array( 'title' ),
            'capabilities' => array(
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
            'labels'              => array(
                'name'               => __( 'Coupon', 'wpuf' ),
                'singular_name'      => __( 'Coupon', 'wpuf' ),
                'menu_name'          => __( 'Coupon', 'wpuf' ),
                'add_new'            => __( 'Add Coupon', 'wpuf' ),
                'add_new_item'       => __( 'Add New Coupon', 'wpuf' ),
                'edit'               => __( 'Edit', 'wpuf' ),
                'edit_item'          => __( 'Edit Coupon', 'wpuf' ),
                'new_item'           => __( 'New Coupon', 'wpuf' ),
                'view'               => __( 'View Coupon', 'wpuf' ),
                'view_item'          => __( 'View Coupon', 'wpuf' ),
                'search_items'       => __( 'Search Coupon', 'wpuf' ),
                'not_found'          => __( 'No Coupon Found', 'wpuf' ),
                'not_found_in_trash' => __( 'No Coupon Found in Trash', 'wpuf' ),
                'parent'             => __( 'Parent Coupon', 'wpuf' ),
            ),
        ) );
    }

    /**
     * Adds coupon details meta boxe
     *
     * @return void
     */
    function add_meta_box_coupon_post() {
        add_meta_box( 'wpuf-metabox-coupon', __( 'Coupon Details', 'wpuf' ), array( $this, 'settings_form' ), 'wpuf_coupon', 'normal', 'high' );
    }

    /**
     * Save coupon details
     *
     * @param int     $post_ID
     * @param WP_Post $post
     * @return void
     */
    function save_form_meta( $post_ID, $post ) {
        $post = $_POST;

        if ( !isset( $post['wpuf_coupon'] ) ) {
            return;
        }

        if ( !wp_verify_nonce( $post['wpuf_coupon'], 'wpuf_coupon_editor' ) ) {
            return;
        }

        // Is the user allowed to edit the post or page?
        if ( !current_user_can( 'edit_post', $post_ID ) ) {
            return;
        }

        $this->update_coupon_meta( $post_ID, $post );
    }

    /**
     * Update coupon meta
     *
     * @param  int $post_id
     * @param  array $post
     * @return void
     */
    function update_coupon_meta( $post_id, $post ) {
        $acccess = !empty( $post['access'] ) ? explode( "\n", $post['access'] ) : array( );

        update_post_meta( $post_id, '_code', $post['code'] );
        update_post_meta( $post_id, '_package', $post['package'] );
        update_post_meta( $post_id, '_start_date', wpuf_date2mysql( $post['start_date'] ) );
        update_post_meta( $post_id, '_end_date', wpuf_date2mysql( $post['end_date'] ) );
        update_post_meta( $post_id, '_type', $post['type'] );
        update_post_meta( $post_id, '_amount', $post['amount'] );
        update_post_meta( $post_id, '_usage_limit', $post['usage_limit'] );
        update_post_meta( $post_id, '_access', $acccess );

        do_action( 'wpuf_update_coupon', $post_id, $post );
    }

    /**
     * Print the main settings form
     *
     * @return void
     */
    function settings_form() {
        global $post;

        $coupon = WPUF_Coupons::init()->get_coupon_meta( $post->ID );

        $start_date = !empty( $coupon['start_date'] ) ? date_i18n( 'M j, Y', strtotime( $coupon['start_date'] ) ) : '';
        $end_date   = !empty( $coupon['end_date'] ) ? date_i18n( 'M j, Y', strtotime( $coupon['end_date'] ) ) : '';
        $access     = !empty( $coupon['access'] ) ? $coupon['access'] : array( );
        $access = implode( "\n", $access );
        ?>
        <style>
            .chosen-container-multi .chosen-choices {
                height: 30px !important;
            }
        </style>
        <table class="form-table" style="width: 100%">

            <tbody>
            <input type="hidden" name="wpuf_coupon" id="wpuf_coupon_editor" value="<?php echo wp_create_nonce( 'wpuf_coupon_editor' ); ?>" />

            <?php do_action( 'wpuf_admin_coupon_form_top', $post->ID, $coupon ); ?>

            <tr valign="top">
                <td scope="row" class="label" for="wpuf-type"><span><?php _e( 'Type', 'wpuf' ); ?></span></td>

                <td>
                    <select id="wpuf-type" name="type">
                        <option value="amount" <?php selected( $coupon['type'], 'amount' ); ?>><?php _e( 'Fixed Price', 'wpuf' ); ?></option>
                        <option value="percent" <?php selected( $coupon['type'], 'percent' ); ?>><?php _e( 'Percentage', 'wpuf' ); ?></option>
                    </select>
                </td>
            </tr>

            <tr valign="top">
                <td scope="row" class="label"><label for="wpuf-amount"><?php _e( 'Amount', 'wpuf' ); ?></label></td>
                <td>
                    <input type="text" size="25" id="wpuf-amount" value="<?php echo esc_attr( $coupon['amount'] ); ?>" name="amount" />

                    <p class="description"><?php _e( 'Amount without <code>%</code> or currency symbol', 'wpuf' ); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <td scope="row" class="label"><label for="wpuf-content"><?php _e( 'Description', 'wpuf' ); ?></label></td>
                <td>
                    <textarea cols="45" rows="3" id="wpuf-content" name="post_content"><?php echo esc_textarea( $post->post_content ); ?></textarea>

                    <p class="description"><?php _e( 'Give a description of this coupon', 'wpuf' ); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <td scope="row" class="label"><label for="wpuf-package"><?php _e( 'Package', 'wpuf' ); ?></label></td>
                <td>
                    <select id="wpuf-package" multiple name="package[]" style="height: 100px !important;"><?php echo $this->get_pack_dropdown( $coupon['package'] ); ?></select>
                    <p class="description"><?php _e( 'Select one or more packages to apply coupon', 'wpuf' ); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <td scope="row" class="label"><label for="wpuf-usage-limit"><?php _e( 'Usage Limit', 'wpuf' ); ?></label></td>
                <td>
                    <input type="text" size="25" id="wpuf-usage-limit" value="<?php echo esc_attr( $coupon['usage_limit'] ); ?>" name="usage_limit" />

                    <p class="description"><?php _e( 'How many times the coupon can be used? Give a numeric value.', 'wpuf' ); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <td scope="row" class="label"><label for="wpuf-validity"><?php _e( 'Validity', 'wpuf' ); ?></label></td>
                <td>
                    <input type="text" class="wpuf-date-picker" placeholder="<?php _e( 'Start date', 'wpuf' ); ?>" size="25" id="" value="<?php echo esc_attr( $start_date ); ?>" name="start_date" />
                    <input type="text" class="wpuf-date-picker" placeholder="<?php _e( 'End date', 'wpuf' ); ?>" size="25" id="" value="<?php echo esc_attr( $end_date ); ?>" name="end_date" />
                    <span class="description"></span>
                </td>
            </tr>

            <tr valign="top">
                <td scope="row" class="label"><label for="wpuf-trial-priod"><?php _e( 'Email Restriction', 'wpuf' ); ?></label></td>

                <td>
                    <textarea type="text" size="25" id="wpuf-trial-priod" name="access" /><?php echo esc_attr( $access ); ?></textarea>
                    <p class="description"><?php _e( 'Only users with these email addresses will be able to use this coupon. Enter Email addresses. One per each line.', 'wpuf' ); ?></p>
                </td>
            </tr>

            <?php do_action( 'wpuf_admin_coupon_form_bottom', $post->ID, $coupon ); ?>
        </tbody>
        </table>

        <script type="text/javascript">
            jQuery( function($) {
                $('.wpuf-date-picker').datepicker();
                $('#wpuf-package').chosen({'width':'250px'});
            });
        </script>

        <?php
    }

    /**
     * Get all packs
     *
     * @return array
     */
    function get_packs() {
        $args = apply_filters( 'wpuf_get_packs', array(
            'post_type'   => 'wpuf_subscription',
            'post_status' => 'publish',
            'numberposts' => '-1'
        ) );

        $packs = get_posts( $args );

        return $packs;
    }

    /**
     * Get a single pack
     *
     * @param  int $pack_id
     * @return \WP_Post
     */
    function get_pack( $pack_id ) {
        $pack = get_post( $pack_id );

        $pack = apply_filters( 'wpuf_get_pack', $pack, $pack_id );

        return $pack;
    }

    /**
     * Get pack dropdown
     *
     * @param  array  $selected
     * @return void
     */
    function get_pack_dropdown( $selected = array( ) ) {
        $selected = is_array( $selected ) ? $selected : array( );
        $packs = $this->get_packs();
        ?>

        <option value="all"><?php _e( 'All package', 'wpuf' ); ?></option>
        <?php
        foreach ( $packs as $key => $pack_obj ) {
            $selecte = in_array( $pack_obj->ID, $selected ) ? 'selected' : '';
            ?>
            <option value="<?php echo esc_attr( $pack_obj->ID ); ?>" <?php echo $selecte; ?>><?php echo $pack_obj->post_title; ?></option>
            <?php
        }
    }

}
