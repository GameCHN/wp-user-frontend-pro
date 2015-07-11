;(function($) {
    var WP_User_Frontend = {
        init: function() {
            // clone and remove repeated field
            $('.wpuf-form').on('click', 'img.wpuf-clone-field', this.cloneField);
            $('.wpuf-form').on('click', 'img.wpuf-remove-field', this.removeField);
            $('.wpuf-form').on('click', 'a.wpuf-delete-avatar', this.deleteAvatar);
            $('.wpuf-form').on('click', 'a#wpuf-post-draft', this.draftPost);

            $('.wpuf-form-add').on('submit', this.formSubmit);
            $('form#post').on('submit', this.adminPostSubmit);

            this.ajaxCategory();
            // image insert
            // this.insertImage();
        },

        ajaxCategory: function () {

            var el = '.cat-ajax',
                wrap = '.category-wrap';

            $(wrap).on('change', el, function(){
                currentLevel = parseInt( $(this).parent().attr('level') );
                WP_User_Frontend.getChildCats( $(this), 'lvl', currentLevel+1, wrap, 'category');
            });
        },

        getChildCats: function (dropdown, result_div, level, wrap_div, taxonomy) {

            cat = $(dropdown).val();
            results_div = result_div + level;
            taxonomy = typeof taxonomy !== 'undefined' ? taxonomy : 'category';
            field_attr = $(dropdown).siblings('span').data('taxonomy');

            $.ajax({
                type: 'post',
                url: wpuf_frontend.ajaxurl,
                data: {
                    action: 'wpuf_get_child_cat',
                    catID: cat,
                    nonce: wpuf_frontend.nonce,
                    field_attr: field_attr
                },
                beforeSend: function() {
                    $(dropdown).parent().parent().next('.loading').addClass('wpuf-loading');
                },
                complete: function() {
                    $(dropdown).parent().parent().next('.loading').removeClass('wpuf-loading');
                },
                success: function(html) {
                    //console.log( html ); return;
                    $(dropdown).parent().nextAll().each(function(){
                        $(this).remove();
                    });

                    if(html != "") {
                        $(dropdown).parent().addClass('hasChild').parent().append('<div id="'+result_div+level+'" level="'+level+'"></div>');
                        dropdown.parent().parent().find('#'+results_div).html(html).slideDown('fast');
                    }
                }
            });
        },

        cloneField: function(e) {
            e.preventDefault();

            var $div = $(this).closest('tr');
            var $clone = $div.clone();
            // console.log($clone);

            //clear the inputs
            $clone.find('input').val('');
            $clone.find(':checked').attr('checked', '');
            $div.after($clone);
        },

        removeField: function() {
            //check if it's the only item
            var $parent = $(this).closest('tr');
            var items = $parent.siblings().andSelf().length;

            if( items > 1 ) {
                $parent.remove();
            }
        },

        adminPostSubmit: function(e) {
            e.preventDefault();

            var form = $(this),
                form_data = WP_User_Frontend.validateForm(form);

            if (form_data) {
                return true;
            }
        },

        draftPost: function (e) {
            e.preventDefault();

            var self = $(this),
                form = $(this).closest('form'),
                form_data = form.serialize() + '&action=wpuf_draft_post',
                post_id = form.find('input[type="hidden"][name="post_id"]').val();

            var rich_texts = [],
                temp, val;

            // grab rich texts from tinyMCE
            $('.wpuf-rich-validation').each(function (index, item) {
                temp = $(item).data('id');
                val = $.trim( tinyMCE.get(temp).getContent() );

                rich_texts.push(temp + '=' + encodeURIComponent( val ) );
            });

            // append them to the form var
            form_data = form_data + '&' + rich_texts.join('&');


            self.after(' <span class="wpuf-loading"></span>');
            $.post(wpuf_frontend.ajaxurl, form_data, function(res) {
                // console.log(res, post_id);
                if ( typeof post_id === 'undefined') {
                    var html = '<input type="hidden" name="post_id" value="' + res.post_id +'">';
                        html += '<input type="hidden" name="post_date" value="' + res.date +'">';
                        html += '<input type="hidden" name="post_author" value="' + res.post_author +'">';
                        html += '<input type="hidden" name="comment_status" value="' + res.comment_status +'">';

                    form.append( html );
                }

                self.next('span.wpuf-loading').remove();

                self.after('<span class="wpuf-draft-saved">&nbsp; Post Saved</span>');
                $('.wpuf-draft-saved').delay(2500).fadeOut('fast', function(){
                    $(this).remove();
                });
            })
        },

        formSubmit: function(e) {
            e.preventDefault();

            var form = $(this),
                submitButton = form.find('input[type=submit]')
                form_data = WP_User_Frontend.validateForm(form);

            if (form_data) {

                // send the request
                form.find('li.wpuf-submit').append('<span class="wpuf-loading"></span>');
                submitButton.attr('disabled', 'disabled').addClass('button-primary-disabled');

                $.post(wpuf_frontend.ajaxurl, form_data, function(res) {
                    // var res = $.parseJSON(res);

                    if ( res.success) {
                        if( res.show_message == true) {
                            form.before( '<div class="wpuf-success">' + res.message + '</div>');
                            form.slideUp( 'fast', function() {
                                form.remove();
                            });

                            //focus
                            $('html, body').animate({
                                scrollTop: $('.wpuf-success').offset().top - 100
                            }, 'fast');

                        } else {
                            window.location = res.redirect_to;
                        }

                    } else {
                        alert( res.error );
                        submitButton.removeAttr('disabled');
                    }

                    submitButton.removeClass('button-primary-disabled');
                    form.find('span.wpuf-loading').remove();
                });
            }
        },

        validateForm: function( self ) {

            var temp,
                temp_val = '',
                error = false,
                error_items = [];

            // remove all initial errors if any
            WP_User_Frontend.removeErrors(self);
            WP_User_Frontend.removeErrorNotice(self);

            // ===== Validate: Text and Textarea ========
            var required = self.find('[data-required="yes"]:visible');

            required.each(function(i, item) {
                // temp_val = $.trim($(item).val());

                // console.log( $(item).data('type') );
                var data_type = $(item).data('type')
                    val = '';

                switch(data_type) {
                    case 'rich':
                        var name = $(item).data('id')
                        val = $.trim( tinyMCE.get(name).getContent() );

                        if ( val === '') {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'textarea':
                    case 'text':
                        val = $.trim( $(item).val() );

                        if ( val === '') {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'select':
                        val = $(item).val();

                        // console.log(val);
                        if ( !val || val === '-1' ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'multiselect':
                        val = $(item).val();

                        if ( val === null || val.length === 0 ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'tax-checkbox':
                        var length = $(item).children().find('input:checked').length;

                        if ( !length ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'radio':
                        var length = $(item).find('input:checked').length;

                        if ( !length ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'file':
                        var length = $(item).find('ul').children().length;

                        if ( !length ) {
                            error = true;

                            // make it warn collor
                            WP_User_Frontend.markError(item);
                        }
                        break;

                    case 'email':
                        var val = $(item).val();

                        if ( val !== '' ) {
                            //run the validation
                            if( !WP_User_Frontend.isValidEmail( val ) ) {
                                error = true;

                                WP_User_Frontend.markError(item);
                            }
                        }
                        break;


                    case 'url':
                        var val = $(item).val();

                        if ( val !== '' ) {
                            //run the validation
                            if( !WP_User_Frontend.isValidURL( val ) ) {
                                error = true;

                                WP_User_Frontend.markError(item);
                            }
                        }
                        break;

                };

            });

            // if already some error found, bail out
            if (error) {
                // add error notice
                WP_User_Frontend.addErrorNotice(self);

                return false;
            }

            var form_data = self.serialize(),
                rich_texts = [];

            // grab rich texts from tinyMCE
            $('.wpuf-rich-validation').each(function (index, item) {
                temp = $(item).data('id');
                val = $.trim( tinyMCE.get(temp).getContent() );

                rich_texts.push(temp + '=' + encodeURIComponent( val ) );
            });

            // append them to the form var
            form_data = form_data + '&' + rich_texts.join('&');
            return form_data;
        },

        addErrorNotice: function(form) {
            $(form).find('li.wpuf-submit').append('<div class="wpuf-errors">' + wpuf_frontend.error_message + '</div>');
        },

        removeErrorNotice: function(form) {
            $(form).find('.wpuf-errors').remove();
        },

        markError: function(item) {
            $(item).closest('li').addClass('has-error');
            $(item).focus();
        },

        removeErrors: function(item) {
            $(item).find('.has-error').removeClass('has-error');
        },

        isValidEmail: function( email ) {
            var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
            return pattern.test(email);
        },

        isValidURL: function(url) {
            var urlregex = new RegExp("^(http:\/\/www.|https:\/\/www.|ftp:\/\/www.|www.|http:\/\/|https:\/\/){1}([0-9A-Za-z]+\.)");
            return urlregex.test(url);
        },

        insertImage: function() {

            var button = 'wpuf-insert-image',
                container = 'wpuf-insert-image-container';
            if ( !$('#' + button).length) {
                return;
            };

            var imageUploader = new plupload.Uploader({
                runtimes: 'html5,html4',
                browse_button: button,
                container: container,
                multipart: true,
                multipart_params: {
                    action: 'wpuf_insert_image'
                },
                multiple_queues: false,
                multi_selection: false,
                urlstream_upload: true,
                file_data_name: 'wpuf_file',
                max_file_size: '2mb',
                url: wpuf_frontend_upload.plupload.url,
                flash_swf_url: wpuf_frontend_upload.flash_swf_url,
                filters: [{
                    title: 'Allowed Files',
                    extensions: 'jpg,jpeg,gif,png,bmp'
                }]
            });

            imageUploader.bind('Init', function(up, params) {
                // console.log("Current runtime environment: " + params.runtime);
            });

            imageUploader.bind('FilesAdded', function(up, files) {
                var $container = $('#' + container);

                $.each(files, function(i, file) {
                    $container.append(
                        '<div class="upload-item" id="' + file.id + '"><div class="progress progress-striped active"><div class="bar"></div></div></div>');
                });

                up.refresh();
                up.start();
            });

            imageUploader.bind('QueueChanged', function (uploader) {
                imageUploader.start();
            });

            imageUploader.bind('UploadProgress', function(up, file) {
                var item = $('#' + file.id);

                $('.bar', item).css({ width: file.percent + '%' });
                $('.percent', item).html( file.percent + '%' );
            });

            imageUploader.bind('Error', function(up, error) {
                alert('Error #' + error.code + ': ' + error.message);
            });

            imageUploader.bind('FileUploaded', function(up, file, response) {

                $('#' + file.id).remove();

                if(response.response !== 'error' ) {
                    var success = false;

                    if ( typeof tinyMCE !== 'undefined' ) {

                        if ( typeof tinyMCE.execInstanceCommand !== 'function' ) {
                            // tinyMCE 4.x
                            tinyMCE.get('post_content').insertContent(response.response);
                        } else {
                            // tinyMCE 3.x
                            tinyMCE.execInstanceCommand('post_content', 'mceInsertContent', false, response.response);
                        }
                    }

                    // insert failed to the edit, perhaps insert into textarea
                    var post_content = $('#post_content');
                    post_content.val( post_content.val() + response.response );

                } else {
                    alert('Something went wrong');
                }
            });

            imageUploader.init();
        },

        deleteAvatar: function(e) {
            e.preventDefault();

            if ( confirm( $(this).data('confirm') ) ) {
                $.post(wpuf_frontend.ajaxurl, {action: 'wpuf_delete_avatar', _wpnonce: wpuf_frontend.nonce}, function() {
                    window.location.reload();
                });
            }
        }
    };

    $(function() {
        WP_User_Frontend.init();
        WP_User_Frontend.insertImage();

        // payment gateway selection
        $('ul.wpuf-payment-gateways').on('click', 'input[type=radio]', function(e) {
            $('.wpuf-payment-instruction').slideUp(250);

            $(this).parents('li').find('.wpuf-payment-instruction').slideDown(250);
        });

        if( !$('ul.wpuf-payment-gateways li').find('input[type=radio]').is(':checked') ) {
            $('ul.wpuf-payment-gateways li').first().find('input[type=radio]').click()
        } else {
            var el = $('ul.wpuf-payment-gateways li').find('input[type=radio]:checked');
            el.parents('li').find('.wpuf-payment-instruction').slideDown(250);
        }
    });

})(jQuery);