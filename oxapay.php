<?php
/**
 * Plugin Name: OxaPay Crypto Payment Gateway: Accept Bitcoin Payments
 * Plugin URI: https://app.oxapay.com/
 * Description: OxaPay provides a robust and secure platform for processing cryptocurrency payments, allowing businesses and developers to easily integrate crypto payments into their applications and websites.
 * Version: 2.0.0
 * Author: OxaPay.com
 * Author URI: https://oxapay.com/
 * Developer: OxaPay
 * Developer URI: https://app.oxapay.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WooCommerce\Admin
 */


defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';


 if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}

add_filter('woocommerce_payment_gateways', function ($plugins) {
	return array_merge([\Oxapay\Woocommerce\oxapayGateway::class], $plugins);
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
	$url = admin_url('admin.php?page=wc-settings&tab=checkout&section=OxaPay');
	return array_merge(['<a href="' . $url . '">' . __('Configure') . '</a>'], $links);
});

add_action('plugins_loaded', function () {
	return new \Oxapay\Woocommerce\oxapayGateway();
});

function oxapay_declare_cart_checkout_blocks_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
add_action('before_woocommerce_init', 'oxapay_declare_cart_checkout_blocks_compatibility');

add_action( 'woocommerce_blocks_loaded', 'oxapay_register_order_approval_payment_method_type' );

function oxapay_register_order_approval_payment_method_type() {
    require_once plugin_dir_path(__FILE__) . 'class-block.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {

            $payment_method_registry->register( new Oxapay_Gateway_Blocks );
        }
    );
}
