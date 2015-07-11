(function($) {

	var subscription = {

		init: function() {

			$('input#wpuf-recuring-pay').on('click', this.showSubscriptionRecurring );

			$('input#wpuf-trial-status').on('click', this.showSubscriptionPack );

            $('.wpuf-coupon-info-wrap').on( 'click','a.wpuf-apply-coupon', this.couponApply );

            $('.wpuf-coupon-info-wrap').on( 'click','a.wpuf-copon-show', this.couponShow );

            $('.wpuf-coupon-info-wrap').on( 'click','a.wpuf-copon-cancel', this.couponCancel );

            $('.wpuf-assing-pack-btn').on( 'click', this.showPackDropdown );

		},

        showPackDropdown: function(e) {
            e.preventDefault();
            var self = $(this),
                wrap = self.parents('.wpuf-user-subscription'),
                sub_dropdown = wrap.find('.wpuf-pack-dropdown'),
                sub_details = wrap.find('.wpuf-user-sub-info'),
                calcel_btn = wrap.find('.wpuf-cancel-pack');
                add_btn = wrap.find('.wpuf-add-pack');

            if ( sub_dropdown.attr( 'disabled' ) == 'disabled' ) {
                sub_dropdown.show().removeAttr('disabled');
                sub_details.hide().attr('disabled', true );
                calcel_btn.show();
                add_btn.hide();
            } else {
                sub_details.show().removeAttr('disabled');
                sub_dropdown.hide().attr('disabled', true );
                calcel_btn.hide();
                add_btn.show();
            }

        },


        couponCancel: function(e) {

            e.preventDefault();

            var self = $(this),

                data = {

                    action: 'coupon_cancel',

                    _wpnonce: wpuf_frontend.nonce,

                    pack_id: self.data('pack_id')

                },

                coupon_field = self.parents('.wpuf-coupon-info-wrap').find('input.wpuf-coupon-field');



            coupon_field.addClass('wpuf-coupon-field-spinner');

            $.post( wpuf_frontend.ajaxurl, data, function( res ) {

                coupon_field.removeClass('wpuf-coupon-field-spinner');

                if ( res.success ) {

                    $('.wpuf-pack-inner' ).html( res.data.append_data );

                    $('.wpuf-coupon-id-field').val('');

                    conpon_wrap = self.closest('.wpuf-copon-wrap');

                    conpon_wrap.hide();

                    conpon_wrap.siblings('.wpuf-copon-show').show();

                }

            });



        },



        couponShow: function(e) {

            e.preventDefault();

            var self = $(this);

            self.hide();

            self.parents('.wpuf-coupon-info-wrap').find('.wpuf-copon-wrap').show();

        },



        couponApply: function(e) {

            e.preventDefault();

            var self = $(this),

                coupon_field = self.parents('.wpuf-coupon-info-wrap').find('input.wpuf-coupon-field'),

                coupon = coupon_field.val();

            if ( coupon == '' ) {

                return;

            }

            var data = {

                    action: 'coupon_apply',

                    _wpnonce: wpuf_frontend.nonce,

                    coupon: coupon,

                    pack_id: self.data('pack_id')

                };



            if ( self.attr('disabled') == 'disabled' ) {

                //return;

            }

            self.attr( 'disabled', true );

            coupon_field.addClass('wpuf-coupon-field-spinner');



            $.post( wpuf_frontend.ajaxurl, data, function( res ) {

                coupon_field.removeClass('wpuf-coupon-field-spinner');

                if ( res.success ) {



                    $('.wpuf-pack-inner' ).html( res.data.append_data );

                    $('.wpuf-coupon-id-field').val( res.data.coupon_id );

                    if ( res.data.amount <= 0 ) {

                        $('.wpuf-nullamount-hide').hide();

                    }

                } else {

                    $('.wpuf-subscription-error').html(res.data.message);

                }

            });

        },



		showSubscriptionRecurring: function() {



            var self = $(this),

            	wrap = self.parents('table.form-table'),

                pack_child = wrap.find('.wpuf-recurring-child'),

                trial_checkbox = wrap.find( 'input#wpuf-trial-status'),

                trial_child = wrap.find('.wpuf-trial-child'),

                expire_field = wrap.find('.wpuf-subcription-expire');



            if ( self.is(':checked') ) {

            	if ( trial_checkbox.is(':checked') ) {

            		trial_child.show();

            	}

                pack_child.show();

                expire_field.hide();

            } else {

            	trial_child.hide();

                pack_child.hide();

                expire_field.show();

            }

        },



        showSubscriptionPack: function() {

            var self = $(this),

                pack_status = self.closest('table.form-table').find('.wpuf-trial-child');

            if ( self.is(':checked') ) {

                pack_status.show();

            } else {

                pack_status.hide();

            }

        },

	}

    if ( typeof datepicker === 'function') {
        $('.wpuf-date-picker').datepicker({ dateFormat: "yy-mm-dd" });
    }

	subscription.init();

})(jQuery);