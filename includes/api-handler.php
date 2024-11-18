<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
add_action('init', 'initialize_newplugin');

function initialize_newplugin() {
    $current_user = wp_get_current_user();
    if ($current_user->ID) {
        $vendorID = $current_user->ID;
        error_log("Vendor ID: " . $vendorID); // Use it as needed
    }
}
function newplugin_api_login( $username, $password ) {
    $url = 'http://localhost:8085/api/partnerCentral/authenticate'; // Dummy API URL

    $response = wp_remote_post( $url, array(
        'body' => json_encode( array(
            'userName' => $username,
            'password' => $password
        ) ),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        return array( 'success' => false, 'message' => $response->get_error_message() );
    }
//    $body = json_decode( wp_remote_retrieve_body( $response['body'] ), true );
    if($response['response']['code']===200 && $response['response']['message']==="OK"){
        $body = json_decode($response['body']);
        return $body;

    }else{
        return "Invalid Login Credentials";
    }

//    if ( isset( $body['jwtToken'] ) && $body['success'] ) {
//        return array( 'success' => true );
//    } else {
//        return array( 'success' => false, 'message' => isset( $body['message'] ) ? $body['message'] : 'Unknown error' );
//    }
}

function newplugin_api_user_identification($jwt, $partnerDetail) {
    $url = 'http://localhost:8085/api/partnerCentral/wordpress/wordPressUserIdentification?isLogin=true';
    $partnerDetail = (object) $partnerDetail;

    $response = wp_remote_post($url, array(
        'body' => json_encode($partnerDetail),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt
        ),
    ));
    if (is_wp_error($response)) {
        return array('success' => false, 'message' => $response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return $body;
}

function newplugin_getWordpressProductsByVendor($vendorId) {
    if (isset($_SESSION['user_data'])) {
        $user_data = $_SESSION['user_data'];
        $jwt_token = $user_data['pc_jwtToken'];
    }
    $url = 'http://localhost:8085/api/partnerCentral/wordpress/getWordPressProductsbyVendor?vendorID=' . urlencode($vendorId);

    // Set up the request headers
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $jwt_token,
            'Content-Type' => 'application/json',
        ),
        'method' => 'GET',
    );

    // Make the GET request
    $response = wp_remote_get($url, $args);

    // Check for errors
    if (is_wp_error($response)) {
        return json_encode(array('success' => false, 'data' => 'Error fetching products: ' . $response->get_error_message()));
    }

    // Retrieve and return the response body
    $response_body = wp_remote_retrieve_body($response);
    $body = json_decode($response_body, true);

    // Log the response for debugging
    error_log('Response body: ' . $response_body);

    // Check if the response is valid
    if (!$body) {
        return json_encode(array('success' => false, 'data' => 'Invalid response from API.'));
    }

    // Return the products data
    return json_encode(array('success' => true, 'data' => $body));
}

function newplugin_sync_product($product_details,$vendor_id) {
    $user_data = $_SESSION['user_data'];
    $jwt_token = $user_data['pc_jwtToken'];
    if (empty($product_ids)) {
        return json_encode(array('success' => false, 'data' => 'No product IDs provided.'));
    }
    $url = 'http://localhost:8085/api/partnerCentral/wordpress/addWordPressProduct'; // External API URL
    $dataArray = array();

    foreach ($product_details as $product_detail) {
        echo "@@@@@@@@@@@@@";
        $product_id = $product_detail->productId; // Correct product ID variable name
        $product = wc_get_product($product_id);

        if (!$product) {
            return json_encode(array('success' => false, 'data' => 'Product not found for ID: ' . $product_detail->productId));
        }

        $data = array(
            'id' => $product->get_id(),
            'vendorId' => $vendor_id,
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'stock_status' => $product->get_stock_status(),
            'categories' => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
            'variations' => array()
        );

        if ($product->is_type('variable')) {
            $available_variations = $product->get_available_variations();

            foreach ($available_variations as $variation) {
                $variation_product = new WC_Product_Variation($variation['variation_id']); // Get the variation product object

                // Add each variation's data to the variations array
                $data['variations'][] = array(
                    'variationID' => $variation_product->get_id(),
                    'sku' => $variation_product->get_sku(),
                    'price' => $variation_product->get_price(),
                    'variation_stock_status' => $variation_product->get_stock_status(),
                    'attributes' => $variation_product->get_attributes()
                );
            }
        }

        $dataArray[] = $data;
    }
    $response = wp_remote_post($url, array(
        'body' => json_encode($dataArray),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt_token
        ),
    ));

    if (is_wp_error($response)) {
        return json_encode(array('success' => false, 'data' => 'Error posting to API: ' . $response->get_error_message()));
    }
    $response_body = wp_remote_retrieve_body($response);
    error_log('Response body: ' . $response_body);  // Log the response body for debugging

    $body = json_decode($response_body, true);

    if (!$body) {
        return json_encode(array('success' => false, 'data' => 'Invalid response from API.'));
    }

    if (isset($body['status_code']) && isset($body['message_status'])) {
        if($body['status_code']===200 && $body['message_status']==="Success"){
            return json_encode(array('success' => true, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
        }else{
            return json_encode(array('success' => false, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
        }

    } else {
        return json_encode(array('success' => false, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
    }
}

add_action('wp_ajax_sync_product', 'handle_sync_product');
add_action('wp_ajax_nopriv_sync_product', 'handle_sync_product');

function handle_sync_product() {
    check_ajax_referer('sync_product_nonce', 'security');
    if (!isset($_POST['product_id'])) {
        wp_send_json_error('No product ID provided.');
    }

    $product_id = $_POST['product_id'];
    $vendor_id = $_POST['vendor_id'];
    $response = newplugin_sync_product($product_id,$vendor_id);
    $response = json_decode($response, true);
    if ($response['success']) {
        wp_send_json_success($response['data']);
    } else {
        wp_send_json_error($response['data']);
    }
}


function newplugin_deactivate_product($product_ids) {
    $user_data = $_SESSION['user_data'];
    $jwt_token = $user_data['pc_jwtToken'];

    if (empty($product_ids)) {
        error_log("No product IDs provided.");
        return json_encode(array('success' => false, 'data' => 'No product IDs provided.'));
    }
    $url = 'http://localhost:8085/api/partnerCentral/wordpress/deactivateWordpressProduct'; // External API URL

    $dataArray = array_map(function($product_id) {
        return (string)$product_id;
    }, $product_ids);

    $response = wp_remote_post($url, array(
        'body' => json_encode($dataArray),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt_token
        ),
    ));

    if (is_wp_error($response)) {
        error_log("Error posting to API: " . $response->get_error_message());
        return json_encode(array('success' => false, 'data' => 'Error posting to API: ' . $response->get_error_message()));
    }
    $response_body = wp_remote_retrieve_body($response);
    error_log('Response body: ' . $response_body);  // Log the response body for debugging

    $body = json_decode($response_body, true);

    if (!$body) {
        error_log("Invalid response from API.");
        return json_encode(array('success' => false, 'data' => 'Invalid response from API.'));
    }

    if (isset($body['status_code']) && isset($body['message_status'])) {
        if($body['status_code']===200 && $body['message_status']==="Success"){
            return json_encode(array('success' => true, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
        }else{
            error_log("API returned error: " . $body['data']);
            return json_encode(array('success' => false, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
        }

    } else {
        error_log("Unknown error in API response.");
        return json_encode(array('success' => false, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
    }
}
add_action('wp_ajax_deactivate_product', 'handle_deactivate_product');
function handle_deactivate_product() {
    // Verify nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sync_product_nonce')) {
        wp_send_json_error('Nonce verification failed');
        return;
    }

    // Fetch product IDs from POST data
    $product_ids = $_POST['product_id'];

    if (empty($product_ids)) {
        wp_send_json_error(array('data' => 'No product IDs provided.'));
        return;
    }

    // Call your deactivate function
    $result = newplugin_deactivate_product($product_ids);

    // Send response back to JavaScript
    if ($result) {
        wp_send_json_success(array('data' => 'Product deactivated successfully.'));
    } else {
        wp_send_json_error(array('data' => 'Failed to deactivate product.'));
    }
}


function newplugin_activate_product($product_ids) {
    $user_data = $_SESSION['user_data'];
    $jwt_token = $user_data['pc_jwtToken'];

    if (empty($product_ids)) {
        error_log("No product IDs provided.");
        return json_encode(array('success' => false, 'data' => 'No product IDs provided.'));
    }
    $url = 'http://localhost:8085/api/partnerCentral/wordpress/activateWordpressProduct'; // External API URL

    $dataArray = array_map(function($product_id) {
        return (string)$product_id;
    }, $product_ids);

    $response = wp_remote_post($url, array(
        'body' => json_encode($dataArray),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt_token
        ),
    ));

    if (is_wp_error($response)) {
        error_log("Error posting to API: " . $response->get_error_message());
        return json_encode(array('success' => false, 'data' => 'Error posting to API: ' . $response->get_error_message()));
    }
    $response_body = wp_remote_retrieve_body($response);
    error_log('Response body: ' . $response_body);  // Log the response body for debugging

    $body = json_decode($response_body, true);

    if (!$body) {
        error_log("Invalid response from API.");
        return json_encode(array('success' => false, 'data' => 'Invalid response from API.'));
    }

    if (isset($body['status_code']) && isset($body['message_status'])) {
        if($body['status_code']===200 && $body['message_status']==="Success"){
            return json_encode(array('success' => true, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
        }else{
            error_log("API returned error: " . $body['data']);
            return json_encode(array('success' => false, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
        }

    } else {
        error_log("Unknown error in API response.");
        return json_encode(array('success' => false, 'data' => isset($body['data']) ? $body['data'] : 'Unknown error.'));
    }
}
add_action('wp_ajax_activate_product', 'handle_activate_product');
function handle_activate_product() {
    // Verify nonce for security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sync_product_nonce')) {
        wp_send_json_error('Nonce verification failed');
        return;
    }

    // Fetch product IDs from POST data
    $product_ids = $_POST['product_id'];

    if (empty($product_ids)) {
        wp_send_json_error(array('data' => 'No product IDs provided.'));
        return;
    }

    // Call your deactivate function
    $result = newplugin_activate_product($product_ids);

    // Send response back to JavaScript
    if ($result) {
        wp_send_json_success(array('data' => 'Product activated successfully.'));
    } else {
        wp_send_json_error(array('data' => 'Failed to activate product.'));
    }
}





//====================================================getcategories
function get_categories_from_external_api() {
    $jwt_token = "";
    $partnerID =0;
    if (isset($_SESSION['user_data'])) {
        $user_data = $_SESSION['user_data'];
        $jwt_token = $user_data['pc_jwtToken'];
        $partnerID = (int) $user_data['pc_vendor_id'];
    } else {
        echo 'User not logged in.';
    }

    $url = 'http://localhost:8085/api/partnerCentral/category/getAllCategoriesByVendor';

    $response = wp_remote_get( $url . '?partnerID=' . $partnerID, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $jwt_token,
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_error', 'Failed to fetch categories' );
    }

    $body = wp_remote_retrieve_body( $response );

    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'json_error', 'Failed to parse JSON' );
    }

    return $data;
}

add_action( 'wp_ajax_get_categories', 'get_categories_ajax_handler' );
function get_categories_ajax_handler() {
    check_ajax_referer( 'get_categories_nonce', 'security' );

    $categories = get_categories_from_external_api();

    if ( is_wp_error( $categories ) ) {
        wp_send_json_error( $categories->get_error_message() );
    } else {
        wp_send_json_success( $categories );
    }
}
?>