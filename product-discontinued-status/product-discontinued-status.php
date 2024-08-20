<?php
/*
Plugin Name: Refined WooCommerce Product and Variation Clearance Status
Description: Adds improved clearance status for WooCommerce products and their variations.
Version: 2.1
Author: Stephen Huang
*/

// Add Clearance checkbox to main product
function add_product_clearance_field() {
    woocommerce_wp_checkbox(
        array(
            'id' => '_clearance',
            'label' => 'Clearance',
            'description' => 'Check if this entire product is on clearance'
        )
    );
}
add_action('woocommerce_product_options_general_product_data', 'add_product_clearance_field');

// Save Clearance status for main product
function save_product_clearance_field($product_id) {
    $clearance = isset($_POST['_clearance']) ? 'yes' : 'no';
    update_post_meta($product_id, '_clearance', $clearance);
}
add_action('woocommerce_process_product_meta', 'save_product_clearance_field');

// Add Clearance checkbox to variation options
function add_variation_clearance_field($loop, $variation_data, $variation) {
    woocommerce_wp_checkbox(
        array(
            'id' => '_variation_clearance[' . $variation->ID . ']',
            'description' => ' Check if this variation is on clearance',
            'value' => get_post_meta($variation->ID, '_variation_clearance', true),
            'class' => 'variation-clearance-checkbox'
        )
    );
}
add_action('woocommerce_product_after_variable_attributes', 'add_variation_clearance_field', 10, 3);

// Save Clearance status for variations
function save_variation_clearance_field($variation_id) {
    $clearance = isset($_POST['_variation_clearance'][$variation_id]) ? 'yes' : 'no';
    update_post_meta($variation_id, '_variation_clearance', $clearance);
}
add_action('woocommerce_save_product_variation', 'save_variation_clearance_field', 10, 1);

// Display clearance status
function display_clearance_status() {
    global $product;

    $product_clearance = get_post_meta($product->get_id(), '_clearance', true);

    if ($product_clearance === 'yes') {
        echo '<div class="clearance-banner">Product on Clearance - Limited Stock Available</div>';
    }

    if ($product->is_type('variable')) {
        $variations = $product->get_available_variations();
        echo '<div class="clearance-variations">';
        foreach ($variations as $variation) {
            $variation_id = $variation['variation_id'];
            $variation_obj = wc_get_product($variation_id);
            $clearance = get_post_meta($variation_id, '_variation_clearance', true);
            
            if ($clearance === 'yes') {
                $attributes = $variation['attributes'];
                foreach ($attributes as $key => $value) {
                    $taxonomy = str_replace('attribute_', '', $key);
                    $term = get_term_by('slug', $value, $taxonomy);
                    if ($term) {
                        $stock_message = ($variation_obj->managing_stock() && $variation_obj->get_stock_quantity() <= 0)
                            ? 'Clearance'
                            : 'Clearance - Limited Stock Available';
                        echo '<span class="clearance">' . $term->name . ': ' . $stock_message . '</span>';
                    }
                }
            }
        }
        echo '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'display_clearance_status', 5);

// Update styles
function add_clearance_styles() {
    echo '<style>
    .clearance {
        background-color: #90EE90;
        color: #000000;
        padding: 5px 10px;
        border-radius: 3px;
        font-weight: bold;
        display: inline-block;
        margin-bottom: 5px;
    }
    .clearance-banner {
        background-color: #90EE90;
        color: #000000;
        padding: 10px;
        text-align: center;
        font-weight: bold;
        margin-bottom: 20px;
    }
    .clearance-variations {
        margin-bottom: 20px;
    }
    .variation-clearance-checkbox {
        margin-right: 10px;
    }
    /* Remove orange line and adjust price display */
    .price del {
        text-decoration: line-through;
        color: #777;
        font-weight: normal;
    }
    .price ins {
        text-decoration: none;
        font-weight: bold;
        color: #dc3545;
    }
    .price del::before,
    .price del::after,
    .price ins::before,
    .price ins::after {
        display: none !important;
    }
    </style>';
}
add_action('wp_head', 'add_clearance_styles');

// Manage clearance variations
function manage_clearance_variations($is_available, $variation) {
    $product_clearance = get_post_meta($variation->get_parent_id(), '_clearance', true);
    $variation_clearance = get_post_meta($variation->get_id(), '_variation_clearance', true);
    
    if (($product_clearance === 'yes' || $variation_clearance === 'yes') && $variation->get_stock_quantity() <= 0) {
        return false;
    }
    return $is_available;
}
add_filter('woocommerce_variation_is_active', 'manage_clearance_variations', 10, 2);

// Modify the "Add to Cart" button text for clearance products
function modify_add_to_cart_text($text, $product) {
    $product_clearance = get_post_meta($product->get_id(), '_clearance', true);
    
    if ($product_clearance === 'yes' && $product->is_in_stock()) {
        return __('Add to Cart', 'woocommerce');
    }
    
    return $text;
}
add_filter('woocommerce_product_single_add_to_cart_text', 'modify_add_to_cart_text', 10, 2);
add_filter('woocommerce_product_add_to_cart_text', 'modify_add_to_cart_text', 10, 2);