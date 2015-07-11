<?php

/**
 * WPUF Form builder template
 *
 * @package WP User Frontend
 * @author Tareq Hasan <tareq@wedevs.com>
 */
class WPUF_Admin_Template {

    static $input_name = 'wpuf_input';
    static $cond_name = 'wpuf_cond';

    /**
     * Legend of a form item
     *
     * @param string $title
     * @param array $values
     */
    public static function legend( $title = 'Field Name', $values = array(), $field_id = 0 ) {

        $field_label = $values ? ': <strong>' . $values['label'] . '</strong>' : '';
        $id          = isset( $values['id'] ) ? $values['id'] : '';
        ?>
        <div class="wpuf-legend" title="<?php _e( 'Click and Drag to rearrange', 'wpuf' ); ?>">
            <input type="hidden" value="<?php echo $id; ?>" name="wpuf_input[<?php echo $field_id; ?>][id]">
            <div class="wpuf-label"><?php echo $title . $field_label; ?></div>
            <div class="wpuf-actions">
                <a href="#" class="wpuf-remove"><?php _e( 'Remove', 'wpuf' ); ?></a>
                <a href="#" class="wpuf-toggle"><?php _e( 'Toggle', 'wpuf' ); ?></a>
            </div>
        </div> <!-- .wpuf-legend -->
        <?php
    }

    /**
     * Common Fields for a input field
     *
     * Contains required, label, meta_key, help text, css class name
     *
     * @param int $id field order
     * @param mixed $field_name_value
     * @param bool $custom_field if it a custom field or not
     * @param array $values saved value
     */
    public static function common( $id, $field_name_value = '', $custom_field = true, $values = array() ) {
        $tpl                 = '%s[%d][%s]';
        $required_name       = sprintf( $tpl, self::$input_name, $id, 'required' );
        $field_name          = sprintf( $tpl, self::$input_name, $id, 'name' );
        $label_name          = sprintf( $tpl, self::$input_name, $id, 'label' );
        $is_meta_name        = sprintf( $tpl, self::$input_name, $id, 'is_meta' );
        $help_name           = sprintf( $tpl, self::$input_name, $id, 'help' );
        $css_name            = sprintf( $tpl, self::$input_name, $id, 'css' );

        // $field_name_value = $field_name_value ?
        $required            = $values ? esc_attr( $values['required'] ) : 'yes';
        $label_value         = $values ? esc_attr( $values['label'] ) : '';
        $help_value          = $values ? stripslashes( $values['help'] ) : '';
        $css_value           = $values ? esc_attr( $values['css'] ) : '';

        if ( $custom_field && $values ) {
            $field_name_value = $values['name'];
        }

        // var_dump($values);
        // var_dump($required, $label_value, $help_value);
        ?>
        <div class="wpuf-form-rows required-field">
            <label><?php _e( 'Required', 'wpuf' ); ?></label>

            <div class="wpuf-form-sub-fields">
                <label><input type="radio" name="<?php echo $required_name; ?>" value="yes"<?php checked( $required, 'yes' ); ?>> <?php _e( 'Yes', 'wpuf' ); ?> </label>
                <label><input type="radio" name="<?php echo $required_name; ?>" value="no"<?php checked( $required, 'no' ); ?>> <?php _e( 'No', 'wpuf' ); ?> </label>
            </div>
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Field Label', 'wpuf' ); ?></label>
            <input type="text" data-type="label" name="<?php echo $label_name; ?>" value="<?php echo $label_value; ?>" class="smallipopInput" title="<?php _e( 'Enter a title of this field', 'wpuf' ); ?>">
        </div> <!-- .wpuf-form-rows -->

        <?php if ( $custom_field ) { ?>
            <div class="wpuf-form-rows">
                <label><?php _e( 'Meta Key', 'wpuf' ); ?></label>
                <input type="text" data-type="name" name="<?php echo $field_name; ?>" value="<?php echo $field_name_value; ?>" class="smallipopInput" title="<?php _e( 'Name of the meta key this field will save to', 'wpuf' ); ?>">
                <input type="hidden" name="<?php echo $is_meta_name; ?>" value="yes">
            </div> <!-- .wpuf-form-rows -->
        <?php } else { ?>

            <input type="hidden" data-type="name" name="<?php echo $field_name; ?>" value="<?php echo $field_name_value; ?>">
            <input type="hidden" name="<?php echo $is_meta_name; ?>" value="no">

        <?php } ?>

        <div class="wpuf-form-rows">
            <label><?php _e( 'Help text', 'wpuf' ); ?></label>
            <textarea name="<?php echo $help_name; ?>" class="smallipopInput" title="<?php _e( 'Give the user some information about this field', 'wpuf' ); ?>"><?php echo $help_value; ?></textarea>
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'CSS Class Name', 'wpuf' ); ?></label>
            <input type="text" name="<?php echo $css_name; ?>" value="<?php echo $css_value; ?>" class="smallipopInput" title="<?php _e( 'Add a CSS class name for this field', 'wpuf' ); ?>">
        </div> <!-- .wpuf-form-rows -->

        <?php
    }

    /**
     * Common fields for a text area
     *
     * @param int $id
     * @param array $values
     */
    public static function common_text( $id, $values = array() ) {
        $tpl               = '%s[%d][%s]';
        $placeholder_name  = sprintf( $tpl, self::$input_name, $id, 'placeholder' );
        $default_name      = sprintf( $tpl, self::$input_name, $id, 'default' );
        $size_name         = sprintf( $tpl, self::$input_name, $id, 'size' );

        $placeholder_value = $values ? esc_attr( $values['placeholder'] ) : '';
        $default_value     = $values ? esc_attr( $values['default'] ) : '';
        $size_value        = $values ? esc_attr( $values['size'] ) : '40';

        // var_dump($values);
        ?>
        <div class="wpuf-form-rows">
            <label><?php _e( 'Placeholder text', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $placeholder_name; ?>" title="<?php esc_attr_e( 'Text for HTML5 placeholder attribute', 'wpuf' ); ?>" value="<?php echo $placeholder_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Default value', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $default_name; ?>" title="<?php esc_attr_e( 'The default value this field will have', 'wpuf' ); ?>" value="<?php echo $default_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Size', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $size_name; ?>" title="<?php esc_attr_e( 'Size of this input field', 'wpuf' ); ?>" value="<?php echo $size_value; ?>" />
        </div> <!-- .wpuf-form-rows -->
        <?php
    }

    /**
     * Common fields for a textarea
     *
     * @param int $id
     * @param array $values
     */
    public static function common_textarea( $id, $values = array() ) {
        $tpl = '%s[%d][%s]';
        $rows_name         = sprintf( $tpl, self::$input_name, $id, 'rows' );
        $cols_name         = sprintf( $tpl, self::$input_name, $id, 'cols' );
        $rich_name         = sprintf( $tpl, self::$input_name, $id, 'rich' );
        $placeholder_name  = sprintf( $tpl, self::$input_name, $id, 'placeholder' );
        $default_name      = sprintf( $tpl, self::$input_name, $id, 'default' );

        $rows_value        = $values ? esc_attr( $values['rows'] ) : '5';
        $cols_value        = $values ? esc_attr( $values['cols'] ) : '25';
        $rich_value        = $values ? esc_attr( $values['rich'] ) : 'no';
        $placeholder_value = $values ? esc_attr( $values['placeholder'] ) : '';
        $default_value     = $values ? esc_attr( $values['default'] ) : '';

        // var_dump($values);
        ?>
        <div class="wpuf-form-rows">
            <label><?php _e( 'Rows', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $rows_name; ?>" title="Number of rows in textarea" value="<?php echo $rows_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Columns', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $cols_name; ?>" title="Number of columns in textarea" value="<?php echo $cols_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Placeholder text', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $placeholder_name; ?>" title="text for HTML5 placeholder attribute" value="<?php echo $placeholder_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Default value', 'wpuf' ); ?></label>
            <input type="text" class="smallipopInput" name="<?php echo $default_name; ?>" title="the default value this field will have" value="<?php echo $default_value; ?>" />
        </div> <!-- .wpuf-form-rows -->

        <div class="wpuf-form-rows">
            <label><?php _e( 'Textarea', 'wpuf' ); ?></label>

            <div class="wpuf-form-sub-fields">
                <label><input type="radio" name="<?php echo $rich_name; ?>" value="no"<?php checked( $rich_value, 'no' ); ?>> <?php _e( 'Normal', 'wpuf' ); ?></label>
                <label><input type="radio" name="<?php echo $rich_name; ?>" value="yes"<?php checked( $rich_value, 'yes' ); ?>> <?php _e( 'Rich textarea', 'wpuf' ); ?></label>
                <label><input type="radio" name="<?php echo $rich_name; ?>" value="teeny"<?php checked( $rich_value, 'teeny' ); ?>> <?php _e( 'Teeny Rich textarea', 'wpuf' ); ?></label>
            </div>
        </div> <!-- .wpuf-form-rows -->
        <?php
    }

    /**
     * Hidden field helper function
     *
     * @param string $name
     * @param string $value
     */
    public static function hidden_field( $name, $value = '' ) {
        printf( '<input type="hidden" name="%s" value="%s" />', self::$input_name . $name, $value );
    }

    /**
     * Displays a radio custom field
     *
     * @param int $field_id
     * @param string $name
     * @param array $values
     */
    public static function radio_fields( $field_id, $name, $values = array() ) {

        $selected_name    = sprintf( '%s[%d][selected]', self::$input_name, $field_id );
        $input_name       = sprintf( '%s[%d][%s]', self::$input_name, $field_id, $name );
        $input_value_name = sprintf( '%s[%d][%s]', self::$input_name, $field_id, $name.'_values' );

        $selected_value   = ( $values && isset( $values['selected'] ) ) ? $values['selected'] : '';
        ?>

        <label for="wpuf-<?php echo $name.'_'.$field_id; ?>" class="wpuf-show-field-value">
            <input type="checkbox" class="wpuf-value-handelar" id="wpuf-<?php echo $name . '_' . $field_id; ?>"><?php _e( 'Show values', 'wpuf' ); ?>
        </label>

        <div class="wpuf-option-label-value"><span><?php _e( 'Label', 'wpuf' ); ?></span><span class="wpuf-option-value" style="display: none;"><?php _e( 'Value', 'wpuf' ); ?></span></div>
        <?php
        if ( $values && $values['options'] > 0 ) {
            foreach ($values['options'] as $key => $value) {
                ?>
                <div class="wpuf-clone-field">
                    <input type="radio" name="<?php echo $selected_name ?>" value="<?php echo $value; ?>" <?php checked( $selected_value, $value ); ?>>
                    <input type="text" data-type="option" name="<?php echo $input_name; ?>[]" value="<?php echo $value; ?>">
                    <input type="text" data-type="option_value" name="<?php echo $input_value_name; ?>[]" value="<?php echo $key; ?>" style="display:none;">

                    <?php self::remove_button(); ?>
                </div>
                <?php
            }

        } else {
            ?>
            <div class="wpuf-clone-field">
                <input type="radio" name="<?php echo $selected_name ?>">
                <input type="text" data-type="option" name="<?php echo $input_name; ?>[]" value="">
                <input type="text" data-type="option_value" name="<?php echo $input_value_name; ?>[]" value="" style="display:none;">

                <?php self::remove_button(); ?>
            </div>
            <?php
        }
    }

    public static function conditional_field( $field_id, $con_fields = array() ) {
        global $form_inputs;

        $con_fields_value = isset( $con_fields['wpuf_cond'] ) ? $con_fields['wpuf_cond'] : array();
        $tpl              = '%s[%d][%s]';
        $enable_name      = sprintf( $tpl, self::$cond_name, $field_id, 'condition_status' );
        $field_name       = sprintf( '%s[%d][cond_field][]', self::$cond_name, $field_id );
        $operator_name    = sprintf( '%s[%d][cond_operator][]', self::$cond_name, $field_id );
        $option_name      = sprintf( '%s[%d][cond_option][]', self::$cond_name, $field_id );
        $logic_name       = sprintf( '%s[%d][cond_logic]', self::$cond_name, $field_id );

        // $enable_value = 'yes';
        $class = '';

        // var_dump($field_id, $con_fields);

        $enable_value = isset( $con_fields_value['condition_status'] ) ? $con_fields_value['condition_status'] : 'no';
        $logic_value  = isset( $con_fields_value['cond_logic'] ) ? $con_fields_value['cond_logic'] : 'all';
        $class        = ($enable_value == 'yes') ? '' : ' wpuf-hide';
        ?>
        <div class="wpuf-form-rows">
            <label><?php _e( 'Conditional Logic', 'wpuf' ); ?></label>

            <div class="wpuf-form-sub-fields">
                <label><input type="radio" name="<?php echo $enable_name; ?>" class="wpuf-conditional-enable" value="yes"<?php checked( $enable_value, 'yes' ); ?>> <?php _e( 'Yes', 'wpuf' ); ?></label>
                <label><input type="radio" name="<?php echo $enable_name; ?>" class="wpuf-conditional-enable" value="no"<?php checked( $enable_value, 'no' ); ?>> <?php _e( 'No', 'wpuf' ); ?></label>

                <div class="conditional-rules-wrap<?php echo $class; ?>">
                    <table class="">
                        <?php
                        if ($enable_value == 'yes') {

                            //var_dump( $form_inputs );
                            //$form_fields = get_post_meta( $post->ID, 'wpuf_form', true );

                            $cond_fields = WPUF_Admin_Form::get_conditional_fields( $form_inputs );

                            $field_dropdown = WPUF_Admin_Form::get_conditional_fields_dropdown( $cond_fields['fields'] );

                            foreach ($con_fields_value['cond_field'] as $key => $field) {
                                $cond_fields['options'][$field] = isset( $cond_fields['options'][$field] ) ? $cond_fields['options'][$field] : array();

                                $option_dropdown = WPUF_Admin_Form::get_conditional_option_dropdown( $cond_fields['options'][$field] );

                                ?>
                                <tr>
                                    <td>
                                        <select name="<?php echo $field_name; ?>" class="wpuf-conditional-fields">
                                            <?php echo wpuf_dropdown_helper($field_dropdown, $con_fields_value['cond_field'][$key]); ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="<?php echo $operator_name; ?>" class="">
                                            <option value="=" <?php selected($con_fields_value['cond_operator'][$key], '=') ;?>>is equal to</option>
                                            <option value="!=" <?php selected($con_fields_value['cond_operator'][$key], '!=') ;?>>is not equal to</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="<?php echo $option_name; ?>" class="wpuf-conditional-fields-option">
                                            <?php
                                            if ( array_key_exists( $field, $cond_fields['options'] ) ) {
                                                echo wpuf_dropdown_helper( $option_dropdown, $con_fields_value['cond_option'][$key] );
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <a class="button wpuf-conditional-plus" href="#">+</a>
                                        <a class="button wpuf-conditional-minus" href="#">-</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td>
                                    <select name="<?php echo $field_name; ?>" class="wpuf-conditional-fields">
                                        <option value="">- select -</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="<?php echo $operator_name; ?>" class="">
                                        <option value="=">is equal to</option>
                                        <option value="!=">is not equal to</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="<?php echo $option_name; ?>" class="wpuf-conditional-fields-option">
                                        <option value="">- select -</option>
                                    </select>
                                </td>
                                <td>
                                    <a class="button wpuf-conditional-plus" href="#">+</a>
                                    <a class="button wpuf-conditional-minus" href="#">-</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>


                    <div class="">
                        Show this field when
                        <select name="<?php echo $logic_name; ?>">
                            <option value="all"<?php selected( $logic_value, 'all') ;?>>all</option>
                            <option value="any"<?php selected( $logic_value, 'any') ;?>>any</option>
                        </select>
                        these rules are met
                    </div>
                </div>
            </div>
        </div> <!-- .wpuf-form-rows -->
        <?php
    }

    /**
     * Displays a checkbox custom field
     *
     * @param int $field_id
     * @param string $name
     * @param array $values
     */
    public static function common_checkbox( $field_id, $name, $values = array() ) {

        $selected_name    = sprintf( '%s[%d][selected]', self::$input_name, $field_id );
        $input_name       = sprintf( '%s[%d][%s]', self::$input_name, $field_id, $name );
        $input_value_name = sprintf( '%s[%d][%s]', self::$input_name, $field_id, $name.'_values' );

        $selected_value   = ( $values && isset( $values['selected'] ) ) ? $values['selected'] : array();

        ?>
        <style>
            .wpuf-option-label-value span {
                font-weight: bold;
                margin-left: 5%;
                margin-right: 27%;
            }
        </style>
        <input type="checkbox" class="wpuf-value-handelar" id="<?php echo $name.'_'.$field_id; ?>"><label for="<?php echo $name.'_'.$field_id; ?>"><?php _e('show values', 'wpuf'); ?></label>
        <div class="wpuf-option-label-value"><span><?php _e( 'Label', 'wpuf' ); ?></span><span class="wpuf-option-value" style="display: none;"><?php _e( 'Value', 'wpuf' ); ?></span></div>
        <?php
        if ( $values && $values['options'] > 0 ) {
            foreach ($values['options'] as $key => $value) {
                ?>
                <div class="wpuf-clone-field">

                    <input type="checkbox" name="<?php echo $selected_name ?>[]" value="<?php echo $value; ?>"<?php echo in_array( $value, $selected_value ) ? ' checked="checked"' : ''; ?> />
                    <input type="text" data-type="option" name="<?php echo $input_name; ?>[]" value="<?php echo $value; ?>">
                    <input type="text" data-type="option_value" name="<?php echo $input_value_name; ?>[]" value="<?php echo $key; ?>" style="display:none;">
                    <?php self::remove_button(); ?>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="wpuf-clone-field">
                <input type="checkbox" name="<?php echo $selected_name ?>[]">
                <input type="text" data-type="option" name="<?php echo $input_name; ?>[]" value="">
                <input type="text" data-type="option_value" name="<?php echo $input_value_name; ?>[]" value="" style="display:none;">

                <?php self::remove_button(); ?>
            </div>
            <?php
        }
    }

    /**
     * Add/remove buttons for repeatable fields
     *
     * @return void
     */
    public static function remove_button() {
        $add = plugins_url( 'assets/images/add.png', dirname( __FILE__ ) );
        $remove = plugins_url( 'assets/images/remove.png', dirname( __FILE__ ) );
        ?>
        <img style="cursor:pointer; margin:0 3px;" alt="add another choice" title="add another choice" class="wpuf-clone-field" src="<?php echo $add; ?>">
        <img style="cursor:pointer;" class="wpuf-remove-field" alt="remove this choice" title="remove this choice" src="<?php echo $remove; ?>">
        <?php
    }

    public static function get_buffered( $func, $field_id, $label ) {
        ob_start();

        self::$func( $field_id, $label );

        return ob_get_clean();
    }

    public static function text_field( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field text_field">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'text' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'text_field' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function textarea_field( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field textarea_field">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'textarea' ); ?>
        <?php self::hidden_field( "[$field_id][template]", 'textarea_field' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>
                <?php self::common_textarea( $field_id, $values ); ?>
                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function radio_field( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field radio_field wpuf-conditional">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'radio' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'radio_field' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Options', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields wpuf-options">
                    <?php self::radio_fields( $field_id, 'options', $values ); ?>

                    </div> <!-- .wpuf-form-sub-fields -->
                    <?php self::conditional_field( $field_id, $values ); ?>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function checkbox_field( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field checkbox_field wpuf-conditional">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'checkbox' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'checkbox_field' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Options', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields wpuf-options">
                    <?php self::common_checkbox( $field_id, 'options', $values ); ?>

                    </div> <!-- .wpuf-form-sub-fields -->
                    <?php self::conditional_field( $field_id, $values ); ?>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function dropdown_field( $field_id, $label, $values = array() ) {
        $first_name = sprintf( '%s[%d][first]', self::$input_name, $field_id );
        $first_value = $values ? $values['first'] : ' - select -';
        $help = esc_attr( __( 'First element of the select dropdown. Leave this empty if you don\'t want to show this field', 'wpuf' ) );
        ?>
        <li class="custom-field dropdown_field wpuf-conditional">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'select' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'dropdown_field' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Select Text', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $first_name; ?>" value="<?php echo $first_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Options', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields wpuf-options">
                        <?php self::radio_fields( $field_id, 'options', $values ); ?>
                    </div> <!-- .wpuf-form-sub-fields -->

                    <?php self::conditional_field( $field_id, $values ); ?>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function multiple_select( $field_id, $label, $values = array() ) {
        $first_name = sprintf( '%s[%d][first]', self::$input_name, $field_id );
        $first_value = $values ? $values['first'] : ' - select -';
        $help = esc_attr( __( 'First element of the select dropdown. Leave this empty if you don\'t want to show this field', 'wpuf' ) );
        ?>
        <li class="custom-field multiple_select">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'multiselect' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'multiple_select' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Select Text', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $first_name; ?>" value="<?php echo $first_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Options', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields wpuf-options">
                        <?php self::radio_fields( $field_id, 'options', $values ); ?>
                    </div> <!-- .wpuf-form-sub-fields -->

                    <?php self::conditional_field( $field_id, $values ); ?>
                </div> <!-- .wpuf-form-rows -->
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function image_upload( $field_id, $label, $values = array() ) {
        $max_size_name   = sprintf( '%s[%d][max_size]', self::$input_name, $field_id );
        $max_files_name  = sprintf( '%s[%d][count]', self::$input_name, $field_id );

        $max_size_value  = $values ? $values['max_size'] : '1024';
        $max_files_value = $values ? $values['count'] : '1';

        $help            = esc_attr( __( 'Enter maximum upload size limit in KB', 'wpuf' ) );
        $count           = esc_attr( __( 'Number of images can be uploaded', 'wpuf' ) );
        ?>
        <li class="custom-field image_upload">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'image_upload' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'image_upload' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. file size', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_size_name; ?>" value="<?php echo $max_size_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. files', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_files_name; ?>" value="<?php echo $max_files_value; ?>" title="<?php echo $count; ?>">
                </div> <!-- .wpuf-form-rows -->

                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function file_upload( $field_id, $label, $values = array() ) {
        $max_size_name    = sprintf( '%s[%d][max_size]', self::$input_name, $field_id );
        $max_files_name   = sprintf( '%s[%d][count]', self::$input_name, $field_id );
        $extensions_name  = sprintf( '%s[%d][extension][]', self::$input_name, $field_id );

        $max_size_value   = $values ? $values['max_size'] : '1024';
        $max_files_value  = $values ? $values['count'] : '1';
        $extensions_value = $values ? $values['extension'] : array('images', 'audio', 'video', 'pdf', 'office', 'zip', 'exe', 'csv');

        $extesions        = wpuf_allowed_extensions();

        // var_dump($extesions);

        $help  = esc_attr( __( 'Enter maximum upload size limit in KB', 'wpuf' ) );
        $count = esc_attr( __( 'Number of images can be uploaded', 'wpuf' ) );
        ?>
        <li class="custom-field custom_image">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'file_upload' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'file_upload' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. file size', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_size_name; ?>" value="<?php echo $max_size_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Max. files', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $max_files_name; ?>" value="<?php echo $max_files_value; ?>" title="<?php echo $count; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Allowed Files', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <?php foreach ($extesions as $key => $value) {
                            ?>
                            <label>
                                <input type="checkbox" name="<?php echo $extensions_name; ?>" value="<?php echo $key; ?>"<?php echo in_array( $key, $extensions_value ) ? ' checked="checked"' : ''; ?>>
                            <?php printf( '%s (%s)', $value['label'], str_replace( ',', ', ', $value['ext'] ) ) ?>
                            </label> <br />
                        <?php } ?>
                    </div>
                </div> <!-- .wpuf-form-rows -->
                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function website_url( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field website_url">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'url' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'website_url' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function email_address( $field_id, $label, $values = array() ) {
        ?>
        <li class="custom-field eamil_address">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'email' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'email_address' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>
                <?php self::common_text( $field_id, $values ); ?>
                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function repeat_field( $field_id, $label, $values = array() ) {
        $tpl = '%s[%d][%s]';

        $enable_column_name = sprintf( '%s[%d][multiple]', self::$input_name, $field_id );
        $column_names       = sprintf( '%s[%d][columns]', self::$input_name, $field_id );
        $has_column         = ( $values && isset( $values['multiple'] ) ) ? true : false;

        $placeholder_name   = sprintf( $tpl, self::$input_name, $field_id, 'placeholder' );
        $default_name       = sprintf( $tpl, self::$input_name, $field_id, 'default' );
        $size_name          = sprintf( $tpl, self::$input_name, $field_id, 'size' );

        $placeholder_value  = $values ? esc_attr( $values['placeholder'] ) : '';
        $default_value      = $values ? esc_attr( $values['default'] ) : '';
        $size_value         = $values ? esc_attr( $values['size'] ) : '40';
        ?>
        <li class="custom-field custom_repeater">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'repeat' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'repeat_field' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Multiple Column', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <label><input type="checkbox" class="multicolumn" name="<?php echo $enable_column_name ?>"<?php echo $has_column ? ' checked="checked"' : ''; ?> value="true"> Enable Multi Column</label>
                    </div>
                </div>

                <div class="wpuf-form-rows<?php echo $has_column ? ' wpuf-hide' : ''; ?>">
                    <label><?php _e( 'Placeholder text', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $placeholder_name; ?>" title="text for HTML5 placeholder attribute" value="<?php echo $placeholder_value; ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows<?php echo $has_column ? ' wpuf-hide' : ''; ?>">
                    <label><?php _e( 'Default value', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $default_name; ?>" title="the default value this field will have" value="<?php echo $default_value; ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Size', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $size_name; ?>" title="Size of this input field" value="<?php echo $size_value; ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows column-names<?php echo $has_column ? '' : ' wpuf-hide'; ?>">
                    <label><?php _e( 'Columns', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <?php
                        if ( $values && $values['columns'] > 0 ) {
                            foreach ($values['columns'] as $key => $value) {
                                ?>
                                <div>
                                    <input type="text" name="<?php echo $column_names; ?>[]" value="<?php echo $value; ?>">

                                    <?php self::remove_button(); ?>
                                </div>
                                <?php
                            }
                        } else {
                            ?>
                            <div>
                                <input type="text" name="<?php echo $column_names; ?>[]" value="">

                                <?php self::remove_button(); ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div> <!-- .wpuf-form-rows -->
                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_html( $field_id, $label, $values = array() ) {
        $title_name  = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $html_name   = sprintf( '%s[%d][html]', self::$input_name, $field_id );
        $title_value = $values ? esc_attr( $values['label'] ) : '';
        $html_value  = $values ? esc_attr( $values['html'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'html' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_html' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Title', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" title="Title of the section" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'HTML Codes', 'wpuf' ); ?></label>
                    <textarea class="smallipopInput" title="Paste your HTML codes, WordPress shortcodes will also work here" name="<?php echo $html_name; ?>" rows="10"><?php echo esc_html( $html_value ); ?></textarea>
                </div>

                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function custom_hidden_field( $field_id, $label, $values = array() ) {
        $meta_name    = sprintf( '%s[%d][name]', self::$input_name, $field_id );
        $value_name   = sprintf( '%s[%d][meta_value]', self::$input_name, $field_id );
        $is_meta_name = sprintf( '%s[%d][is_meta]', self::$input_name, $field_id );
        $label_name   = sprintf( '%s[%d][label]', self::$input_name, $field_id );

        $meta_value   = $values ? esc_attr( $values['name'] ) : '';
        $value_value  = $values ? esc_attr( $values['meta_value'] ) : '';
        ?>
        <li class="custom-field custom_hidden_field">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'hidden' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'custom_hidden_field' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Meta Key', 'wpuf' ); ?></label>
                    <input type="text" name="<?php echo $meta_name; ?>" value="<?php echo $meta_value; ?>" class="smallipopInput" title="<?php _e( 'Name of the meta key this field will save to', 'wpuf' ); ?>">
                    <input type="hidden" name="<?php echo $is_meta_name; ?>" value="yes">
                    <input type="hidden" name="<?php echo $label_name; ?>" value="">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Meta Value', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" title="<?php esc_attr_e( 'Enter the meta value', 'wpuf' ); ?>" name="<?php echo $value_name; ?>" value="<?php echo $value_value; ?>">
                </div>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function section_break( $field_id, $label, $values = array() ) {
        $title_name        = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $description_name  = sprintf( '%s[%d][description]', self::$input_name, $field_id );

        $title_value       = $values ? esc_attr( $values['label'] ) : '';
        $description_value = $values ? esc_attr( $values['description'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'section_break' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'section_break' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Title', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" title="Title of the section" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Description', 'wpuf' ); ?></label>
                    <textarea class="smallipopInput" title="Some details text about the section" name="<?php echo $description_name; ?>" rows="3"><?php echo esc_html( $description_value ); ?></textarea>
                </div> <!-- .wpuf-form-rows -->

                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function recaptcha( $field_id, $label, $values = array() ) {
        $title_name  = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $html_name   = sprintf( '%s[%d][html]', self::$input_name, $field_id );

        $title_value = $values ? esc_attr( $values['label'] ) : '';
        $html_value  = isset( $values['html'] ) ? esc_attr( $values['html'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'recaptcha' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'recaptcha' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Title', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <input type="text" class="smallipopInput" title="Title of the section" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />

                        <div class="description" style="margin-top: 8px;">
                            <?php printf( __( "Insert your public key and private key in <a href='%s'>plugin settings</a>. <a href='https://www.google.com/recaptcha/' target='_blank'>Register</a> first if you don't have any keys." ), admin_url( 'admin.php?page=wpuf-settings' ) ); ?>
                        </div>
                    </div> <!-- .wpuf-form-rows -->
                </div>

                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function really_simple_captcha( $field_id, $label, $values = array() ) {
        $title_name  = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $html_name   = sprintf( '%s[%d][html]', self::$input_name, $field_id );

        $title_value = $values ? esc_attr( $values['label'] ) : '';
        $html_value  = isset( $values['html'] ) ? esc_attr( $values['html'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'really_simple_captcha' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'really_simple_captcha' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Title', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <input type="text" class="smallipopInput" title="Title of the section" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />

                        <div class="description" style="margin-top: 8px;">
                            <?php printf( __( "Depends on <a href='http://wordpress.org/extend/plugins/really-simple-captcha/' target='_blank'>Really Simple Captcha</a> Plugin. Install it first." ) ); ?>
                        </div>
                    </div> <!-- .wpuf-form-rows -->
                </div>

                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function action_hook( $field_id, $label, $values = array() ) {
        $title_name  = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $title_value = $values ? esc_attr( $values['label'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'action_hook' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'action_hook' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Hook Name', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <input type="text" class="smallipopInput" title="<?php _e( 'Name of the hook', 'wpuf' ); ?>" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />

                        <div class="description" style="margin-top: 8px;">
                            <?php _e( "An option for developers to add dynamic elements they want. It provides the chance to add whatever input type you want to add in this form.", 'wpuf' ); ?>
                            <?php _e( 'This way, you can bind your own functions to render the form to this action hook. You\'ll be given 3 parameters to play with: $form_id, $post_id, $form_settings.', 'wpuf' ); ?>
                            <pre>
        add_action('HOOK_NAME', 'your_function_name', 10, 3 );
        function your_function_name( $form_id, $post_id, $form_settings ) {
            // do what ever you want
        }
                            </pre>
                        </div>
                    </div> <!-- .wpuf-form-rows -->
                </div>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function date_field( $field_id, $label, $values = array() ) {
        $format_name  = sprintf( '%s[%d][format]', self::$input_name, $field_id );
        $time_name    = sprintf( '%s[%d][time]', self::$input_name, $field_id );

        $format_value = $values ? $values['format'] : 'dd/mm/yy';
        $time_value   = $values ? $values['time'] : 'no';

        $help         = esc_attr( __( 'The date format', 'wpuf' ) );
        ?>
        <li class="custom-field custom_image">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'date' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'date_field' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Date Format', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $format_name; ?>" value="<?php echo $format_value; ?>" title="<?php echo $help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Time', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <label>
                            <?php self::hidden_field( "[$field_id][time]", 'no' ); ?>
                            <input type="checkbox" name="<?php echo $time_name ?>" value="yes"<?php checked( $time_value, 'yes' ); ?> />
                            <?php _e( 'Enable time input', 'wpuf' ); ?>
                        </label>
                    </div>
                </div> <!-- .wpuf-form-rows -->

                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function google_map( $field_id, $label, $values = array() ) {
        $zoom_name         = sprintf( '%s[%d][zoom]', self::$input_name, $field_id );
        $address_name      = sprintf( '%s[%d][address]', self::$input_name, $field_id );
        $default_pos_name  = sprintf( '%s[%d][default_pos]', self::$input_name, $field_id );
        $show_lat_name     = sprintf( '%s[%d][show_lat]', self::$input_name, $field_id );

        $zoom_value        = $values ? $values['zoom'] : '12';
        $address_value     = $values ? $values['address'] : 'yes';
        $show_lat_value    = $values ? $values['show_lat'] : 'no';
        $default_pos_value = $values ? $values['default_pos'] : '40.7143528,-74.0059731';

        $zoom_help         = esc_attr( __( 'Set the map zoom level', 'wpuf' ) );
        $pos_help          = esc_attr( __( 'Enter default latitude and longitude to center the map', 'wpuf' ) );
        ?>
        <li class="custom-field custom_image">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'map' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'google_map' ); ?>

            <div class="wpuf-form-holder">
                <?php self::common( $field_id, '', true, $values ); ?>

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Zoom Level', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $zoom_name; ?>" value="<?php echo $zoom_value; ?>" title="<?php echo $zoom_help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Default Co-ordinate', 'wpuf' ); ?></label>
                    <input type="text" class="smallipopInput" name="<?php echo $default_pos_name; ?>" value="<?php echo $default_pos_value; ?>" title="<?php echo $pos_help; ?>">
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Address Button', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <label>
                            <?php self::hidden_field( "[$field_id][address]", 'no' ); ?>
                            <input type="checkbox" name="<?php echo $address_name ?>" value="yes"<?php checked( $address_value, 'yes' ); ?> />
                            <?php _e( 'Show address find button', 'wpuf' ); ?>
                        </label>
                    </div>
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Show Latitude/Longitude', 'wpuf' ); ?></label>

                    <div class="wpuf-form-sub-fields">
                        <label>
                            <?php self::hidden_field( "[$field_id][show_lat]", 'no' ); ?>
                            <input type="checkbox" name="<?php echo $show_lat_name ?>" value="yes"<?php checked( $show_lat_value, 'yes' ); ?> />
                            <?php _e( 'Show latitude and longitude input box value', 'wpuf' ); ?>
                        </label>
                    </div>
                </div> <!-- .wpuf-form-rows -->

                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

    public static function toc( $field_id, $label, $values = array() ) {
        $title_name        = sprintf( '%s[%d][label]', self::$input_name, $field_id );
        $description_name  = sprintf( '%s[%d][description]', self::$input_name, $field_id );

        $title_value       = $values ? esc_attr( $values['label'] ) : '';
        $description_value = $values ? esc_attr( $values['description'] ) : '';
        ?>
        <li class="custom-field custom_html">
            <?php self::legend( $label, $values, $field_id ); ?>
            <?php self::hidden_field( "[$field_id][input_type]", 'toc' ); ?>
            <?php self::hidden_field( "[$field_id][template]", 'toc' ); ?>

            <div class="wpuf-form-holder">
                <div class="wpuf-form-rows">
                    <label><?php _e( 'Label', 'wpuf' ); ?></label>
                    <input type="text" name="<?php echo $title_name; ?>" value="<?php echo esc_attr( $title_value ); ?>" />
                </div> <!-- .wpuf-form-rows -->

                <div class="wpuf-form-rows">
                    <label><?php _e( 'Terms & Conditions', 'wpuf' ); ?></label>
                    <textarea class="smallipopInput" title="<?php _e( 'Insert terms and condtions here.', 'wpuf' ); ?>" name="<?php echo $description_name; ?>" rows="3"><?php echo esc_html( $description_value ); ?></textarea>
                </div> <!-- .wpuf-form-rows -->
                <?php self::conditional_field( $field_id, $values ); ?>
            </div> <!-- .wpuf-form-holder -->
        </li>
        <?php
    }

}