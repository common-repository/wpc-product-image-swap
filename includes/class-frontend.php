<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wpcis_Frontend' ) && class_exists( 'WC_Product' ) ) {
	class Wpcis_Frontend {
		protected static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			// frontend scripts
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

			// add images after main image
			add_filter( 'woocommerce_post_class', [ $this, 'product_class' ], 99, 2 );
			add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'add_images' ], 11 );
		}

		function enqueue_scripts() {
			if ( ! self::is_enable() ) {
				return;
			}

			wp_enqueue_style( 'animate', WPCIS_URI . 'assets/libs/animate/animate.css', [], WPCIS_VERSION );

			wp_enqueue_style( 'wpcis-frontend', WPCIS_URI . 'assets/css/frontend.css', [], WPCIS_VERSION );
			wp_enqueue_script( 'wpcis-frontend', WPCIS_URI . 'assets/js/frontend.js', [ 'jquery' ], WPCIS_VERSION, true );
			wp_localize_script( 'wpcis-frontend', 'wpcis_vars', [
					'product_selector' => apply_filters( 'wpcis_product_selector', 'li.wpcis-swap' ),
				]
			);
		}

		function product_class( $classes, $product ) {
			if ( ! self::is_enable() ) {
				return $classes;
			}

			$type = self::get_type( $product );

			if ( ( ( $images = $product->get_gallery_image_ids() ) && is_array( $images ) && ! empty( $images ) ) || ( ( $images = get_post_meta( $product->get_id(), 'wpcis_images', true ) ) && ! empty( $images ) ) ) {
				$classes[] = 'wpcis wpcis-' . $type;
			}

			return $classes;
		}

		function add_images() {
			if ( ! self::is_enable() ) {
				return;
			}

			global $product;

			if ( ! $product ) {
				return;
			}

			$product_id = $product->get_id();
			$size       = apply_filters( 'single_product_archive_thumbnail_size', 'woocommerce_thumbnail' );
			$size_class = $size;

			if ( is_array( $size_class ) ) {
				$size_class = implode( 'x', $size_class );
			}

			// swap
			if ( ( $images = get_post_meta( $product_id, 'wpcis_images', true ) ) && ! empty( $images ) ) {
				$images_arr = array_map( 'absint', explode( ',', $images ) );
				$image_id   = $images_arr[0];
			} elseif ( ( $images = $product->get_gallery_image_ids() ) && is_array( $images ) && ! empty( $images ) ) {
				$image_id = $images[0];
			} else {
				$image_id = 0;
			}

			if ( $image_id ) {
				$attr = [ 'class' => 'attachment-' . $size_class . ' size-' . $size_class . ' wpcis-swap-image animate__animated' ];
				$swap = Wpcis_Backend()::get_swap( $product_id );
				echo wp_get_attachment_image( $image_id, $size, false, $attr );
				echo '<span class="wpcis-swap-data" data-in="' . esc_attr( $swap['effect_in'] ) . '" data-out="' . esc_attr( $swap['effect_out'] ) . '"></span>';

			}
		}

		function get_type( $product ) {
			if ( is_a( $product, 'WC_Product' ) ) {
				$product_id = $product->get_id();
			} elseif ( is_numeric( $product ) ) {
				$product_id = $product;
			} else {
				$product_id = 0;
			}

			$type = 'swap';

			return apply_filters( 'wpcis_get_type', $type, $product_id );
		}

		public static function is_enable() {
			$enable = true;

			if ( ( Wpcis_Backend()::get_setting( 'mobile', 'enable' ) === 'disable' ) && wp_is_mobile() ) {
				$enable = false;
			}

			return apply_filters( 'wpcis_is_enable', $enable );
		}
	}

	function Wpcis_Frontend() {
		return Wpcis_Frontend::instance();
	}

	Wpcis_Frontend();
}
