<?php
/*
Plugin Name: Refined WooCommerce Product and Variation Discontinued Status
Description: Adds improved discontinued status for WooCommerce products and their variations.
Version: 1.1
Author: Stephen Huang
*/

// Add Discontinued checkbox to main product
function add_product_discontinued_field() {
    woocommerce_wp_checkbox(
        array(
            'id' => '_discontinued',
            'label' => 'Discontinued',
            'description' => 'Check if this entire product is discontinued'
        )
    );
}
add_action('woocommerce_product_options_general_product_data', 'add_product_discontinued_field');

// Save Discontinued status for main product
function save_product_discontinued_field($product_id) {
    $discontinued = isset($_POST['_discontinued']) ? 'yes' : 'no';
    update_post_meta($product_id, '_discontinued', $discontinued);
}
add_action('woocommerce_process_product_meta', 'save_product_discontinued_field');

// Add Discontinued checkbox to variation options
function add_variation_discontinued_field($loop, $variation_data, $variation) {
    woocommerce_wp_checkbox(
        array(
            'id' => '_variation_discontinued[' . $variation->ID . ']',
            'description' => ' Check if this variation is discontinued',
            'value' => get_post_meta($variation->ID, '_variation_discontinued', true),
            'class' => 'variation-discontinued-checkbox'
        )
    );
}
add_action('woocommerce_product_after_variable_attributes', 'add_variation_discontinued_field', 10, 3);

// Save Discontinued status for variations
function save_variation_discontinued_field($variation_id) {
    $discontinued = isset($_POST['_variation_discontinued'][$variation_id]) ? 'yes' : 'no';
    update_post_meta($variation_id, '_variation_discontinued', $discontinued);
}
add_action('woocommerce_save_product_variation', 'save_variation_discontinued_field', 10, 1);

// Display Discontinued status on product page
function display_discontinued_status() {
    global $product;

    $product_discontinued = get_post_meta($product->get_id(), '_discontinued', true);

    if ($product_discontinued === 'yes') {
        echo '<div class="discontinued-banner">Product Discontinued</div>';
    } elseif ($product->is_type('variable')) {
        $variations = $product->get_available_variations();
        echo '<div class="discontinued-variations">';
        foreach ($variations as $variation) {
            $variation_id = $variation['variation_id'];
            $discontinued = get_post_meta($variation_id, '_variation_discontinued', true);
            if ($discontinued === 'yes') {
                $attributes = $variation['attributes'];
                foreach ($attributes as $key => $value) {
                    $taxonomy = str_replace('attribute_', '', $key);
                    $term = get_term_by('slug', $value, $taxonomy);
                    if ($term) {
                        echo '<span class="discontinued">' . $term->name . ': Discontinued</span>';
                    }
                }
            }
        }
        echo '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'display_discontinued_status', 5);

// Display discontinued status on shop page
function display_shop_discontinued_status() {
    global $product;
    $product_discontinued = get_post_meta($product->get_id(), '_discontinued', true);
    if ($product_discontinued === 'yes') {
        echo '<span class="discontinued">Discontinued</span>';
    }
}
add_action('woocommerce_before_shop_loop_item_title', 'display_shop_discontinued_status');

// Add styles for Discontinued label and banner
function add_discontinued_styles() {
    echo '<style>
    .discontinued {
        background-color: #ff0000;
        color: #ffffff;
        padding: 5px 10px;
        border-radius: 3px;
        font-weight: bold;
        display: inline-block;
        margin-bottom: 5px;
    }
    .discontinued-banner {
        background-color: #ff0000;
        color: #ffffff;
        padding: 10px;
        text-align: center;
        font-weight: bold;
        margin-bottom: 20px;
    }
    .discontinued-variations {
        margin-bottom: 20px;
    }
    .variation-discontinued-checkbox {
        margin-right: 10px;
    }
    </style>';
}
add_action('wp_head', 'add_discontinued_styles');

// Optionally hide discontinued variations from dropdown
function hide_discontinued_variations($is_available, $variation) {
    $product_discontinued = get_post_meta($variation->get_parent_id(), '_discontinued', true);
    $variation_discontinued = get_post_meta($variation->get_id(), '_variation_discontinued', true);
    
    if ($product_discontinued === 'yes' || $variation_discontinued === 'yes') {
        return false;
    }
    return $is_available;
}
add_filter('woocommerce_variation_is_active', 'hide_discontinued_variations', 10, 2);