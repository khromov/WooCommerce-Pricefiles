<?php
/**
 * Description of misc
 *
 * @author peter
 */
class WC_Pricefiles_Misc
{
    function woocommerce_not_active_notice()
    {
        ?>
        <div class="updated fade">
            <p><?php 
            printf(__('The Pricefiles plugin requires the plugin %sWooCommerce%s to work. Please install and activate WooCommerce or deactivate this plugin.', WC_PRICEFILES_PLUGIN_SLUG),
                '<a href="http://wordpress.org/plugins/woocommerce/">', '</a>',
                '<a href="?deactivate-woocommerce-pricefiles=1">', '</a>'
            ); ?></p>
        </div>
        <?php
    }
}
