<?php
function change_price_shortcode($atts) {
    global $wpdb;

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return 'WooCommerce is not active'; // Return a message indicating WooCommerce is not active
    }

    // Initialize WooCommerce
    $woocommerce = WC();

    // Start session explicitly for guest users
    if (!is_user_logged_in() && WC()->session) {
        WC()->session->set_customer_session_cookie(true);
    }

    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => '', // Product ID
        'destinationToBaseRate' => '',
    ), $atts);

    $destinationToBaseRate = $atts['destinationToBaseRate'];
    // Check if ID is provided
    if (empty($atts['id'])) {
        return 'محصول انتخاب نشده'; // Return empty string if ID is missing
    }

    // Make API call to fetch TRY value
    $url = "api url";

    $response = file_get_contents($url);
    if ($response === false) {
        // Handle API call error
        return 'API Error'; // Return empty string
    }

    $data = json_decode($response, true);
    if ($data === null || !isset($data[$destinationToBaseRate]) || !isset($data[$destinationToBaseRate]['value'])) {
        // Handle API response error
        return 'Price in API Not Found'; // Return empty string
    }

    $destinationToBaseRate_value = $data[$destinationToBaseRate]['value'];
    // Set Meta Field Of Currency
    $product_meta = get_post_meta($atts['id'], $destinationToBaseRate, true);
    $new_price = ($product_meta * $destinationToBaseRate_value) * 10;

    // Update the regular price and the sale price in the WordPress database
    $table_name = $wpdb->prefix . 'postmeta';
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET meta_value = %s WHERE (meta_key = '_price' OR meta_key = '_regular_price') AND post_id = %d", $new_price, $atts['id']));

    // Check if the product is already in the user's cart
    $cart = $woocommerce->cart->get_cart();
    $product_in_cart = false;
    foreach ($cart as $cart_item_key => $cart_item) {
        if ($cart_item['product_id'] == $atts['id']) {
            $product_in_cart = true;
            break;
        }
    }

    // If the product is not in the cart, add it
    if (!$product_in_cart) {
        $woocommerce->cart->add_to_cart($atts['id']);
    }

    // Return empty string (shortcode doesn't output anything)
    return 'Success Worked';
}
add_shortcode('change_price', 'change_price_shortcode');
