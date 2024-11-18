<?php
/*
Plugin Name: New Plugin
Description: A custom plugin for WooCommerce with a login page, dashboard, and another page.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if (!session_id()) {
    session_start();
}

// Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    // Include necessary files
    include_once plugin_dir_path( __FILE__ ) . 'includes/api-handler.php';

    // Enqueue styles
    function newplugin_enqueue_styles() {
        wp_enqueue_style( 'newplugin-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
    }
    add_action( 'wp_enqueue_scripts', 'newplugin_enqueue_styles' );

    // Add menu item in WooCommerce section
    function newplugin_add_menu_item() {
        add_submenu_page(
            'woocommerce',
            'New Plugin',
            'New Plugin',
            'manage_options',
            'newplugin',
            'newplugin_login_page'
        );
    }
    add_action( 'admin_menu', 'newplugin_add_menu_item' );

    // Login page callback
    function newplugin_login_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/login-page.php';
    }

    // Handle form submission
    function newplugin_handle_login() {
        if ( isset( $_POST['newplugin_login'] ) ) {
            $username = sanitize_text_field( $_POST['username'] );
            $password = sanitize_text_field( $_POST['password'] );

            $response = newplugin_api_login( $username, $password );
            $current_user = wp_get_current_user();

            if(isset($response->jwtToken)){
                    $partnerDetail = array(
                        'pcVendorCode' => $response->partnerId,
                        'vendorName' => $response->contactPersonName,
                        'vendorID' => $current_user->ID,
                    );
                    $identification_response = newplugin_api_user_identification($response->jwtToken, $partnerDetail);
                    if (isset($identification_response['status_code']) && isset($identification_response['message_status']) ) {
                        if($identification_response['status_code']===200 && $identification_response['message_status']==="Success"){


                            if ( $current_user->exists() ) {
                                $user_data = array(
                                    'pc_user_id' => $response->user_id,
                                    'pc_user_name' => $response->userName,
                                    'pc_email' => $response->email,
                                    'pc_partner_id' => $response->partnerId,
                                    'pc_temp_code' => $response->temp_code,
                                    'pc_contact_person_name' => $response->contactPersonName,
                                    'pc_roles' => $response->roles,
                                    'pc_vendor_id'=> $identification_response['data'],
                                    'pc_jwtToken' => $response->jwtToken,
                                    'ID' => $current_user->ID,
                                    'user_login' => $current_user->user_login,
                                    'user_email' => $current_user->user_email,
                                    'user_registered' => $current_user->user_registered,
                                    'display_name' => $current_user->display_name,
                                    'roles' => $current_user->roles,
                                );
                            }
                            $_SESSION['user_data'] = $user_data;

                            wp_redirect(admin_url('admin.php?page=newplugin_dashboard'));
                            exit;
                        }
                    } else {
                        echo '<script>
                        window.location.href = "' . admin_url('admin.php?page=newplugin') . '";
                </script>';
                        exit;
                    }
            }
            else {
                echo '<script>
                    window.location.href = "' . admin_url('admin.php?page=newplugin') . '";
            </script>';
                exit;
            }
        }
    }
    add_action( 'admin_post_newplugin_login', 'newplugin_handle_login' );

    // Dashboard page callback
    function newplugin_dashboard_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/dashboard-page.php';
    }

    // Other page callback
    function newplugin_other_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/other-page.php';
    }

    add_action('admin_menu', function() {
        add_submenu_page('newplugin', 'Dashboard', 'Dashboard', 'manage_options', 'newplugin_dashboard', 'newplugin_dashboard_page');
        add_submenu_page('newplugin', 'Product Details', 'Product Details', 'manage_options', 'newplugin_other', 'newplugin_other_page');
    });
}
//====================================================new test edit
function newplugin_enqueue_scripts() {
    wp_enqueue_script('jquery');
}

add_action('admin_enqueue_scripts', 'newplugin_enqueue_scripts');

//==============================================================
function newplugin_handle_logout() {
    // Destroy plugin session data
    if (isset($_SESSION['user_data'])) {
        unset($_SESSION['user_data']); // Remove plugin-specific session data
    }
    // Redirect to the login page
    wp_redirect(admin_url('admin.php?page=newplugin'));
    exit;
}
add_action('admin_post_newplugin_logout', 'newplugin_handle_logout');

function enqueue_sweetalert_assets() {
    wp_enqueue_script('sweetalert-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js', array('jquery'), null, true);
    wp_enqueue_style('sweetalert-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), null);
}
add_action('admin_enqueue_scripts', 'enqueue_sweetalert_assets');


add_action('wp_ajax_get_wordpress_products_by_vendor', 'handle_get_wordpress_products_by_vendor');

function handle_get_wordpress_products_by_vendor() {
    // Verify nonce for security
    check_ajax_referer('get_products_by_vendor_nonce', 'security');

    $vendor_id = isset($_POST['vendor_id']) ? sanitize_text_field($_POST['vendor_id']) : '';

    // Add JWT token if required
    if (!empty($vendor_id)) {
        $response = newplugin_getWordpressProductsByVendor($vendor_id);
        echo $response;
    } else {
        echo json_encode(array('success' => false, 'data' => 'Vendor ID is missing.'));
    }
    wp_die(); // Required to end AJAX execution properly
}

?>
