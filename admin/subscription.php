<?php

/**
 * Manage Subscription packs
 *
 * @package WP User Frontend
 */
class WPUF_Admin_Subscription {

    private $table;
    private $db;
    public $baseurl;
    private static $_instance;

    public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new WPUF_Admin_Subscription();
        }

        return self::$_instance;
    }

    function __construct() {
        global $wpdb;

        $this->db = $wpdb;
        $this->table = $this->db->prefix . 'wpuf_subscription';
        $this->baseurl = admin_url( 'admin.php?page=wpuf_subscription' );

        add_filter( 'post_updated_messages', array($this, 'form_updated_message') );

        add_action( 'show_user_profile', array($this, 'profile_subscription_details'), 30 );
        add_action( 'edit_user_profile', array($this, 'profile_subscription_details'), 30 );
        add_action( 'personal_options_update', array($this, 'profile_subscription_update') );
        add_action( 'edit_user_profile_update', array($this, 'profile_subscription_update') );

        add_filter('manage_wpuf_subscription_posts_columns', array( $this, 'subscription_columns_head') );

        add_action('manage_wpuf_subscription_posts_custom_column', array( $this, 'subscription_columns_content' ),10, 2 );
    }

    /**
     * Custom post update message
     *
     * @param  array $messages
     * @return array
     */
    function form_updated_message( $messages ) {
        $message = array(
             0 => '',
             1 => __('Subscription pack updated.'),
             2 => __('Custom field updated.'),
             3 => __('Custom field deleted.'),
             4 => __('Subscription pack updated.'),
             5 => isset($_GET['revision']) ? sprintf( __('Subscription pack restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
             6 => __('Subscription pack published.'),
             7 => __('Subscription pack saved.'),
             8 => __('Subscription pack submitted.' ),
             9 => '',
            10 => __('Subscription pack draft updated.'),
        );

        $messages['wpuf_subscription'] = $message;

        return $messages;
    }

    /**
     * Update user profile lock
     *
     * @param int $user_id
     */
    function profile_subscription_update( $user_id ) {
        if ( !is_admin() && !current_user_can( 'edit_users' ) ) {
            return;
        }

        $pack_id = $_POST['pack_id'];
        $user_pack = WPUF_Subscription::get_user_pack( $_POST['user_id'] );

        if (isset($user_pack['pack_id']) && $pack_id == $user_pack['pack_id'] ) {
            if ( isset( $user_pack['recurring'] ) && $user_pack['recurring'] == 'yes' ) {
                foreach ( $user_pack['posts'] as $type => $value ) {
                    $user_pack['posts'][$type] = isset( $_POST[$type] ) ? $_POST[$type] : 0;
                }
            } else {
                foreach ( $user_pack['posts'] as $type => $value ) {
                    $user_pack['posts'][$type] = isset( $_POST[$type] ) ? $_POST[$type] : 0;
                }
                $user_pack['expire'] = isset( $_POST['expire'] ) ? wpuf_date2mysql( $_POST['expire'] ) : $user_pack['expire'];
            }
            WPUF_Subscription::update_user_subscription_meta( $user_id, $user_pack );
        } else {
            if ( $pack_id == '-1' ) {
                return;
            }
            WPUF_Subscription::init()->new_subscription( $user_id, $pack_id, null, false, $status = null );
        }
    }

    function subscription_columns_content( $column_name, $post_ID ) {
        switch ( $column_name ) {
            case 'amount':

                $amount = get_post_meta( $post_ID, '_billing_amount', true );
                if ( intval($amount) == 0 ) {
                    $amount = __( 'Free', 'wpuf' );
                } else {
                    $currency = wpuf_get_option( 'currency_symbol', 'wpuf_payment' );
                    $amount = $currency . $amount;
                }
                echo $amount;
                break;

            case 'recurring':

                $recurring = get_post_meta( $post_ID, '_recurring_pay', true );
                if ( $recurring == 'yes' ) {
                    _e( 'Yes', 'wpuf' );
                } else {
                    _e( 'No', 'wpuf' );
                }
                break;

            case 'duration':

                $recurring_pay        =  get_post_meta( $post_ID, '_recurring_pay', true );
                $billing_cycle_number =  get_post_meta( $post_ID, '_billing_cycle_number', true );
                $cycle_period         =  get_post_meta( $post_ID, '_cycle_period', true );
                if ( $recurring_pay == 'yes' ) {
                    echo $billing_cycle_number .' '. $cycle_period . '\'s (cycle)';
                } else {
                    $expiration_number    =  get_post_meta( $post_ID, '_expiration_number', true );
                    $expiration_period    =  get_post_meta( $post_ID, '_expiration_period', true );
                    echo $expiration_number .' '. $expiration_period . '\'s';
                }
                break;
        }

    }

    function subscription_columns_head( $head ) {
        unset($head['date']);
        $head['title']     = __('Pack Name', 'wpuf' );
        $head['amount']    = __( 'Amount', 'wpuf' );
        $head['recurring'] = __( 'Recurring', 'wpuf' );
        $head['duration']  = __( 'Duration', 'wpuf' );

        return $head;
    }

    function get_packs() {
        return $this->db->get_results( "SELECT * FROM {$this->table} ORDER BY created DESC" );
    }

    function get_pack( $pack_id ) {
        return $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $pack_id ) );
    }

    function delete_pack( $pack_id ) {
        $this->db->query( $this->db->prepare( "DELETE FROM {$this->table} WHERE id= %d", $pack_id ) );
    }

    function list_packs() {

        //delete packs
        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == "del" ) {
            check_admin_referer( 'wpuf_pack_del' );
            $this->delete_pack( $_GET['id'] );
            echo '<div class="updated fade" id="message"><p><strong>' . __( 'Pack Deleted', 'wpuf' ) . '</strong></p></div>';

            echo '<script type="text/javascript">window.location.href = "' . $this->baseurl . '";</script>';
        }
        ?>

        <table class="widefat meta" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th scope="col"><?php _e( 'Name', 'wpuf' ); ?></th>
                    <th scope="col"><?php _e( 'Description', 'wpuf' ); ?></th>
                    <th scope="col"><?php _e( 'Cost', 'wpuf' ); ?></th>
                    <th scope="col"><?php _e( 'Validity', 'wpuf' ); ?></th>
                    <th scope="col"><?php _e( 'Post Count', 'wpuf' ); ?></th>
                    <th scope="col"><?php _e( 'Action', 'wpuf' ); ?></th>
                </tr>
            </thead>
            <?php
            $packs = $this->get_packs();
            if ( $packs ) {
                $count = 0;
                foreach ($packs as $row) {
                    ?>
                    <tr valign="top" <?php echo ( ($count % 2) == 0) ? 'class="alternate"' : ''; ?>>
                        <td><?php echo stripslashes( htmlspecialchars( $row->name ) ); ?></td>
                        <td><?php echo stripslashes( htmlspecialchars( $row->description ) ); ?></td>
                        <td><?php echo $row->cost; ?> <?php echo get_option( 'wpuf_sub_currency' ); ?></td>
                        <td><?php echo ( $row->pack_length == 0 ) ? 'Unlimited' : $row->pack_length . ' days'; ?></td>
                        <td><?php echo ( $row->count == 0 ) ? 'Unlimited' : $row->count; ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url( add_query_arg( array('action' => 'edit', 'pack_id' => $row->id), $this->baseurl, 'wpuf_pack_edit' ) ); ?>">
                                <?php _e( 'Edit', 'wpuf' ); ?>
                            </a>
                            <span class="sep">|</span>
                            <a href="<?php echo wp_nonce_url( add_query_arg( array('action' => 'del', 'id' => $row->id), $this->baseurl ), 'wpuf_pack_del' ); ?>" onclick="return confirm('<?php _e( 'Are you sure to delete this pack?', 'wpuf' ); ?>');">
                                <?php _e( 'Delete', 'wpuf' ); ?>
                            </a>
                        </td>

                    </tr>
                    <?php
                    $count++;
                }
                ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6"><?php _e( 'No subscription pack found', 'wpuf' ); ?></td>
                </tr>
            <?php } ?>

        </table>
        <?php
    }

    function get_post_types( $post_types = null ) {

        if ( ! $post_types ) {
            $post_types = WPUF_Subscription::init()->get_all_post_type();
        }

        ob_start();

        foreach ( $post_types as $key => $name ) {
            $name = ( $post_types !== null ) ? $name : '';
            ?>
            <tr>
                <th><label for="wpuf-<?php echo esc_attr( $key ); ?>"><?php printf( 'Number of %ss', $key ); ?></label></th>
                <td>
                    <input type="text" size="20" style="" id="wpuf-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (int)$name ); ?>" name="post_type_name[<?php echo esc_attr( $key ); ?>]" />
                    <div><span class="description"><span><?php printf( 'How many %s the user can list with this pack? Enter <strong>-1</strong> for unlimited.', $key ); ?></span></span></div>
                </td>
            </tr>
            <?php
        }

        return ob_get_clean();
    }

    function form( $pack_id = null ) {
        global $post;

        $sub_meta = WPUF_Subscription::init()->get_subscription_meta( $post->ID, $post );

        $hidden_recurring_class = ( $sub_meta['recurring_pay'] != 'yes' ) ? 'none' : '';
        $hidden_trial_class     = ( $sub_meta['trial_status'] != 'yes' ) ? 'none' : '';
        $hidden_expire          = ( $sub_meta['recurring_pay'] == 'yes' ) ? 'none' : '';

        ?>

        <table class="form-table" style="width: 100%">
            <tbody>
                <input type="hidden" name="wpuf_subscription" id="wpuf_subscription_editor" value="<?php echo wp_create_nonce( 'wpuf_subscription_editor' ); ?>" />
                <tr>
                    <th><label><?php _e( 'Pack Description', 'wpuf' ); ?></label></th>
                    <td>
                        <?php wp_editor( $sub_meta['post_content'], 'post_content', array('editor_height' => 100, 'quicktags' => false, 'media_buttons' => false) ); ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="wpuf-billing-amount">
                        <span class="wpuf-biling-amount wpuf-subcription-expire" style="display: <?php echo $hidden_expire; ?>;"><?php _e( 'Billing amount:', 'wpuf' ); ?></span>
                        <span class="wpuf-billing-cycle wpuf-recurring-child" style="display: <?php echo $hidden_recurring_class; ?>;"><?php _e( 'Billing amount each cycle:', 'wpuf' ); ?></span></label></th>
                    <td>
                        <?php echo wpuf_get_option( 'currency_symbol', 'wpuf_payment', '$' ); ?><input type="text" size="20" style="" id="wpuf-billing-amount" value="<?php echo esc_attr( $sub_meta['billing_amount'] ); ?>" name="billing_amount" />
                        <div><span class="description"></span></div>
                    </td>
                </tr>

                <tr class="wpuf-subcription-expire" style="display: <?php echo $hidden_expire; ?>;">
                    <th><label for="wpuf-expiration-number"><?php _e( 'Expires In:', 'wpuf' ); ?></label></th>
                    <td>
                        <input type="text" size="20" style="" id="wpuf-expiration-number" value="<?php echo esc_attr( $sub_meta['expiration_number'] ); ?>" name="expiration_number" />

                        <select id="expiration-period" name="expiration_period">
                            <?php echo $this->option_field( $sub_meta['expiration_period'] ); ?>

                        </select>
                        <div><span class="description"></span></div>
                    </td>
                </tr>

                <tr valign="top">
                    <th><label><?php _e( 'Recurring', 'wpuf' ); ?></label></th>
                    <td>
                        <label for="wpuf-recuring-pay">
                            <input type="checkbox" <?php checked( $sub_meta['recurring_pay'], 'yes' ); ?> size="20" style="" id="wpuf-recuring-pay" value="yes" name="recurring_pay" />
                            <?php _e( 'Enable Recurring Payment', 'wpuf' ); ?>
                        </label>
                    </td>
                </tr>

                <tr valign="top" class="wpuf-recurring-child" style="display: <?php echo $hidden_recurring_class; ?>;">
                    <th><label for="wpuf-billing-cycle-number"><?php _e( 'Billing cycle:', 'wpuf' ); ?></label></th>
                    <td>
                        <select id="wpuf-billing-cycle-number" name="billing_cycle_number">
                            <?php echo $this->lenght_type_option( $sub_meta['billing_cycle_number'] ); ?>
                        </select>

                        <select id="cycle_period" name="cycle_period">
                            <?php echo $this->option_field( $sub_meta['cycle_period'] ); ?>
                        </select>
                        <div><span class="description"></span></div>
                    </td>
                </tr>

                <tr valign="top" class="wpuf-recurring-child" style="display: <?php echo $hidden_recurring_class; ?>;">
                    <th><label for="wpuf-billing-limit"><?php _e( 'Billing cycle stop', 'wpuf' ); ?></label></td>
                    <td>
                        <select id="wpuf-billing-limit" name="billing_limit">
                            <option value=""><?php _e( 'Never', 'wpuf' ); ?></option>
                            <?php echo $this->lenght_type_option( $sub_meta['billing_limit'] ); ?>
                        </select>
                        <div><span class="description"><?php _e( 'After how many cycles should billing stop?', 'wpuf' ); ?></span></div>
                    </td>
                </tr>

                <tr valign="top" class="wpuf-recurring-child" style="display: <?php echo $hidden_recurring_class; ?>;">
                    <th><label for="wpuf-trial-status"><?php _e( 'Trial', 'wpuf' ); ?></label></th>
                    <td>
                        <label for="wpuf-trial-status">
                            <input type="checkbox" size="20" style="" id="wpuf-trial-status" <?php checked( $sub_meta['trial_status'], 'yes' ); ?> value="yes" name="trial_status" />
                            <?php _e( 'Enable trial period', 'wpuf' ); ?>
                        </label>
                    </td>
                </tr>

                <tr class="wpuf-trial-child" style="display: <?php echo $hidden_trial_class; ?>;">
                    <th><label for="wpuf-trial-cost"><?php _e( 'Trial amount', 'wpuf' ); ?></label></th>
                    <td>
                        <?php echo wpuf_get_option( 'currency_symbol', 'wpuf_payment', '$' ); ?><input type="text" size="20" class="small-text" id="wpuf-trial-cost" value="<?php echo esc_attr( $sub_meta['trial_cost'] ); ?>" name="trial_cost" />
                        <span class="description"><?php _e( 'Amount to bill for the trial period', 'wpuf' ); ?></span>
                    </td>
                </tr>

                <tr class="wpuf-trial-child" style="display: <?php echo $hidden_trial_class; ?>;">
                    <th><label for="wpuf-trial-duration"><?php _e( 'Trial period', 'wpuf' ); ?></label></th>
                    <td>
                        <select id="wpuf-trial-duration" name="trial_duration">
                            <?php echo $this->lenght_type_option( $sub_meta['trial_duration'] ); ?>
                        </select>
                        <select id="trial-duration-type" name="trial_duration_type">
                            <?php echo $this->option_field( $sub_meta['trial_duration_type'] ); ?>
                        </select>
                        <span class="description"><?php _e( 'Define the trial period', 'wpuf' ); ?></span>
                    </td>
                </tr>

                <?php echo $this->get_post_types( $sub_meta['post_type_name'] ); ?>

            </tbody>
        </table>

        <?php
    }

    function option_field( $selected ) {
        ?>
        <option value="day" <?php selected( $selected, 'day' ); ?> ><?php _e( 'Day(s)', 'wpuf' ); ?></option>
        <option value="week" <?php selected( $selected, 'week' ); ?> ><?php _e( 'Week(s)', 'wpuf' ); ?></option>
        <option value="month" <?php selected( $selected, 'month' ); ?> ><?php _e( 'Month(s)', 'wpur'); ?></option>
        <option value="year" <?php selected( $selected, 'year' ); ?> ><?php _e( 'Year(s)', 'wpuf' ); ?></option>
        <?php
    }

    function packdropdown_without_recurring( $packs, $selected = '' ) {
        $packs = isset( $packs ) ? $packs : array();
        foreach( $packs as $key => $pack ) {
            $recurring = isset( $pack->meta_value['recurring_pay'] ) ? $pack->meta_value['recurring_pay'] : '';
            if( $recurring == 'yes' ) {
                continue;
            }
            ?>
            <option value="<?php echo $pack->ID; ?>" <?php selected( $selected, $pack->ID ); ?>><?php echo $pack->post_title; ?></option>
            <?php
        }
    }

    /**
     * Adds the postlock form in users profile
     *
     * @param object $profileuser
     */
    function profile_subscription_details( $profileuser ) {

        if ( ! current_user_can( 'edit_users' ) ) {
            return;
        }

        if ( wpuf_get_option( 'charge_posting', 'wpuf_payment' ) != 'yes' ) {
            return;
        }
        $userdata = get_userdata( $profileuser->ID ); //wp 3.3 fix

        $packs = WPUF_Subscription::init()->get_subscriptions();
        $user_sub = WPUF_Subscription::get_user_pack( $userdata->ID );
        $pack_id = isset( $user_sub['pack_id'] ) ? $user_sub['pack_id'] : '';

        ?>
        <div class="wpuf-user-subscription">
            <h3><?php _e( 'WPUF Subscription', 'wpuf' ); ?></h3>
            <a class="btn button-primary wpuf-assing-pack-btn wpuf-add-pack" href="#"><?php _e( 'Assign Package', 'wpuf' ); ?></a>
            <a class="btn button-primary wpuf-assing-pack-btn wpuf-cancel-pack" style="display:none;" href="#"><?php _e( 'Show Package', 'wpuf' ); ?></a>
            <table class="form-table wpuf-pack-dropdown" disabled="disabled" style="display: none;">
                <tr>
                    <th><label for="wpuf_sub_pack"><?php _e( 'Pack:', 'wpuf' ); ?> </label></th>
                    <td>
                        <select name="pack_id" id="wpuf_sub_pack">
                        <option value="-1"><?php _e( '--Select--', 'wpuf' ); ?></option>
                        <?php $this->packdropdown_without_recurring( $packs, $pack_id );//WPUF_Subscription::init()->packdropdown( $packs, $selected = '' ); ?>
                        </select>
                    </td>
                </tr>

            </table>
            <?php


            if ( !isset( $user_sub['pack_id'] ) ) {
                return;
            }

            $pack = WPUF_Subscription::get_subscription( $user_sub['pack_id'] );

            $details_meta = WPUF_Subscription::init()->get_details_meta_value();

            $billing_amount = ( intval( $pack->meta_value['billing_amount'] ) > 0 ) ? $details_meta['symbol'] . $pack->meta_value['billing_amount'] : __( 'Free', 'wpuf' );
            if ( $billing_amount && $pack->meta_value['recurring_pay'] == 'yes' ) {
                $recurring_des = sprintf( 'For each %s %s', $pack->meta_value['billing_cycle_number'], $pack->meta_value['cycle_period'], $pack->meta_value['trial_duration_type'] );
                $recurring_des .= !empty( $pack->meta_value['billing_limit'] ) ? sprintf( ', for %s installments', $pack->meta_value['billing_limit'] ) : '';
                $recurring_des = $recurring_des;
            } else {
                $recurring_des = '';
            }

            ?>

            <div class="wpuf-user-sub-info">
                <h3><?php _e( 'Subscription Details', 'wpuf' ); ?></h3>
                <div class="wpuf-text">
                    <div><strong><?php _e( 'Subcription Name: ','wpuf' ); ?></strong><?php echo $pack->post_title; ?></div>
                    <div>
                        <strong><?php _e( 'Package billing details: '); ?></strong>
                        <div class="wpuf-pricing-wrap">
                            <div class="wpuf-sub-amount">
                                <?php echo $billing_amount; ?>
                                <?php echo $recurring_des; ?>

                            </div>
                        </div>
                    </div>

                    <strong><?php _e( 'Remaining post: ', 'wpuf'); ?></strong>
                    <table class="form-table">

                        <?php

                        foreach ($user_sub['posts'] as $key => $value) {
                            ?>
                             <tr>
                                 <th><label><?php echo $key; ?></label></th>
                                 <td><input type="text" value="<?php echo $value; ?>" name="<?php echo $key; ?>" ></td>
                             </tr>
                            <?php
                        }
                        ?>



                    <?php
                    if ( $user_sub['recurring'] != 'yes' ) {
                        if ( !empty( $user_sub['expire'] ) ) {

                            $expire =  ( $user_sub['expire'] == 'unlimited' ) ? ucfirst( 'unlimited' ) : wpuf_date2mysql( $user_sub['expire'] );

                            ?>
                            <tr>
                                <th><label><?php echo _e('Expire date:'); ?></label></th>
                                <td><input type="text" class="wpuf-date-picker" name="expire" value="<?php echo wpuf_get_date( $expire ); ?>"></td>
                            </tr>
                            <?php
                        }

                    } ?>
                    </table>
                </div>
            </div>
        </div>
            <?php

    }

    function lenght_type_option( $selected ) {

        for ($i = 1; $i <= 30; $i++) {
            ?>
                <option value="<?php echo $i; ?>" <?php selected( $i, $selected ); ?>><?php echo $i; ?></option>
            <?php
        }

    }
}

//$subscription = new WPUF_Admin_Subscription();
