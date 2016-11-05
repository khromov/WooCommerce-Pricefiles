<?php
/**
 * @package   woocommerce-pricefiles
 * @author    Peter Elmered <peter@elmered.com>
 * @license   GPL-2.0+
 * @link      http://elmered.com
 * @copyright 2014 Peter Elmered
 *
 * @wordpress-plugin
 * Plugin Name: WooCommerce Pricefiles Lite
 * Plugin URI:  http://wordpress.org/plugins/woocommerce-pricefiles/
 * Description: Connect your WooCommerce shop to Price comparison sites with Pricefiles. Supports: Prisjakt / PriceSpy and Pricerunner
 * Version:     2.0
 * Author:      Peter Elmered, khromov
 * Author URI:  http://elmered.com
 * Text Domain: woocommerce-pricefiles
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

define('WP_PRICEFILES_PLUGIN_NAME', trailingslashit(plugin_basename(__FILE__)));
define('WP_PRICEFILES_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define('WP_PRICEFILES_PLUGIN_URL', plugins_url('', __FILE__).'/');

require( WP_PRICEFILES_PLUGIN_PATH . 'define.php');
require_once( WP_PRICEFILES_PLUGIN_PATH . 'includes/pricefiles.php' );
add_action( 'plugins_loaded', 'WC_Pricefiles' );

function WC_Pricefiles()
{
    require_once( WP_PRICEFILES_PLUGIN_PATH .'includes/pricefiles.php' );
    return WC_Pricefiles::get_instance();
}