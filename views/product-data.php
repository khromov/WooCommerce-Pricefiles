<?php
global $woocommerce, $post;
?>
<div id="options_group_manufacturer" class="options_group">

    <h3><?php _e('Optional information for price files integration'); ?></h3>

    <?php woocommerce_wp_text_input(array(
        'id' => WC_PRICEFILES_PLUGIN_SLUG.'_ean_code', 
        'class' => '', 
        'label' => '<abbr title="' . __('European Article Number / International Article Number barcode number', 'woocommerce') . '">' . __('EAN code', 'woocommerce') . '</abbr>', 
        'desc_tip' => 'true', 
        'description' => __('EAN is the international standard for product barcodes. Type in the whole 8 or 13 digit number below the product barcode.', $this->plugin_slug)
    )); ?>

    <p id="<?php echo WC_PRICEFILES_PLUGIN_SLUG; ?>_ean_code_status"></p>
    
    <?php woocommerce_wp_text_input(array(
        'id' => WC_PRICEFILES_PLUGIN_SLUG.'_sku_manufacturer', 
        'class' => '', 
        'label' => '<abbr title="' . __('Stock Keeping Unit manufacturer', 'woocommerce') . '">' . __('Manufacturer SKU', $this->plugin_slug) . '</abbr>', 
        'desc_tip' => 'true', 
        'description' => __('SKU refers to a Stock-keeping unit, a unique identifier for each distinct product and service that can be purchased.', $this->plugin_slug)
    )); ?>

    <?php woocommerce_wp_text_input(array(
        'id' => WC_PRICEFILES_PLUGIN_SLUG.'_sku_manufacturer_name',
        'class' => '',
        'label' => '<abbr title="' . __('Manufacturer name', 'woocommerce') . '">' . __('Manufacturer name', $this->plugin_slug) . '</abbr>',
        'desc_tip' => 'true',
        'description' => __('Name of manufacturer.', $this->plugin_slug)
    )); ?>

    <?php
    $pricelist_cats = WC_Pricefiles::get_instance()->get_category_list();
    
    // Ensure it exists 
    if ( !(empty($pricelist_cats)) ) 
    {

        $current = get_post_meta( $post->ID, '_pricelist_cat', true );
        
        $category_field = array(
            'id'    => WC_PRICEFILES_PLUGIN_SLUG.'_pricelist_cat',
            'label' => __('Category'),
            'class' => 'chosen-select',
            //'wrapper_class' => '',
            'options' => array()
        );
        
        $c = get_post_meta( $post->ID, WC_PRICEFILES_PLUGIN_SLUG.'_pricelist_cat', true );
        
        if(empty($c))
        {
            $category_field['options'][''] = __('Choose category', WC_PRICEFILES_PLUGIN_SLUG);
        }

        foreach ($pricelist_cats as $id => $name) {
            $category_field['options'][esc_attr($id)] = esc_attr($name);
        }

        woocommerce_wp_select($category_field);
    }
    
    
    // Prisjakt Status
    $current = get_post_meta( $post->ID, '_prisjakt_status', true );
    
    $prisjakt_status_field = array(
        'id'    => WC_PRICEFILES_PLUGIN_SLUG.'_prisjakt_status',
        'label' => __('Prisjakt Status'),
        'class' => 'chosen-select',
        'options' => array()
    );
    
    $prisjakt_status_list = array(
        "Normal" => "Normal",
        "Begagnad" => "Begagnad",
        "Ej köpbar" => "Ej köpbar",
        "Demo" => "Demo",
        "Nerladdning"=> "Nerladdning",
        "Avhämtning" => "Avhämtning"
    );
    
    foreach ($prisjakt_status_list as $id => $name) {
        $prisjakt_status_field['options'][esc_attr($id)] = esc_attr($name);
    }
    
    woocommerce_wp_select($prisjakt_status_field);
    
    // Final
    do_action( WC_PRICEFILES_PLUGIN_SLUG . '_product_options'); 
    
    ?>

</div>

