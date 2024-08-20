<?php
/*
Plugin Name: Refined WooCommerce Product and Variation Discounted Status
Description: Adds improved discounted status for WooCommerce products and their variations.
Version: 1.3
Author: Stephen Huang
*/

// Add Discounted checkbox to main product
function add_product_discounted_field() {
    woocommerce_wp_checkbox(
        array(
            'id' => '_discounted',
            'label' => 'Discounted',
            'description' => 'Check if this entire product is discounted'
        )
    );
}
add_action('woocommerce_product_options_general_product_data', 'add_product_discounted_field');

// Save Discounted status for main product
function save_product_discounted_field($product_id) {
    $discounted = isset($_POST['_discounted']) ? 'yes' : 'no';
    update_post_meta($product_id, '_discounted', $discounted);
}
add_action('woocommerce_process_product_meta', 'save_product_discounted_field');

// Add Discounted checkbox to variation options
function add_variation_discounted_field($loop, $variation_data, $variation) {
    woocommerce_wp_checkbox(
        array(
            'id' => '_variation_discounted[' . $variation->ID . ']',
            'description' => ' Check if this variation is discounted',
            'value' => get_post_meta($variation->ID, '_variation_discounted', true),
            'class' => 'variation-discounted-checkbox'
        )
    );
}
add_action('woocommerce_product_after_variable_attributes', 'add_variation_discounted_field', 10, 3);

// Save Discounted status for variations
function save_variation_discounted_field($variation_id) {
    $discounted = isset($_POST['_variation_discounted'][$variation_id]) ? 'yes' : 'no';
    update_post_meta($variation_id, '_variation_discounted', $discounted);
}
add_action('woocommerce_save_product_variation', 'save_variation_discounted_field', 10, 1);

// Modify the display_discounted_status function
function display_discounted_status() {
    global $product;

    $product_discounted = get_post_meta($product->get_id(), '_discounted', true);

    if ($product_discounted === 'yes') {
        echo '<div class="discounted-banner">Product Discounted - Limited Stock Available</div>';
    } elseif ($product->is_type('variable')) {
        $variations = $product->get_available_variations();
        echo '<div class="discounted-variations">';
        foreach ($variations as $variation) {
            $variation_id = $variation['variation_id'];
            $variation_obj = wc_get_product($variation_id);
            $discounted = get_post_meta($variation_id, '_variation_discounted', true);
            if ($discounted === 'yes') {
                $attributes = $variation['attributes'];
                foreach ($attributes as $key => $value) {
                    $taxonomy = str_replace('attribute_', '', $key);
                    $term = get_term_by('slug', $value, $taxonomy);
                    if ($term) {
                        $stock_message = ($variation_obj->managing_stock() && $variation_obj->get_stock_quantity() <= 0)
                            ? 'Discounted'
                            : 'Discounted - Limited Stock Available';
                        echo '<span class="discounted">' . $term->name . ': ' . $stock_message . '</span>';
                    }
                }
            }
        }
        echo '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'display_discounted_status', 5);

// Modify the display_shop_discounted_status function
function display_shop_discounted_status() {
    global $product;
    $product_discounted = get_post_meta($product->get_id(), '_discounted', true);
    if ($product_discounted === 'yes') {
        echo '<span class="discounted">Discounted - Limited Stock</span>';
    }
}
add_action('woocommerce_before_shop_loop_item_title', 'display_shop_discounted_status');

// Replace the hide_discontinued_variations function with this new one
function manage_discounted_variations($is_available, $variation) {
    $product_discounted = get_post_meta($variation->get_parent_id(), '_discounted', true);
    $variation_discounted = get_post_meta($variation->get_id(), '_variation_discounted', true);
    
    if (($product_discounted === 'yes' || $variation_discounted === 'yes') && $variation->get_stock_quantity() <= 0) {
        return false;
    }
    return $is_available;
}
add_filter('woocommerce_variation_is_active', 'manage_discounted_variations', 10, 2);

// Add a new function to modify the "Add to Cart" button text for discounted products
function modify_add_to_cart_text($text, $product) {
    $product_discounted = get_post_meta($product->get_id(), '_discounted', true);
    
    if ($product_discounted === 'yes' && $product->is_in_stock()) {
        return __('Add to Cart', 'woocommerce');
    }
    
    return $text;
}
add_filter('woocommerce_product_single_add_to_cart_text', 'modify_add_to_cart_text', 10, 2);
add_filter('woocommerce_product_add_to_cart_text', 'modify_add_to_cart_text', 10, 2);

// Update styles for Discounted label and banner
function add_discounted_styles() {
    echo '<style>
    .discounted {
        background-color: #90EE90;
        color: #000000;
        padding: 5px 10px;
        border-radius: 3px;
        font-weight: bold;
        display: inline-block;
        margin-bottom: 5px;
    }
    .discounted-banner {
        background-color: #90EE90;
        color: #000000;
        padding: 10px;
        text-align: center;
        font-weight: bold;
        margin-bottom: 20px;
    }
    .discounted-variations {
        margin-bottom: 20px;
    }
    .variation-discounted-checkbox {
        margin-right: 10px;
    }
    </style>';
}
add_action('wp_head', 'add_discounted_styles');