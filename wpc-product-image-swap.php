<?php
/*
Plugin Name: WPC Product Image Swap for WooCommerce
Plugin URI: https://wpclever.net/
Description: It presents products visually engagingly to customers by offering attention-drawing swapping effects for images of products on archive/shop pages.
Version: 1.1.3
Author: WPClever
Author URI: https://wpclever.net
Text Domain: wpc-product-image-swap
Domain Path: /languages/
Requires Plugins: woocommerce
Requires at least: 4.0
Tested up to: 6.6
WC requires at least: 3.0
WC tested up to: 9.2
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WPCIS_VERSION' ) && define( 'WPCIS_VERSION', '1.1.3' );
! defined( 'WPCIS_LITE' ) && define( 'WPCIS_LITE', __FILE__ );
! defined( 'WPCIS_FILE' ) && define( 'WPCIS_FILE', __FILE__ );
! defined( 'WPCIS_URI' ) && define( 'WPCIS_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCIS_DIR' ) && define( 'WPCIS_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'WPCIS_SUPPORT' ) && define( 'WPCIS_SUPPORT', 'https://wpclever.net/support?utm_source=support&utm_medium=wpcis&utm_campaign=wporg' );
! defined( 'WPCIS_REVIEWS' ) && define( 'WPCIS_REVIEWS', 'https://wordpress.org/support/plugin/wpc-product-image-swap/reviews/?filter=5' );
! defined( 'WPCIS_CHANGELOG' ) && define( 'WPCIS_CHANGELOG', 'https://wordpress.org/plugins/wpc-product-image-swap/#developers' );
! defined( 'WPCIS_DISCUSSION' ) && define( 'WPCIS_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-product-image-swap' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCIS_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpcis_init' ) ) {
	add_action( 'plugins_loaded', 'wpcis_init', 11 );

	function wpcis_init() {
		// load text-domain
		load_plugin_textdomain( 'wpc-product-image-swap', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpcis_notice_wc' );

			return null;
		}

		include_once 'includes/class-backend.php';
		include_once 'includes/class-frontend.php';
	}
}

if ( ! function_exists( 'wpcis_notice_wc' ) ) {
	function wpcis_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Product Image Swap</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
