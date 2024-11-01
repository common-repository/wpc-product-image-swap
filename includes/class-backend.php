<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wpcis_Backend' ) && class_exists( 'WC_Product' ) ) {
	class Wpcis_Backend {
		protected static $instance = null;
		protected static $settings = [];
		protected static $effects_in = [
			'animate__tada',
			'animate__pulse',
			'animate__rubberBand',
			'animate__swing',
			'animate__wobble',
			'animate__jello',
			'animate__heartBeat',
			'animate__fadeIn',
			'animate__fadeInDown',
			'animate__fadeInLeft',
			'animate__fadeInRight',
			'animate__fadeInUp',
			'animate__bounceIn',
			'animate__bounceInDown',
			'animate__bounceInLeft',
			'animate__bounceInRight',
			'animate__bounceInUp',
			'animate__flipInX',
			'animate__flipInY',
			'animate__rotateIn',
			'animate__rotateInDownLeft',
			'animate__rotateInDownRight',
			'animate__rotateInUpLeft',
			'animate__rotateInUpRight',
			'animate__rollIn',
			'animate__zoomIn',
			'animate__zoomInDown',
			'animate__zoomInLeft',
			'animate__zoomInRight',
			'animate__zoomInUp',
			'animate__slideInDown',
			'animate__slideInLeft',
			'animate__slideInRight',
			'animate__slideInUp',
		];
		protected static $effects_out = [
			'animate__fadeOut',
			'animate__fadeOutDown',
			'animate__fadeOutLeft',
			'animate__fadeOutRight',
			'animate__fadeOutUp',
			'animate__bounceOut',
			'animate__bounceOutDown',
			'animate__bounceOutLeft',
			'animate__bounceOutRight',
			'animate__bounceOutUp',
			'animate__flipOutX',
			'animate__flipOutY',
			'animate__rotateOut',
			'animate__rotateOutDownLeft',
			'animate__rotateOutDownRight',
			'animate__rotateOutUpLeft',
			'animate__rotateOutUpRight',
			'animate__rollOut',
			'animate__zoomOut',
			'animate__zoomOutLeft',
			'animate__zoomOutRight',
			'animate__zoomOutUp',
			'animate__slideOutDown',
			'animate__slideOutLeft',
			'animate__slideOutRight',
			'animate__slideOutUp',
		];

		protected static $swap_df = [
			'effect_in'  => 'animate__flipInX',
			'effect_out' => 'animate__flipOutX',
		];

		protected static $slider_df = [
			'animation'      => 'fade',
			'direction'      => 'horizontal',
			'slideshow'      => 'true',
			'slideshowSpeed' => 7000,
			'controlNav'     => 'true',
			'directionNav'   => 'true',
			'pausePlay'      => 'false',
			'pauseOnHover'   => 'true',
			'maxItems'       => 5
		];

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			self::$settings = (array) get_option( 'wpcis_settings', [] );

			// init
			add_action( 'init', [ $this, 'init' ] );

			// backend
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
			add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
			add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

			// product settings
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
			add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'process_product_meta' ] );
		}

		function init() {
			self::$effects_in  = apply_filters( 'wpcis_effects_in', self::$effects_in );
			self::$effects_out = apply_filters( 'wpcis_effects_out', self::$effects_out );
			self::$swap_df     = apply_filters( 'wpcis_swap_default', self::$swap_df );
			self::$slider_df   = apply_filters( 'wpcis_slider_default', self::$slider_df );
		}

		function enqueue_scripts( $hook ) {
			if ( apply_filters( 'wpcis_ignore_backend_scripts', false, $hook ) ) {
				return null;
			}

			// enqueue frontend scripts for preview
			if ( ! apply_filters( 'wpcis_ignore_backend_preview', false ) ) {
				wp_enqueue_style( 'animate', WPCIS_URI . 'assets/libs/animate/animate.css', [], WPCIS_VERSION );
				wp_enqueue_script( 'flexslider', plugins_url( '/assets/js/flexslider/jquery.flexslider.js', WC_PLUGIN_FILE ), [ 'jquery', ], WPCIS_VERSION, true );
				wp_enqueue_style( 'wpcis-frontend', WPCIS_URI . 'assets/css/frontend.css', [], WPCIS_VERSION );
			}

			wp_enqueue_style( 'wpcis-backend', WPCIS_URI . 'assets/css/backend.css', [ 'woocommerce_admin_styles' ], WPCIS_VERSION );
			wp_enqueue_script( 'wpcis-backend', WPCIS_URI . 'assets/js/backend.js', [
				'jquery',
				'wp-util'
			], WPCIS_VERSION, true );
			wp_localize_script( 'wpcis-backend', 'wpcis_vars', [
				'media_add_text' => esc_html__( 'Add image', 'wpc-product-image-swap' ),
				'media_title'    => esc_html__( 'Swap Images', 'wpc-product-image-swap' )
			] );
		}

		function register_settings() {
			// settings
			register_setting( 'wpcis_settings', 'wpcis_settings' );
		}

		function admin_menu() {
			add_submenu_page( 'wpclever', 'WPC Product Image Swap', 'Product Image Swap', 'manage_options', 'wpclever-wpcis', [
				$this,
				'admin_menu_content'
			] );
		}

		function admin_menu_content() {
			$active_tab = sanitize_key( $_GET['tab'] ?? 'settings' );
			?>
            <div class="wpclever_settings_page wrap">
                <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Product Image Swap', 'wpc-product-image-swap' ) . ' ' . esc_html( WPCIS_VERSION ) . ' ' . ( defined( 'WPCIS_PREMIUM' ) ? '<span class="premium" style="display: none">' . esc_html__( 'Premium', 'wpc-product-image-swap' ) . '</span>' : '' ); ?></h1>
                <div class="wpclever_settings_page_desc about-text">
                    <p>
						<?php printf( /* translators: %s is the stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-product-image-swap' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                        <br/>
                        <a href="<?php echo esc_url( WPCIS_REVIEWS ); ?>" target="_blank"><?php esc_html_e( 'Reviews', 'wpc-product-image-swap' ); ?></a> |
                        <a href="<?php echo esc_url( WPCIS_CHANGELOG ); ?>" target="_blank"><?php esc_html_e( 'Changelog', 'wpc-product-image-swap' ); ?></a> |
                        <a href="<?php echo esc_url( WPCIS_DISCUSSION ); ?>" target="_blank"><?php esc_html_e( 'Discussion', 'wpc-product-image-swap' ); ?></a>
                    </p>
                </div>
				<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e( 'Settings updated.', 'wpc-product-image-swap' ); ?></p>
                    </div>
				<?php } ?>
                <div class="wpclever_settings_page_nav">
                    <h2 class="nav-tab-wrapper">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcis&tab=settings' ) ); ?>" class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'Settings', 'wpc-product-image-swap' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcis&tab=premium' ) ); ?>" class="<?php echo esc_attr( $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>" style="color: #c9356e">
							<?php esc_html_e( 'Premium Version', 'wpc-product-image-swap' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>" class="nav-tab">
							<?php esc_html_e( 'Essential Kit', 'wpc-product-image-swap' ); ?>
                        </a>
                    </h2>
                </div>
                <div class="wpclever_settings_page_content">
					<?php if ( $active_tab === 'settings' ) {
						$mobile = self::get_setting( 'mobile', 'enable' );
						?>
                        <form method="post" action="options.php">
                            <table class="form-table wpcis-settings">
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( 'General', 'wpc-product-image-swap' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Mobile devices', 'wpc-product-image-swap' ); ?></th>
                                    <td>
                                        <label> <select name="wpcis_settings[mobile]">
                                                <option value="enable" <?php selected( $mobile, 'enable' ); ?>><?php esc_html_e( 'Enable', 'wpc-product-image-swap' ); ?></option>
                                                <option value="disable" <?php selected( $mobile, 'disable' ); ?>><?php esc_html_e( 'Disable', 'wpc-product-image-swap' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Enable/disable swapping effects on mobile devices.', 'wpc-product-image-swap' ); ?></span>
                                    </td>
                                </tr>
								<?php self::style_settings(); ?>
                                <tr class="submit">
                                    <th colspan="2">
										<?php settings_fields( 'wpcis_settings' ); ?><?php submit_button(); ?>
                                    </th>
                                </tr>
                            </table>
                        </form>
					<?php } elseif ( $active_tab == 'premium' ) { ?>
                        <div class="wpclever_settings_page_content_text">
                            <p>Get the Premium Version just $29!
                                <a href="https://wpclever.net/downloads/wpc-product-image-swap/?utm_source=pro&utm_medium=wpcis&utm_campaign=wporg" target="_blank">https://wpclever.net/downloads/wpc-product-image-swap/</a>
                            </p>
                            <p><strong>Extra features for Premium Version:</strong></p>
                            <ul style="margin-bottom: 0">
                                <li>- Use slider style for images.</li>
                                <li>- Get the lifetime update & premium support.</li>
                            </ul>
                        </div>
					<?php } ?>
                </div><!-- /.wpclever_settings_page_content -->
                <div class="wpclever_settings_page_suggestion">
                    <div class="wpclever_settings_page_suggestion_label">
                        <span class="dashicons dashicons-yes-alt"></span> Suggestion
                    </div>
                    <div class="wpclever_settings_page_suggestion_content">
                        <div>
                            To display custom engaging real-time messages on any wished positions, please install
                            <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC Smart Messages</a> plugin. It's free!
                        </div>
                        <div>
                            Wanna save your precious time working on variations? Try our brand-new free plugin
                            <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC Variation Bulk Editor</a> and
                            <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC Variation Duplicator</a>.
                        </div>
                    </div>
                </div>
            </div>
			<?php
		}

		function style_settings( $product_id = 0 ) {
			$type        = self::get_setting( 'type', 'swap' );
			$swap        = self::get_swap();
			$slider      = self::get_slider();
			$name_type   = 'wpcis_settings[type]';
			$name_swap   = 'wpcis_settings[swap]';
			$name_slider = 'wpcis_settings[slider]';

			if ( $product_id ) {
				$type        = get_post_meta( $product_id, 'wpcis_type', true );
				$swap        = self::get_swap( $product_id );
				$slider      = self::get_slider( $product_id );
				$name_type   = 'wpcis_type';
				$name_swap   = 'wpcis_swap';
				$name_slider = 'wpcis_slider';
			}
			?>
            <tr>
                <th><?php esc_html_e( 'Style', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_type ); ?>" class="wpcis_type">
							<?php if ( $product_id ) {
								echo '<option value="">' . esc_html__( 'Default', 'wpc-product-image-swap' ) . '</option>';
							} ?>
                            <option value="swap" <?php selected( $type, 'swap' ); ?>><?php esc_html_e( 'Basic', 'wpc-product-image-swap' ); ?></option>
                            <option value="slider" <?php selected( $type, 'slider' ); ?>><?php esc_html_e( 'Slider', 'wpc-product-image-swap' ); ?></option>
                        </select> </label>
                    <div class="description">
                        <ul>
                            <li><?php esc_html_e( 'Basic: swap between the main featured image and the first image in the gallery', 'wpc-product-image-swap' ); ?></li>
                            <li><?php esc_html_e( 'Slider: swap among featured image & a set of pictures (all or some gallery images, or a custom set uploaded by user)', 'wpc-product-image-swap' ); ?>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
			<?php if ( ! apply_filters( 'wpcis_ignore_backend_preview', false ) ) { ?>
                <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_swap">
                    <th><?php esc_html_e( 'Preview', 'wpc-product-image-swap' ); ?></th>
                    <td>
                        <div class="wpcis-preview wpcis-preview-wrap">
                            <div class="wpcis-preview-inner">
                                <ul class="products">
                                    <li class="product wpcis wpcis-swap">
                                        <img src="<?php echo esc_url( WPCIS_URI . 'assets/images/preview_01.jpg' ); ?>" width="300" height="300" alt=""/>
                                        <img src="<?php echo esc_url( WPCIS_URI . 'assets/images/preview_02.jpg' ); ?>" width="300" height="300" class="wpcis-swap-image animate__animated" alt=""/>
                                        <span class="wpcis-swap-data" data-in="<?php echo esc_attr( $swap['effect_in'] ); ?>" data-out="<?php echo esc_attr( $swap['effect_out'] ); ?>"></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                    <th><?php esc_html_e( 'Preview', 'wpc-product-image-swap' ); ?></th>
                    <td>
                        <div class="wpcis-preview wpcis-preview-slider">
                            <div class="wpcis-preview-inner">
                                <ul class="products">
                                    <li class="product wpcis wpcis-slider"></li>
                                </ul>
                            </div>
                        </div>
                        <script type="text/template" id="tmpl-wpcis-slider">
                            <div class="wpcis-slider-slides">
                                <ul class="slides">
                                    <li>
                                        <img src="<?php echo esc_url( WPCIS_URI . 'assets/images/preview_01.jpg' ); ?>" width="300" height="300" class="wpcis-slider-image" alt=""/>
                                    </li>
                                    <li>
                                        <img src="<?php echo esc_url( WPCIS_URI . 'assets/images/preview_02.jpg' ); ?>" width="300" height="300" class="wpcis-slider-image" alt=""/>
                                    </li>
                                    <li>
                                        <img src="<?php echo esc_url( WPCIS_URI . 'assets/images/preview_03.jpg' ); ?>" width="300" height="300" class="wpcis-slider-image" alt=""/>
                                    </li>
                                </ul>
                            </div>
                        </script>
                    </td>
                </tr>
			<?php } ?>
            <tr class="heading wpcis_hide_if_type wpcis_tr_show_if_type_swap">
                <th>
					<?php esc_html_e( 'Basic configuration', 'wpc-product-image-swap' ); ?>
                </th>
                <td>
                    Read more about Animate.css options <a href="https://animate.style/" target="_blank">here</a>.
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_swap">
                <th><?php esc_html_e( 'IN animation', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_swap ); ?>[effect_in]" class="wpcis_effect_in">
							<?php foreach ( self::$effects_in as $effect ) {
								echo '<option value="' . esc_attr( $effect ) . '" ' . ( selected( $effect, $swap['effect_in'], false ) ) . '>' . esc_html( str_replace( 'animate__', '', $effect ) ) . '</option>';
							} ?>
                        </select> </label>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_swap">
                <th><?php esc_html_e( 'OUT animation', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_swap ); ?>[effect_out]" class="wpcis_effect_out">
							<?php foreach ( self::$effects_out as $effect ) {
								echo '<option value="' . esc_attr( $effect ) . '" ' . ( selected( $effect, $swap['effect_out'], false ) ) . '>' . esc_html( str_replace( 'animate__', '', $effect ) ) . '</option>';
							} ?>
                        </select> </label>
                </td>
            </tr>
            <tr class="heading wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th>
					<?php esc_html_e( 'Slider configuration', 'wpc-product-image-swap' ); ?>
                </th>
                <td>
                    Read more about Flexslider options <a href="https://woo.com/flexslider/" target="_blank">here</a>.
                    <p class="description" style="color: #c9356e">
                        * This feature only available on Premium Version. Click
                        <a href="https://wpclever.net/downloads/wpc-product-image-swap/?utm_source=pro&utm_medium=wpcis&utm_campaign=wporg" target="_blank">here</a> to buy, just $29!
                    </p>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Animation', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_slider ); ?>[animation]">
                            <option value="fade" <?php selected( $slider['animation'], 'fade' ); ?>><?php esc_html_e( 'fade', 'wpc-product-image-swap' ); ?></option>
                            <option value="slide" <?php selected( $slider['animation'], 'slide' ); ?>><?php esc_html_e( 'slide', 'wpc-product-image-swap' ); ?></option>
							<?php foreach ( self::$effects_in as $effect ) {
								echo '<option value="' . esc_attr( $effect ) . '" ' . ( selected( $effect, $slider['animation'], false ) ) . '>' . esc_html( str_replace( 'animate__', '', $effect ) ) . '</option>';
							} ?>
                        </select> </label>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Direction', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_slider ); ?>[direction]">
                            <option value="horizontal" <?php selected( $slider['direction'], 'horizontal' ); ?>><?php esc_html_e( 'horizontal', 'wpc-product-image-swap' ); ?></option>
                            <option value="vertical" <?php selected( $slider['direction'], 'vertical' ); ?>><?php esc_html_e( 'vertical', 'wpc-product-image-swap' ); ?></option>
                        </select> </label>
                    <span class="description"><?php esc_html_e( 'For "slide" animation only.', 'wpc-product-image-swap' ); ?></span>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Pagination buttons', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_slider ); ?>[controlNav]">
                            <option value="true" <?php selected( $slider['controlNav'], 'true' ); ?>><?php esc_html_e( 'yes', 'wpc-product-image-swap' ); ?></option>
                            <option value="false" <?php selected( $slider['controlNav'], 'false' ); ?>><?php esc_html_e( 'no', 'wpc-product-image-swap' ); ?></option>
                        </select> </label>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Navigation arrows', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_slider ); ?>[directionNav]">
                            <option value="true" <?php selected( $slider['directionNav'], 'true' ); ?>><?php esc_html_e( 'yes', 'wpc-product-image-swap' ); ?></option>
                            <option value="false" <?php selected( $slider['directionNav'], 'false' ); ?>><?php esc_html_e( 'no', 'wpc-product-image-swap' ); ?></option>
                        </select> </label>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Pause/Play buttons', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_slider ); ?>[pausePlay]">
                            <option value="true" <?php selected( $slider['pausePlay'], 'true' ); ?>><?php esc_html_e( 'yes', 'wpc-product-image-swap' ); ?></option>
                            <option value="false" <?php selected( $slider['pausePlay'], 'false' ); ?>><?php esc_html_e( 'no', 'wpc-product-image-swap' ); ?></option>
                        </select> </label>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Pause on Hover', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_slider ); ?>[pauseOnHover]">
                            <option value="true" <?php selected( $slider['pauseOnHover'], 'true' ); ?>><?php esc_html_e( 'yes', 'wpc-product-image-swap' ); ?></option>
                            <option value="false" <?php selected( $slider['pauseOnHover'], 'false' ); ?>><?php esc_html_e( 'no', 'wpc-product-image-swap' ); ?></option>
                        </select> </label>
                    <span class="description"><?php esc_html_e( 'Pause the slideshow when hovering over slider, then resume when no longer hovering.', 'wpc-product-image-swap' ); ?></span>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Autoplay', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label> <select name="<?php echo esc_attr( $name_slider ); ?>[slideshow]">
                            <option value="true" <?php selected( $slider['slideshow'], 'true' ); ?>><?php esc_html_e( 'yes', 'wpc-product-image-swap' ); ?></option>
                            <option value="false" <?php selected( $slider['slideshow'], 'false' ); ?>><?php esc_html_e( 'no', 'wpc-product-image-swap' ); ?></option>
                        </select> </label>
                    <span class="description"><?php esc_html_e( 'Animate slider automatically.', 'wpc-product-image-swap' ); ?></span>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Speed', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label>
                        <input type="number" class="text small-text" step="1" min="0" name="<?php echo esc_attr( $name_slider ); ?>[slideshowSpeed]" value="<?php echo esc_attr( $slider['slideshowSpeed'] ); ?>"/>
                    </label>
                    <span class="description"><?php esc_html_e( 'The speed of the slideshow cycling, in milliseconds.', 'wpc-product-image-swap' ); ?></span>
                </td>
            </tr>
            <tr class="wpcis_hide_if_type wpcis_tr_show_if_type_slider">
                <th><?php esc_html_e( 'Limit', 'wpc-product-image-swap' ); ?></th>
                <td>
                    <label>
                        <input type="number" class="text small-text" min="2" max="100" step="1" name="<?php echo esc_attr( $name_slider ); ?>[maxItems]" value="<?php echo esc_attr( absint( $slider['maxItems'] ) ); ?>"/>
                    </label>
                    <span class="description"><?php esc_html_e( 'Limit the number of images to be swapped.', 'wpc-product-image-swap' ); ?></span>
                </td>
            </tr>
			<?php
		}

		function action_links( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WPCIS_FILE );
			}

			if ( $plugin === $file ) {
				$settings             = '<a href="' . admin_url( 'admin.php?page=wpclever-wpcis&tab=settings' ) . '">' . esc_html__( 'Settings', 'wpc-product-image-swap' ) . '</a>';
				$links['wpc-premium'] = '<a href="' . admin_url( 'admin.php?page=wpclever-wpcis&tab=premium' ) . '">' . esc_html__( 'Premium Version', 'wpc-product-image-swap' ) . '</a>';
				array_unshift( $links, $settings );
			}

			return (array) $links;
		}

		function row_meta( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WPCIS_FILE );
			}

			if ( $plugin === $file ) {
				$row_meta = [
					'support' => '<a href="' . esc_url( WPCIS_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-product-image-swap' ) . '</a>',
				];

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		function product_data_tabs( $tabs ) {
			$tabs['wpcis'] = [
				'label'  => esc_html__( 'Product Image Swap', 'wpc-product-image-swap' ),
				'target' => 'wpcis_settings',
			];

			return $tabs;
		}

		function product_data_panels() {
			global $post, $thepostid, $product_object;

			if ( $product_object instanceof WC_Product ) {
				$product_id = $product_object->get_id();
			} elseif ( is_numeric( $thepostid ) ) {
				$product_id = $thepostid;
			} elseif ( $post instanceof WP_Post ) {
				$product_id = $post->ID;
			} else {
				$product_id = 0;
			}

			if ( ! $product_id ) {
				?>
                <div id='wpcis_settings' class='panel woocommerce_options_panel wpcis_table'>
                    <p style="padding: 0 12px; color: #c9356e"><?php esc_html_e( 'Product wasn\'t returned.', 'wpc-product-image-swap' ); ?></p>
                </div>
				<?php
				return;
			}
			?>
            <div id='wpcis_settings' class='panel woocommerce_options_panel wpcis_table wpcis-settings'>
                <table>
                    <tr>
                        <th><?php esc_html_e( 'Images', 'wpc-product-image-swap' ); ?></th>
                        <td>
							<?php
							$images = get_post_meta( $product_id, 'wpcis_images', true );

							echo '<div class="wpcis-images-form" data-id="' . esc_attr( $product_id ) . '">';
							echo '<input type="hidden" class="wpcis-images-ids" name="wpcis_images" value="' . esc_attr( $images ) . '">';
							echo '<ul class="wpcis-images">';

							if ( ! empty( $images ) ) {
								foreach ( explode( ',', $images ) as $attachment_id ) {
									if ( $attachment = wp_get_attachment_image_src( $attachment_id, [ 40, 40 ] ) ) {
										echo '<li class="wpcis-image" data-id="' . esc_attr( $attachment_id ) . '"><span class="wpcis-image-thumb"><a class="wpcis-image-remove" href="#"></a><img src="' . esc_url( $attachment[0] ) . '" width="40" height="40" alt=""/></span></li>';
									}
								}
							}

							echo '</ul>';
							echo '<a href="#" class="wpcis-add-images button" rel="' . esc_attr( $product_id ) . '">' . esc_html__( '+ Add Images', 'wpc-product-image-swap' ) . '</a>';
							echo '</div>';

							echo '<p class="description">' . esc_html__( 'Upload images here to be swapped instead of gallery images.', 'wpc-product-image-swap' ) . '</p>';
							?>
                        </td>
                    </tr>
					<?php self::style_settings( $product_id ); ?>
                </table>
            </div>
			<?php
		}

		function process_product_meta( $post_id ) {
			if ( isset( $_POST['wpcis_images'] ) ) {
				update_post_meta( $post_id, 'wpcis_images', sanitize_text_field( $_POST['wpcis_images'] ) );
			}

			if ( isset( $_POST['wpcis_type'] ) ) {
				update_post_meta( $post_id, 'wpcis_type', sanitize_text_field( $_POST['wpcis_type'] ) );
			}

			if ( isset( $_POST['wpcis_swap'] ) ) {
				update_post_meta( $post_id, 'wpcis_swap', self::sanitize_array( $_POST['wpcis_swap'] ) );
			}

			if ( isset( $_POST['wpcis_slider'] ) ) {
				update_post_meta( $post_id, 'wpcis_slider', self::sanitize_array( $_POST['wpcis_slider'] ) );
			}
		}

		function sanitize_array( $arr ) {
			foreach ( (array) $arr as $k => $v ) {
				if ( is_array( $v ) ) {
					$arr[ $k ] = self::sanitize_array( $v );
				} else {
					$arr[ $k ] = sanitize_text_field( $v );
				}
			}

			return $arr;
		}

		public static function get_swap( $product_id = 0 ) {
			$swap = array_merge( self::$swap_df, self::get_setting( 'swap', [] ) );

			if ( $product_id ) {
				$type = get_post_meta( $product_id, 'wpcis_type', true );

				if ( $type === 'swap' ) {
					$swap = array_merge( self::$swap_df, get_post_meta( $product_id, 'wpcis_swap', true ) ?: [] );
				}
			}

			return apply_filters( 'wpcis_get_swap', $swap, $product_id );
		}

		public static function get_slider( $product_id = 0 ) {
			$slider = array_merge( self::$slider_df, self::get_setting( 'slider', [] ) );

			if ( $product_id ) {
				$type = get_post_meta( $product_id, 'wpcis_type', true );

				if ( $type === 'slider' ) {
					$slider = array_merge( self::$slider_df, get_post_meta( $product_id, 'wpcis_slider', true ) ?: [] );
				}
			}

			foreach ( $slider as $k => $v ) {
				if ( $v === 'true' || $v === 'yes' ) {
					$slider[ $k ] = true;
				}

				if ( $v === 'false' || $v === 'no' ) {
					$slider[ $k ] = false;
				}

				if ( is_numeric( $v ) ) {
					$slider[ $k ] = absint( $v );
				}
			}

			return apply_filters( 'wpcis_get_slider', $slider, $product_id );
		}

		public static function get_settings() {
			return apply_filters( 'wpcis_get_settings', self::$settings );
		}

		public static function get_setting( $name, $default = false ) {
			if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
				$setting = self::$settings[ $name ];
			} else {
				$setting = get_option( 'wpcis_' . $name, $default );
			}

			return apply_filters( 'wpcis_get_setting', $setting, $name, $default );
		}
	}

	function Wpcis_Backend() {
		return Wpcis_Backend::instance();
	}

	Wpcis_Backend();
}

