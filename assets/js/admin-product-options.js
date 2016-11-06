// Administration-specific JavaScripts for WooCommerce Pricefiles
(function ($) {
    "use strict";
    $(function () {

        /**
         * EAN Validation
         */
        $('#options_group_manufacturer').on('input blur', '#woocommerce-pricefiles_ean_code', function() {

            var $this = $(this);
            var val = $this.val();

            $(document).trigger('EAN_check', [ $this, val ]);
        });

        /**
         * EAN validation on load
         */
        $(window).load(function() {
            $(document).trigger('EAN_check', [ $('#woocommerce-pricefiles_ean_code'), $('#woocommerce-pricefiles_ean_code').val() ]);
        });

        /**
         * EAN trigger
         */
        $(document).on('EAN_check', function( e, $this, ean ) {

            $this.css('background-position-x', ($this.width() - 15)+'px' );

            //FIXME: Do in CSS by class to avoid initial load issue
            $this.css('background-image', 'url(' + wc_pricelists_options.woocommerce_pricefiles_url + '/assets/images/ajax-loader.gif)');

            var data = {
                action: 'wc_pricefiles_check_ean_code',
                code: ean
            };

            var ean_check_xhr = $.ajax({
                type: 'POST',
                url: wc_pricelists_options.ajax_url,
                data: data
            }).done(function( response ) {

                if( response.status === 'valid' )
                {
                    $('#woocommerce-pricefiles_ean_code').addClass('valid').removeClass('invalid');
                    $('#woocommerce-pricefiles_ean_code_status').text('').slideUp();
                }
                else if( response.status === 'invalid' )
                {
                    $('#woocommerce-pricefiles_ean_code').addClass('invalid').removeClass('valid');
                    $('#woocommerce-pricefiles_ean_code_status').text(response.msg).slideDown();
                }

                $this.css('background-image', '');
            });
        });
    });
}(jQuery));

