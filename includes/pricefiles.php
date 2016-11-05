<?php
/**
 * WooCommerce Pricefiles
 * Plugin class.
 *
 * @package   woocommerce-pricefiles
 * @author    Peter Elmered <peter@elmered.com>
 * @license   GPL-2.0+
 * @link      http://elmered.com
 * @copyright 2014 Peter Elmered
 */

class WC_Pricefiles
{
    /**
     * Plugin version, used for autoatic updates and for cache-busting of style and script file references.
     *
     * @since    0.1.0
     * @var     string
     */
    const VERSION = '1.0.0';

    /**
     * Unique identifier for your plugin.
     *
     * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
     * match the Text Domain file header in the main plugin file.
     *
     * @since    0.1.0
     * @var      string
     */
    public $plugin_slug = WC_PRICEFILES_PLUGIN_SLUG;

    /**
     * Instance of this class.
     *
     * @since    0.1.0
     * @var      object
     */
    protected static $instance = null;

    /**
     * Plugin options
     * 
     * @since    0.1.0
     */
    protected $options = array();
    
    /**
     * Variable to store cached data in singleton for reuse
     * 
     * @var type 
     * @since    1.0
     */
    protected $cache_data = array();

    /**
     * Initialize the plugin by setting localization, filters, and administration functions.
     * 
     * @since    0.1.0
     */
    private function __construct()
    {
        add_action('init', array($this, 'init'), 20);

        // Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Return an instance of this class.
     *
     * @since     0.1.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance)
        {
            self::$instance = new self;
        }

        return self::$instance;         
    }

    public function check_dependencies()
    {
        //Needed for is_plugin_active() call
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        //if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
        if ( is_plugin_active( 'woocommerce/woocommerce.php' ) )
        {
            return true;
        }
        else
        {
            $misc = new WC_Pricefiles_Misc();
            add_action('admin_notices', array($misc, 'woocommerce_not_active_notice'));
            return false;
        }
    }
    
    function init()
    {
        require 'misc.php';
        
        if(!$this->check_dependencies())
        {
            return false;
        }
        
        // Load public-facing style sheet and JavaScript.
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        if (is_admin())
        {
            $this->options = $this->get_options();
            // Activate plugin when new blog is added
            //add_action('wpmu_new_blog', array($this, 'activate_new_site'));

            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );

            // Add the options page and menu item.
            add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

            // Load admin style sheet and JavaScript.
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

            //Add custom fields to the bottom of the "General" product tab.
            add_action('woocommerce_product_options_general_product_data', array($this, 'product_data_fields'), 999);
            //Hook into product meta save action to save our custom fields.
            add_action('woocommerce_process_product_meta', array($this, 'process_product_data_fields'), 10, 2);

            //Register refresh cache AJAX call
            add_action('wp_ajax_wc_pricefiles_refresh_pricefile_cache', array($this, 'ajax_check_ean_code'));
            //Register EAN code validator AJAX call
            add_action('wp_ajax_wc_pricefiles_check_ean_code', array($this, 'ajax_check_ean_code'));

            add_action('admin_notices', array($this, 'notices'));

            //Update pricefile cat acording to the catorgory mappings on product save
            add_action('save_post', array($this, 'update_pricefile_category'));
            
            //Include admin classes
            require_once( WP_PRICEFILES_PLUGIN_PATH . 'includes/admin.php' );
            require_once( WP_PRICEFILES_PLUGIN_PATH . 'includes/admin/options.php' );
            require_once( WP_PRICEFILES_PLUGIN_PATH . 'includes/admin/category-mapping.php' );

            //Initialize all admin pages
            new WC_Pricefiles_Admin_Options($this->plugin_slug);
            new WC_Pricefiles_Admin_Category_Mapping($this->plugin_slug);
        }

        if (!empty($_GET['pricefile']))
        {
            header('Content-Type: text/html; charset=utf-8',true);
            
            $this->options = $this->get_options();
            
            if (!class_exists('WC_Pricefile_Generator'))
                require_once( WP_PRICEFILES_PLUGIN_PATH . 'includes/generator.php' );
            
            $slug = $_GET['pricefile'];
            
            $available_pricefiles = $this->get_available_pricefiles();
            $available_pricefiles_slugs = array_keys($available_pricefiles);
            
            if(in_array($slug, $available_pricefiles_slugs))
            {
                $class_name = 'WC_Pricefile_'.ucfirst($slug);

                if (!class_exists($class_name))
                    require_once( $available_pricefiles[$slug]['generator_path'] );

                $wc_pricefile_generator = $class_name::get_instance($slug);
                //die();
                $wc_pricefile_generator->generate_pricefile();

                die();
            }
            elseif($slug == 'all')
            {
                if($_GET['refresh'] == 1)
                {
                    while (@ob_end_flush()){}
                    
                    $error = false;
                    $response = array();
                    
                    foreach($available_pricefiles AS $slug => $data )
                    {
                        $class_name = 'WC_Pricefile_'.ucfirst($slug);
                        
                        if (!class_exists($class_name))
                        {
                            require_once( $data['generator_path'] );
                        }
                        
                        $wc_pricefile_generator = $class_name::get_instance($slug);
                        
                        $status = $wc_pricefile_generator->generate_pricefile();
                        
                        $response[$slug] = $status['status'];
                        $response[$slug.'_count'] = $status['product_count'];
                        $response[$slug.'_excluded'] = $status['excluded_count'];
                        $response[$slug.'_hidden'] = $status['hidden_count'];
                        
                        if($status['status'] != 'cache_written')
                        {
                            $error = 'Cache could not be written';
                        }
                    }
                    

                    //AJAX refresh from admin
                    if(!empty($_GET['output']) && $_GET['output'] == 'json')
                    {
                        if($response_code == 'no_cache')
                        {
                            $response['status'] = 'ok_no_cache';
                            $response['code'] = $status['status'];
                            $response['msg'] = 'Cache not activated';
                            $response['time'] = timer_stop();
                        }
                        else if($error)
                        {
                            $response['status'] = 'error';
                            $response['code'] = $status['status'];
                            $response['msg'] = $error;
                            $response['time'] = timer_stop();
                        }
                        else
                        {
                            $response['status'] = 'ok';
                            $response['time'] = timer_stop();
                        }
                        
                        //Add some nice spacing if runt fom command line
                        if(php_sapi_name() === 'cli')
                        {
                            echo "\n\n";
                        }
                        
                        echo json_encode($response);
                        
                        //Add some nice spacing if runt fom command line
                        if(php_sapi_name() === 'cli')
                        {
                            echo "\n\n";
                        }
                        
                        die();        
                    }
                    else
                    {
                        die();
                    }
                }
            }
        }
    }
    
    function get_options()
    {
        if( !empty($this->options) )
        {
            return $this->options;
        }
        
        $options = get_option(WC_PRICEFILES_PLUGIN_SLUG . '_options', array());

        $this->options = wp_parse_args($options, $this->default_pricelist_options());
        
        return $this->options;
    }
    
    /**
     * Get price tax display option. I.e. whether we should out put prices including or excluding tax  
	 *
     * @return  string  'incl' or 'excl'
     * @since   0.1.10
     */
    public function get_price_type()
    {
        if (!empty($this->cache_data['price_type']))
        {
            return $this->cache_data['price_type'];
        }
        $options = WC_Pricefiles()->get_options();
        if ($options['output_prices'] == 'shop')
        {
            $wc_option = get_option('woocommerce_tax_display_cart');
            if(!empty($wc_option) )
            {
                $this->cache_data['price_type'] = $wc_option;
                return $this->cache_data['price_type'];
            }
        } 
        if (!empty($options['output_prices']))
        {
            $this->cache_data['price_type'] = $options['output_prices'];
            return $this->cache_data['price_type'];
        } else
        {
            $this->cache_data['price_type'] = 'incl';
            return $this->cache_data['price_type'];
        }
    }
    
    /**
     * Provides default values for the Display Options.
     */
    function default_pricelist_options()
    {
        global $wc_pricefiles_globals;
        
        $defaults = array(
            //General options
            'output_prices'         => 'shop',
            'exclude_ids'           => array(),
            'stock_status_type'     => 'yes_no',
            'show_variations'       => 0,
            'show_variation_format' => '%title% - %var%',
            'variation_glue'        => ' ',
            'shipping_methods'      => array(),
            'product_id_as_sku'     => 0,
            'prisjakt_referrals'    => 0,
            'shipping_destination'  => $wc_pricefiles_globals['default_shipping_destination'],
            
            //Advanced options
            'use_cache'             => 0,
            'cache_timeout'         => 0,
            'use_debug'             => 0,
            'set_memory_limit'      => 0,
            'disable_timeout'       => 0,
            'deactivate_ean_validation' => 0
        );
        
        return apply_filters('default_pricelist_options', $defaults);
    }
    
    function get_available_pricefiles()
    {
        $wc_pricefiles_list = array(
            /*Name => array(
                'name'              => <service name>,
                'generator_path'    => <generator class>
            ),*/
            'prisjakt' => array(
                'name'              => 'Prisjakt',
                'class'             => 'WC_Pricefile_Prisjakt',
                'generator_path'    => WP_PRICEFILES_PLUGIN_PATH . 'includes/integrations/prisjakt.php',
                'info_link'         => 'http://www.prisjakt.nu/info.php?t=for_stores_price'
            ),
            'pricerunner' => array(
                'name'              => 'Pricerunner',
                'class'             => 'WC_Pricefile_Pricerunner',
                'generator_path'    => WP_PRICEFILES_PLUGIN_PATH . 'includes/integrations/pricerunner.php',
                'info_link'         => 'http://www.pricerunner.se/foretag/aterforsaljare/prisfiler.html'
            ),
        );
        
        return apply_filters( WC_PRICEFILES_PLUGIN_SLUG . '_available_pricefiles', $wc_pricefiles_list);
    }

    function update_pricefile_category($post_id)
    {
        if (isset($_POST['post_type']) && $_POST['post_type'] != 'product')
        {
            return;
        }

        $categories = wp_get_post_terms($post_id, 'product_cat');
        
        if( empty($categories))
        {
            //No product categories found
            return;
        }
        
        $cat = $this->get_deepest_child_category($categories);

        $cats_map = get_option($this->plugin_slug . '_category_mappings');
        $pl_cat = $cats_map[$cat->term_id];

        update_post_meta($post_id, '_pricelist_cat', $pl_cat);
    }

    /**
     * Extract the deepest caregory from a category tree (from wp_get_post_terms())
     * TODO: Revist. Not a perfect solution
     * 
     * @param type $categories
     * @return boolean
     */
    function get_deepest_child_category($categories)
    {
        $maxId = 0;
        $maxKey = 0;

        if( empty($categories))
        {
            return false;
        }

        foreach ($categories as $key => $value)
        {
            if ($value->parent > $maxId)
            {
                $maxId = $value->term_id;
                $maxKey = $key;
            }
        }

        return $categories[$maxKey];
    }

    /**
     * Fired when the plugin is activated.
     *
     * @since    0.1.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
     */
    public function activate($network_wide)
    {
        if (function_exists('is_multisite') && is_multisite())
        {
            if ($network_wide)
            {
                // Get all blog ids
                $blog_ids = $this->get_blog_ids();

                foreach ($blog_ids as $blog_id)
                {
                    switch_to_blog($blog_id);
                    $this->single_activate();
                }
                restore_current_blog();
            }
            else
            {
                $this->single_activate();
            }
        }
        else
        {
            $this->single_activate();
        }
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @since    0.1.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
     */
    public function deactivate($network_wide)
    {
        if (function_exists('is_multisite') && is_multisite())
        {
            if ($network_wide)
            {
                // Get all blog ids
                $blog_ids = $this->get_blog_ids();

                foreach ($blog_ids as $blog_id)
                {
                    switch_to_blog($blog_id);
                    $this->single_deactivate();
                }
                restore_current_blog();
            }
            else
            {
                $this->single_deactivate();
            }
        }
        else
        {
            $this->single_deactivate();
        }
    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @since    0.1.0
     *
     * @param	int	$blog_id ID of the new blog.
     */
    public function activate_new_site($blog_id)
    {
        if (1 !== did_action('wpmu_new_blog'))
        {
            return;
        }
        
        switch_to_blog($blog_id);
        $this->single_activate();
        restore_current_blog();
    }

    /**
     * Get all blog ids of blogs in the current network that are:
     * - not archived
     * - not spam
     * - not deleted
     *
     * @since    0.1.0
     *
     * @return	array|false	The blog ids, false if no matches.
     */
    private function get_blog_ids()
    {
        global $wpdb;

        // get an array of blog ids
        $sql = "SELECT blog_id FROM $wpdb->blogs
                    WHERE archived = '0' AND spam = '0'
                    AND deleted = '0'";
        return $wpdb->get_col($sql);
    }

    /**
     * Fired for each blog when the plugin is activated.
     *
     * @since    0.1.0
     */
    private function single_activate()
    {
        $this->init();
        
        $this->woocomemrce_pricefiles_add_manufacturer_attribute();
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     *
     * @since    0.1.0
     */
    private function single_deactivate()
    {
        
    }

    /**
     * action_links function.
     *
     * @access public
     * @param mixed $links
     * @return void
     */
    public function action_links($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page='.$this->plugin_slug) . '">' . __('Settings', $this->plugin_slug) . '</a>',
            //'<a href="http://extendwp.com/">' . __( 'Docs', $this->plugin_slug ) . '</a>',
            '<a href="http://wordpress.org/plugins/woocommerce-pricefiles/">' . __('Info & Support', $this->plugin_slug) . '</a>',
        );

        return array_merge($plugin_links, $links);
    }

    /**
     * Add Manufacturer product attribute if not exists
     * 
     * @global type $wpdb
     */
    function woocomemrce_pricefiles_add_manufacturer_attribute()
    {
        global $wpdb;
        
        //@TODO: add check for WC Brands plugin

        $manufacturer = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'woocommerce_attribute_taxonomies WHERE attribute_name = "manufacturer"');
        
        if ($manufacturer == null)
        {
            $attribute = array(
                'attribute_label' => __('Manufacturer', $this->plugin_slug),
                'attribute_name' => 'manufacturer',
                'attribute_type' => 'select',
                'attribute_orderby' => 'name', //Or menu_order?
            );

            $wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute);

            //Flush rewrite rules and cache
            flush_rewrite_rules();
            delete_transient('wc_attribute_taxonomies');

            do_action('woocommerce_attribute_added', $wpdb->insert_id, $attribute);
        }
    }



    /**
     * 
     * @global type $wc_pricefiles_globals
     * @return type
     */
    function get_shipping_destination()
    {
        $shipping_destination = array();
        $shipping_destination_values = $this->options['shipping_destination'];

        
        if (!is_array($shipping_destination_values) || empty($shipping_destination_values))
        {
            $shipping_destination_values = $wc_pricefiles_globals['default_shipping_destination'];
        }

        foreach ($shipping_destination_values AS $key => $value)
        {
            $shipping_destination[str_replace('shipping_', '', $key)] = $value;
        }

        if(!isset($shipping_destination['state']))
        {
            $shipping_destination['state'] = '';
        }
        if(!isset($shipping_destination['address_2']))
        {
            $shipping_destination['address_2'] = '';
        }
        
        return $shipping_destination;
    }
    
    
    function get_shipping_methods()
    {
        $shipping_methods = $this->options['shipping_methods'];

        if (is_array($shipping_methods) && count($shipping_methods))
        {
            foreach ($shipping_methods AS $shipping_method)
            {
                $s[$shipping_method] = $shipping_method;
            }
        } else
        {
            $s = array();
        }

        return $s;
    }


    function get_manufacturer_attribute_taxonomy()
    {
        global $wpdb;

        $attribute_taxonomies = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = 'manufacturer'");

        return apply_filters('woocommerce_attribute_taxonomies', $attribute_taxonomies);
    }

    function product_data_fields()
    {
        require(WP_PRICEFILES_PLUGIN_PATH . 'views/product-data.php');
    }

    function process_product_data_fields($post_id, $post)
    {
        if(!empty($_POST[$this->plugin_slug.'_ean_code']))
        {
            update_post_meta($post_id, $this->plugin_slug.'_ean_code', stripslashes($_POST[$this->plugin_slug.'_ean_code']));            
        }
        if(!empty($_POST[$this->plugin_slug.'_sku_manufacturer']))
        {
            update_post_meta($post_id, $this->plugin_slug.'_sku_manufacturer', stripslashes($_POST[$this->plugin_slug.'_sku_manufacturer']));            
        }
        if(!empty($_POST[$this->plugin_slug.'_manufacturer']))
        {
            update_post_meta($post_id, $this->plugin_slug.'_manufacturer', stripslashes($_POST[$this->plugin_slug.'_manufacturer']));            
        }
        if(!empty($_POST[$this->plugin_slug.'_pricelist_cat']))
        {
            update_post_meta($post_id, $this->plugin_slug.'_pricelist_cat', stripslashes($_POST[$this->plugin_slug.'_pricelist_cat']));            
        }
        if(!empty($_POST[$this->plugin_slug.'_prisjakt_status']))
        {
            update_post_meta($post_id, $this->plugin_slug.'_prisjakt_status', stripslashes($_POST[$this->plugin_slug.'_prisjakt_status']));            
        }
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    0.1.0
     */
    public function load_plugin_textdomain()
    {
        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, FALSE, basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * Register and enqueue admin-specific style sheet.
     *
     * @since     0.1.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles()
    {
        global $wp_scripts;

        wp_enqueue_style('woocommerce_frontend_styles', WC()->plugin_url() . '/assets/css/woocommerce.css');

        $jquery_version = isset($wp_scripts->registered['jquery-ui-core']->ver) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

        wp_enqueue_style('jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css');

        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');

        wp_enqueue_style($this->plugin_slug . '-admin-styles', WP_PRICEFILES_PLUGIN_URL . 'assets/css/admin.css', array(), self::VERSION);

        wp_enqueue_style($this->plugin_slug . '-admin-options-styles', WP_PRICEFILES_PLUGIN_URL . 'assets/css/admin-options.css', array(), self::VERSION);
    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @since     0.1.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts()
    {
        //global $current_screen, $typenow, $woocommerce;

        wp_enqueue_script($this->plugin_slug . '-admin-script', WP_PRICEFILES_PLUGIN_URL . 'assets/js/admin-product-options.js', array('jquery', 'chosen'), self::VERSION);

        wp_enqueue_script($this->plugin_slug . '-admin-options-script', WP_PRICEFILES_PLUGIN_URL . 'assets/js/admin-options.js', array('jquery', 'chosen', 'ajax-chosen'), self::VERSION);
    
        //Inject variables into our scripts
        wp_localize_script($this->plugin_slug . '-admin-script', 'wc_pricelists_options', array(
            'woocommerce_url'           => WC()->plugin_url(),
            'ajax_url'                  => admin_url('/admin-ajax.php'),
            'search_products_nonce' 	=> wp_create_nonce("search-products"),
        ));
        
        wp_localize_script($this->plugin_slug . '-admin-options-script', 'wc_pricelists_options', array(
            'woocommerce_url'           => WC()->plugin_url(),
            'site_url'                  => get_bloginfo('url'),
            'ajax_url'                  => admin_url('/admin-ajax.php'),
            'search_products_nonce' 	=> wp_create_nonce("search-products"),
        ));
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    0.1.0
     */
    public function add_plugin_admin_menu()
    {
        
    }

    /**
     * AJAX call handler for EAN validation
     *
     * @since    0.1.0
     */
    function ajax_check_ean_code()
    {
        $code = $_POST['code'];

        $status = $this->check_ean_code($code);

        if (is_numeric($status))
        {
            $resp = array(
                'code' => $code,
                'new_code' => $status,
                'status' => 'corrected'
            );
        }
        else if ($status === true)
        {
            $resp = array(
                'code' => $code,
                'status' => 'valid'
            );
        }
        else
        {
            $resp = array(
                'code' => $code,
                'status' => 'invalid',
                'msg' => __('The EAN code is not valid. Please check the numbers.', $this->plugin_slug)
            );
        }

        header("Content-Type: application/json");
        echo json_encode($resp);
        die();
    }

    /**
     * Validates if a EAN code is correct by calculating the checksum. Supports EAN8 and EAN13
     * 
     * @param type $code EAN code the test
     * @return boolean|string Returns TRUE if valid, FALSE if invalid and retuturn full EAN code when pased in without checksum.
     *
     * @since    0.1.0
     */
    function check_ean_code($code)
    {
        $input_checksum = false;

        if (strlen($code) == 13 || strlen($code) == 8)
        {
            $input_checksum = substr($code, -1);
            $code = substr($code, 0, -1);
        }
        elseif (strlen($code) != 12)
        {
            // Invalid EAN13 barcode
            return FALSE;
        }

        $sequence_ean8 = array(3, 1);
        $sequence_ean13 = array(1, 3);

        $sums = 0;

        foreach (str_split($code) as $n => $digit)
        {
            if (strlen($code) == 7)
            {
                $sums += $digit * $sequence_ean8[$n % 2];
            }
            elseif (strlen($code) == 12)
            {
                $sums += $digit * $sequence_ean13[$n % 2];
            }
            else
            {
                return FALSE;
            }
        }

        $checksum = 10 - $sums % 10;
        if ($checksum == 10)
        {
            $checksum = 0;
        }

        if ($input_checksum !== false)
        {
            if ($checksum == $input_checksum)
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }
        else
        {
            $ean_code = $code . $checksum;
            return $ean_code;
        }
    }

    /**
     * Display notice in admin if not configured
     *
     * @access public
     * @return void
     */
    public function notices()
    {
        if (!get_option($this->plugin_slug . '_options', FALSE))
        {
            ?>
            <div class="updated fade">
                <p><?php printf(__('The Pricefiles plugin needs to be configured to work. Configure it <a href="%s">here</a>', $this->plugin_slug), admin_url('admin.php?page=' . $this->plugin_slug)); ?></p>
            </div>
            <?php
        }
    }

    function get_shipping_destination_values()
    {
        $shipping_destination = array();
        $options = $this->get_options();
        
        foreach ($options['shipping_destination'] AS $key => $value)
        {
            $shipping_destination[str_replace('shipping_', '', $key)] = $value;
        }
        
        return $shipping_destination;
    }
    
    /**
     * Gets an array of default shipping destination fields to be used of shipping calculations 
     * 
     * @return array Array of shipping destination fields
     */
    public function get_shipping_destination_fields()
    {
        $shipping_destination_fields = array(
            'country' => array(
                'type' => 'country',
                'label' => 'Country',
                'class' => array(
                    'form-row-wide'
                )
            ),
            'address_1' => array(
                'label' => 'Address',
                'class' => array(
                    'form-row-wide', 'address-field'
                ),
                'custom_attributes' => array(
                    'autocomplete' => 'no'
                )
            ),
            'postcode' => array(
                'label' => 'Postcode / Zip',
                'class' => array(
                    'form-row-first'
                )
            ),
            'city' => array(
                'label' => 'Town / City',
                'class' => array(
                    'form-row-last', 'address-field'
                ),
            ),
                /*
                  'state' => array(
                  'type' => 'state',
                  'label' => 'State / County',
                  'class' => array(
                  'form-row-wide', 'address-field'
                  ),
                  'custom_attributes' => Array
                  (
                  'autocomplete' => 'no'
                  )
                  )
                 */
        );

        return apply_filters('wc_pricefiles_destination_destination_fields', $shipping_destination_fields);
    }

    /**
     * Gets an array of default shipping destination fields to be used of shipping calculations 
     * 
     * @return array Array of shipping destination fields
     */
    public function get_category_list()
    {
        global $wc_pricefiles_globals, $wpdb;
        $pricelist_cats = $wc_pricefiles_globals['wc_pricefiles_categories'];

        //Get used categories sorted by usage
        /*
          $cl = $wpdb->get_results("SELECT pm.meta_value, COUNT(pm.meta_id) AS count
          FROM $wpdb->posts AS p
          JOIN $wpdb->postmeta AS pm ON pm.post_id = p.ID
          WHERE pm.meta_key = '_pricelist_cat'
          GROUP BY pm.meta_value
          ORDER BY count DESC;");
         */
        $cl = $wpdb->get_results("
                SELECT pm.meta_value, COUNT(pm.meta_id) AS count 
                FROM $wpdb->postmeta AS pm 
                WHERE pm.meta_key = '_pricelist_cat' AND pm.meta_value != 0 
                GROUP BY pm.meta_value 
                ORDER BY count DESC");

        $up = array();

        //Get and unset form orginal category list
        foreach ($cl AS $c)
        {
            if (empty($pricelist_cats[$c->meta_value]))
                continue;

            $up[$c->meta_value] = $pricelist_cats[$c->meta_value] . ' (' . $c->count . ')';

            unset($pricelist_cats[$c->meta_value]);
        }

        //Put used categories on top and return
        return $up + $pricelist_cats;
        //return array_merge($up, $pricelist_cats);
    }
}

