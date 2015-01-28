<?php
/**
 * Unified interface to get product data
 *
 * @author peter
 */

class WC_Pricefiles_Product
{
    private static $product;
    private static $product_meta = array();
    
    private static $options = array();
    
    private static $price_type;
    
    public function __construct( $product_id )
    {
        self::$product = get_product( $product_id );

        self::$product_meta = get_post_meta( $product_id );
        
        self::$options = WC_Pricefiles()->get_options();
    }
    
    /**
     * Should product be shown in product feed
     * 
     * @return boolean
     */
    public static function show()
    {
        if (!self::$product->is_purchasable() || self::$product->visibility == 'hidden')
        {
            return false;
        }
        else
        {
            return true;
        }
    }
    
    
    
    // Getters
    
    
    /**
     * Get price with correct tax display option
	 *
     * @return  string  'incl' or 'excl'
     * @since   0.1.10
     */
    public static function get_price()
    {
        if (self::get_price_type() === 'excl')
        {
            return self::$product->get_price_excluding_tax(1);
        } else
        {
            return self::$product->get_price_including_tax(1);
        }
    }

    /**
     * Get price tax display option. I.e. whether we should out put prices including or excluding tax  
	 *
     * @return  string  'incl' or 'excl'
     * @since   0.1.10
     */
    public static function get_price_type()
    {
        if (!empty(self::$price_type))
        {
            return self::$price_type;
        }
        if (self::$options['output_prices'] == 'shop')
        {
            $wc_option = get_option('woocommerce_tax_display_cart');
            if(!empty($wc_option) )
            {
                self::$price_type = $wc_option;
                return self::$price_type;
            }
        } 
        if (!empty($this->options['output_prices']))
        {
            self::$price_type = self::$options['output_prices'];
            return self::$price_type;
        } else
        {
            self::$price_type = 'incl';
            return self::$price_type;
        }
    }

    /**
     * Extract the EAN code of a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return string The EAN code or an empty string if it's missing.
     * @since    0.1.10
     */
    public static function get_ean()
    {
        if (isset(self::$product_meta[WC_PRICEFILES_PLUGIN_SLUG . '_ean_code'][0]))
        {
            return self::$product_meta[WC_PRICEFILES_PLUGIN_SLUG . '_ean_code'][0];
        }
        else {
            return '';
        }
    }

    /**
     * Extract the manufacturer name of a product.
     *
     * @param   array   An array with keys for product and product_meta.
     * @return  string  The manufacturer name or an empty string if it's missing.
     * @since   0.1.10
     */
    public static function get_manufacturer()
    {
        if (isset(self::$product_meta[WC_PRICEFILES_PLUGIN_SLUG . '_manufacturer'][0]))
        {
            $term = get_term_by('slug', self::$product_meta[WC_PRICEFILES_PLUGIN_SLUG . '_manufacturer'][0], 'pa_manufacturer');
            if ($term !== false) {
                return $term->name;
            }
            else {
                return '';
            }
        }
        else {
            return '';
        }
    }

    /**
     * Extract the manufacturer SKU of a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  string  The manufacturer SKU or an empty string if it's missing.
     * @since    0.1.10
     */
    public static function get_manufacturer_sku()
    {
        if (isset(self::$product_meta[WC_PRICEFILES_PLUGIN_SLUG . '_sku_manufacturer'][0]))
        {
            return self::$product_meta[WC_PRICEFILES_PLUGIN_SLUG . '_sku_manufacturer'][0];
        }
        else {
            return '';
        }
    }

    /**
     * Extract the stock status of a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  string  'Ja' or 'Nej'
     * @since    0.1.12
     */
    public static function get_stock_status()
    {
        if (self::$product->is_in_stock())
        {
            return 'Ja';
        }
        else
        {
            return 'Nej';
        }
    }

    /**
     * Extract the product URL a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  string  A URL.
     * @since    0.1.12
     */
    public static function get_product_url()
    {
        return get_permalink(self::$product->id);
    }

    /**
     * Extract the image URL a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  string  A URL or an empty string if it's missing.
     * @since    0.1.12
     */
    public static function get_image_url()
    {
        $product_id = self::$product->id;
        if (has_post_thumbnail($product_id))
        {
            return wp_get_attachment_url(get_post_thumbnail_id($product_id));
        }
        else
        {
            return '';
        }
    }

    /**
     * Extract the product SKU of a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  string  The SKU.
     * @since    0.1.12
     */
    public static function get_product_sku()
    {
        return self::$product->get_sku();
    }

    /**
     * Extract the product title of a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  string  The product title.
     * @since    0.1.12
     */
    public static function get_product_title()
    {
        return self::$product->post->post_title;
    }

    /**
     * Extract the description of a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  string  The product description.
     * @since    0.1.12
     */
    public static function get_description()
    {
        return strip_tags(self::$product->post->post_excerpt);
    }

    /**
     * Extract the stock quantity of a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  int  The stock quantity
     * @since    0.1.12
     */
    public static function get_stock_quantity()
    {
        return self::$product->get_stock_quantity();
    }

    /**
     * Extract the delivery time of a product.
     * 
     * @param   array   An array with keys for product and product_meta.
     * @return  string  Always an empty string.
     * @since    0.1.12
     */
    static function get_delivery_time()
    {
        return '';
    }
    
    /**
     * Get product categories formatted for pricefile.
	 *
     * @param   array   $product_meta Return value from get_post_meta()
     * @return  string  The manufacturer name or an empty string if it's missing.
     * @since   0.1.10
     */
    public static function get_categories()
    {
        global $wc_pricefiles_globals;
        
        $product_id = self::$product->id;

        $cat = get_post_meta($product_id, WC_PRICEFILES_PLUGIN_SLUG . '_pricelist_cat', true);
        
        if ($cat && !empty($wc_pricefiles_globals['wc_pricefiles_categories'][$cat]))
        {
            return $wc_pricefiles_globals['wc_pricefiles_categories'][$cat];
        }

        $terms = get_the_terms($product_id, 'product_cat');
        
        if(is_wp_error( $terms ) || count($terms) == 0)
        {
            return '';
        }
        
        $cat = '';
        $parents = array();
        $t = array();
        $t2 = array();

        foreach ($terms AS $term)
        {
            if ($term->parent)
            {
                $parents[] = $term->parent;
            }

            $ancestors = get_ancestors($term->term_id, 'product_cat');

            $t_str = '';

            foreach ($ancestors AS $a)
            {
                $tt = get_term($a, 'product_cat');
                $t2[] = $tt->name;

                $t_str = $tt->name . ' > ' . $t_str;
            }
            $t[$term->term_id] = $t_str . $term->name;
        }

        foreach ($parents AS $p)
        {
            unset($t[$p]);
        }

        return implode(',', $t);
    }

    /**
     * This function is a hack to calculate the shipping cost for a single product. To do this we must first build a cart object and after that a package object that is needed to calculate the price. 
     * TODO: This need to be revisited. Has been improved, but not perfect
     * 
     * @global  object  $woocommerce
     * @param   object  $product Product object
     * @return  float   Lowest shipping price
     */
    public static function get_shipping_cost()
    {
        // Packages array for storing package/cart object
        $packages = array();
        $product = self::$product;

        $price = $product->get_price_excluding_tax(1);
        $price_tax = $product->get_price_including_tax(1) - $price;

        // Build up a fake package object
        $cart = array(
            'product_id' => $product->id,
            'variation_id' => '',
            'variation' => '',
            'quantity' => 1,
            'data' => $product,
            'line_total' => $price,
            'line_tax' => $price_tax,
            'line_subtotal' => $price,
            'line_subtotal_tax' => $price_tax,
        );

        // Items in the package
        $packages[0]['contents'][md5('wc_pricefiles_' . $product->id . $price)] = $cart;  
        // Cost of items in the package, set below
        $packages[0]['contents_cost'] = $price;  
        // Applied coupons - some, like free shipping, affect costs    
        $packages[0]['applied_coupons'] = ''; 
        // Fake destination address. Needed for calculation the shipping
        $packages[0]['destination'] = WC_Pricefiles()->get_shipping_destination();

        // Apply filters to mimic normal behaviour
        $packages = apply_filters('woocommerce_cart_shipping_packages', $packages);


        $package = $packages[0];
        
        // Calculate the shipping using our fake package object
        $shipping_method_rates = WC()->shipping->calculate_shipping_for_package($package);

        $shipping_methods = WC_Pricefiles()->get_shipping_methods();

        $lowest_shipping_cost = 0;

        if (!empty($shipping_methods))
        {
            //$shipping_methods = array_intersect_key($shipping_method_rates['rates'], $this->shipping_methods);

            foreach ($shipping_method_rates['rates'] AS $rate)
            {
                if (in_array($rate->method_id, $shipping_methods))
                {
                    $total_tax = 0;
                    
                    if( $this->get_price_type() == 'incl' )
                    {
                        //Sum the taxes
                        foreach($rate->taxes AS $tax)
                        {
                            $total_tax += $tax;
                        }

                        //Calc shipping cost including tax
                        $total_cost = $rate->cost + $total_tax;
                    }
                    else
                    {
                        $total_cost = $rate->cost;
                    }

                    if (empty($lowest_shipping_cost) || $total_cost < $lowest_shipping_cost)
                    {
                        $lowest_shipping_cost = $total_cost;
                    }
                }
            }
        }

        return $lowest_shipping_cost;
    }

    
}