<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package     DPSFIWU
 * @subpackage  DPSFIWU/admin
 * @copyright   Copyright (c) DPS.MEDIA JSC
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @package     DPSFIWU
 * @subpackage  DPSFIWU/admin
 */
class DPSFIWU_Admin {

	/**
	 * Image meta key for saving image URL.
	 *
	 * @var string
	 */
	private $image_meta_url = '_dpsfiwu_url';

	/**
	 * Image meta key for saving image alt.
	 *
	 * @var string
	 */
	private $image_meta_alt = '_dpsfiwu_alt';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'dpsfiwu_add_metabox' ), 10, 2 );
			add_action( 'save_post', array( $this, 'dpsfiwu_save_image_url_data' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_action( 'admin_menu', array( $this, 'dpsfiwu_add_options_page' ) );
			add_action( 'admin_init', array( $this, 'dpsfiwu_settings_init' ) );
			// Add & Save Product Variation Featured image by URL.
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'dpsfiwu_add_product_variation_image_selector' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'dpsfiwu_save_product_variation_image' ), 10, 2 );

			// Handle migration from "Featured Image by URL" plugin.
			add_action( 'admin_notices', array( $this, 'maybe_display_migrate_from_fibu_notices' ) );
			add_action( 'admin_post_dpsfiwu_migrate_from_fibu', array( $this, 'handle_migration_from_fibu' ) );
			add_action( 'admin_post_dpsfiwu_migration_notice_dismissed', array( $this, 'dismiss_fibu_migration_notice' ) );
			add_filter( 'removable_query_args', array( $this, 'removable_query_args' ) );
		}
	}

	/**
	 * Add Meta box for Featured Image with URL.
	 *
	 * @since 1.0
	 * @param string $post_type Post type.
	 * @param object $post      Post object.
	 * @return void
	 */
	public function dpsfiwu_add_metabox( $post_type, $post ) {
		$options            = get_option( DPSFIWU_OPTIONS );
		$disabled_posttypes = isset( $options['dpsfiwu_disabled_posttypes'] ) ? $options['dpsfiwu_disabled_posttypes'] : array();

		if ( in_array( $post_type, $disabled_posttypes, true ) ) {
			return;
		}

		add_meta_box(
			'dpsfiwu_metabox',
			__( 'Featured Image with URL', 'dpsfiwu' ),
			array( $this, 'dpsfiwu_render_metabox' ),
			$this->dpsfiwu_get_posttypes(),
			'side',
			'low'
		);

		add_meta_box(
			'dpsfiwu_wcgallary_metabox',
			__( 'Product gallery by URLs', 'dpsfiwu' ),
			array( $this, 'dpsfiwu_render_wcgallary_metabox' ),
			'product',
			'side',
			'low'
		);
	}

	/**
	 * Render Meta box for Featured Image with URL.
	 *
	 * @since 1.0
	 * @param object $post Post object.
	 * @return void
	 */
	public function dpsfiwu_render_metabox( $post ) {
		$image_meta = $this->dpsfiwu_get_image_meta( $post->ID );

		// Include Metabox Template.
		include DPSFIWU_PLUGIN_DIR . 'templates/dpsfiwu-metabox.php';
	}

	/**
	 * Render Meta box for Product gallary by URLs
	 *
	 * @since 1.0
	 * @param object $post Post object.
	 * @return void
	 */
	public function dpsfiwu_render_wcgallary_metabox( $post ) {
		// Include WC Gallary Metabox Template.
		include DPSFIWU_PLUGIN_DIR . 'templates/dpsfiwu-wcgallary-metabox.php';
	}

	/**
	 * Load Admin Styles.
	 *
	 * Enqueues the required admin styles.
	 *
	 * @since 1.0
	 * @param string $hook Page hook.
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {
		$css_dir = DPSFIWU_PLUGIN_URL . 'assets/css/';
		wp_enqueue_style( 'dpsfiwu-admin', $css_dir . 'dpsfiwu-admin.css', array(), '1.0.3', '' );
	}

	/**
	 * Load Admin Scripts.
	 *
	 * Enqueues the required admin scripts.
	 *
	 * @since 1.0
	 * @param string $hook Page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		$js_dir = DPSFIWU_PLUGIN_URL . 'assets/js/';
		wp_register_script( 'dpsfiwu-admin', $js_dir . 'dpsfiwu-admin.js', array( 'jquery' ), DPSFIWU_VERSION, true );
		$strings = array(
			'invalid_image_url' => __( 'Error in Image URL', 'dpsfiwu' ),
		);
		wp_localize_script( 'dpsfiwu-admin', 'dpsfiwujs', $strings );
		wp_enqueue_script( 'dpsfiwu-admin' );
	}

	/**
	 * Add Meta box for Featured Image with URL.
	 *
	 * @since 1.0
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 * @return void
	 */
	public function dpsfiwu_save_image_url_data( $post_id, $post ) {
		$cap = 'page' === $post->post_type ? 'edit_page' : 'edit_post';
		if ( ! current_user_can( $cap, $post_id ) || ! post_type_supports( $post->post_type, 'thumbnail' ) || defined( 'DOING_AUTOSAVE' ) ) {
			return;
		}

		if ( isset( $_POST['dpsfiwu_url'] ) && isset( $_POST['dpsfiwu_img_url_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['dpsfiwu_img_url_nonce'] ), 'dpsfiwu_img_url_nonce_action' ) ) {
			global $dpsfiwu;
			// Update Featured Image URL.
			$image_url = isset( $_POST['dpsfiwu_url'] ) ? esc_url_raw( wp_unslash( $_POST['dpsfiwu_url'] ) ) : '';
			$image_alt = isset( $_POST['dpsfiwu_alt'] ) ? sanitize_text_field( wp_unslash( $_POST['dpsfiwu_alt'] ) ) : '';
			$og_title = isset( $_POST['dpsfiwu_og_title'] ) ? sanitize_text_field( wp_unslash( $_POST['dpsfiwu_og_title'] ) ) : '';
			$og_description = isset( $_POST['dpsfiwu_og_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['dpsfiwu_og_description'] ) ) : '';

			if ( ! empty( $image_url ) ) {
				if ( 'product' === get_post_type( $post_id ) ) {
					$img_url = get_post_meta( $post_id, $this->image_meta_url, true );
					if ( is_array( $img_url ) && isset( $img_url['img_url'] ) && $image_url === $img_url['img_url'] ) {
							$image_url = array(
								'img_url' => $image_url,
								'width'   => $img_url['width'],
								'height'  => $img_url['height'],
								'img_alt' => $image_alt,
								'og_title' => $og_title,
								'og_description' => $og_description,
							);
					} else {
						$imagesize = $dpsfiwu->common->get_image_sizes( $image_url );
						$image_url = array(
							'img_url' => $image_url,
							'width'   => isset( $imagesize[0] ) ? $imagesize[0] : '',
							'height'  => isset( $imagesize[1] ) ? $imagesize[1] : '',
							'img_alt' => $image_alt,
							'og_title' => $og_title,
							'og_description' => $og_description,
						);
					}
				} else {
					// For non-products, store as array if SEO data exists
					$existing_url = get_post_meta( $post_id, $this->image_meta_url, true );
					if ( ! empty( $og_title ) || ! empty( $og_description ) || ! empty( $image_alt ) ) {
						$image_url = array(
							'img_url' => $image_url,
							'img_alt' => $image_alt,
							'og_title' => $og_title,
							'og_description' => $og_description,
						);
					}
				}

				update_post_meta( $post_id, $this->image_meta_url, $image_url );

				// Save alt text separately for backward compatibility
				if ( $image_alt ) {
					update_post_meta( $post_id, $this->image_meta_alt, $image_alt );
				}

				// Save OG data separately for easier access
				if ( $og_title ) {
					update_post_meta( $post_id, '_dpsfiwu_og_title', $og_title );
				} else {
					delete_post_meta( $post_id, '_dpsfiwu_og_title' );
				}

				if ( $og_description ) {
					update_post_meta( $post_id, '_dpsfiwu_og_description', $og_description );
				} else {
					delete_post_meta( $post_id, '_dpsfiwu_og_description' );
				}
			} else {
				delete_post_meta( $post_id, $this->image_meta_url );
				delete_post_meta( $post_id, $this->image_meta_alt );
				delete_post_meta( $post_id, '_dpsfiwu_og_title' );
				delete_post_meta( $post_id, '_dpsfiwu_og_description' );
			}
		}

		if ( isset( $_POST['dpsfiwu_wcgallary'] ) && isset( $_POST['dpsfiwu_wcgallary_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['dpsfiwu_wcgallary_nonce'] ), 'dpsfiwu_wcgallary_nonce_action' ) ) {
			global $dpsfiwu;
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslash and Sanitization already done in dpsfiwu_sanitize function.
			$dpsfiwu_wcgallary = is_array( $_POST['dpsfiwu_wcgallary'] ) ? $this->dpsfiwu_sanitize( $_POST['dpsfiwu_wcgallary'] ) : array();

			if ( empty( $dpsfiwu_wcgallary ) || 'product' !== $post->post_type ) {
				return;
			}

			$old_images = $dpsfiwu->common->dpsfiwu_get_wcgallary_meta( $post_id );
			if ( ! empty( $old_images ) ) {
				foreach ( $old_images as $key => $value ) {
					$old_images[ $value['url'] ] = $value;
				}
			}

			$gallary_images = array();
			if ( ! empty( $dpsfiwu_wcgallary ) ) {
				foreach ( $dpsfiwu_wcgallary as $dpsfiwu_gallary ) {
					if ( isset( $dpsfiwu_gallary['url'] ) && '' !== $dpsfiwu_gallary['url'] ) {
						$gallary_image        = array();
						$gallary_image['url'] = $dpsfiwu_gallary['url'];

						if ( isset( $old_images[ $gallary_image['url'] ]['width'] ) && '' !== $old_images[ $gallary_image['url'] ]['width'] ) {
							$gallary_image['width']  = isset( $old_images[ $gallary_image['url'] ]['width'] ) ? $old_images[ $gallary_image['url'] ]['width'] : '';
							$gallary_image['height'] = isset( $old_images[ $gallary_image['url'] ]['height'] ) ? $old_images[ $gallary_image['url'] ]['height'] : '';

						} else {
							$imagesizes              = $dpsfiwu->common->get_image_sizes( $dpsfiwu_gallary['url'] );
							$gallary_image['width']  = isset( $imagesizes[0] ) ? $imagesizes[0] : '';
							$gallary_image['height'] = isset( $imagesizes[1] ) ? $imagesizes[1] : '';
						}

						$gallary_images[] = $gallary_image;
					}
				}
			}

			if ( ! empty( $gallary_images ) ) {
				update_post_meta( $post_id, DPSFIWU_WCGALLARY, $gallary_images );
			} else {
				delete_post_meta( $post_id, DPSFIWU_WCGALLARY );
			}
		}
	}

	/**
	 * Get Image metadata by post_id
	 *
	 * @since 1.0
	 * @param int  $post_id        Post ID.
	 * @param bool $is_single_page Is single page? If true then return image size also.
	 * @return array
	 */
	public function dpsfiwu_get_image_meta( $post_id, $is_single_page = false ) {
		global $dpsfiwu;
		$image_meta = array();
		$img_url    = get_post_meta( $post_id, $this->image_meta_url, true );
		$img_alt    = get_post_meta( $post_id, $this->image_meta_alt, true );

		// Compatibility with "Featured Image by URL" plugin.
		if ( empty( $img_url ) ) {
			$old_img_url = get_post_meta( $post_id, '_knawatfibu_url', true );
			if ( ! empty( $old_img_url ) ) {
				$img_url     = $old_img_url;
				$old_img_alt = get_post_meta( $post_id, '_knawatfibu_alt', true );
				update_post_meta( $post_id, $this->image_meta_url, $old_img_url );

				if ( ! empty( $old_img_alt ) && empty( $img_alt ) ) {
					$img_alt = $old_img_alt;
					update_post_meta( $post_id, $this->image_meta_alt, $old_img_alt );
				}
			}
		}

		if ( is_array( $img_url ) && isset( $img_url['img_url'] ) ) {
			$image_meta['img_url'] = $img_url['img_url'];
		} else {
			$image_meta['img_url'] = $img_url;
		}
		$image_meta['img_alt'] = $img_alt;

		// Get OG title and description for backward compatibility
		$image_meta['og_title'] = get_post_meta( $post_id, '_dpsfiwu_og_title', true );
		$image_meta['og_description'] = get_post_meta( $post_id, '_dpsfiwu_og_description', true );

		if ( ( 'product_variation' === get_post_type( $post_id ) || 'product' === get_post_type( $post_id ) ) && $is_single_page ) {
			if ( isset( $img_url['width'] ) ) {
				$image_meta['width']  = $img_url['width'];
				$image_meta['height'] = $img_url['height'];
			} else {
				if ( isset( $image_meta['img_url'] ) && '' !== $image_meta['img_url'] ) {
					$imagesize = $dpsfiwu->common->get_image_sizes( $image_meta['img_url'] );
					$image_url = array(
						'img_url' => $image_meta['img_url'],
						'width'   => isset( $imagesize[0] ) ? $imagesize[0] : '',
						'height'  => isset( $imagesize[1] ) ? $imagesize[1] : '',
						'img_alt' => $image_meta['img_alt'],
						'og_title' => $image_meta['og_title'],
						'og_description' => $image_meta['og_description'],
					);
					update_post_meta( $post_id, $this->image_meta_url, $image_url );
					$image_meta = $image_url;
				}
			}
		}
		return $image_meta;
	}

	/**
	 * Adds Settings Page
	 *
	 * @since 1.0
	 * @return void
	 */
	public function dpsfiwu_add_options_page() {
		add_options_page(
			__( 'Featured Image with URL', 'dpsfiwu' ),
			__( 'Featured Image with URL', 'dpsfiwu' ),
			'manage_options',
			'dpsfiwu',
			array( $this, 'dpsfiwu_options_page_html' )
		);
	}

	/**
	 * Settings Page HTML
	 *
	 * @since 1.0
	 * @return array|null
	 */
	public function dpsfiwu_options_page_html() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				// Output security fields for the registered setting "dpsfiwu".
				settings_fields( 'dpsfiwu' );

				// Output setting sections and their fields.
				do_settings_sections( 'dpsfiwu' );

				// Output save settings button.
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register custom settings, Sections & fields
	 *
	 * @since 1.0
	 * @return void
	 */
	public function dpsfiwu_settings_init() {
		register_setting( 'dpsfiwu', DPSFIWU_OPTIONS );

		add_settings_section(
			'dpsfiwu_section',
			__( 'Settings', 'dpsfiwu' ),
			array( $this, 'dpsfiwu_section_callback' ),
			'dpsfiwu'
		);

		// Register a new field in the "dpsfiwu_section" section, inside the "dpsfiwu" page.
		add_settings_field(
			'dpsfiwu_disabled_posttypes',
			__( 'Disable Post types', 'dpsfiwu' ),
			array( $this, 'disabled_posttypes_callback' ),
			'dpsfiwu',
			'dpsfiwu_section',
			array(
				'label_for' => 'dpsfiwu_disabled_posttypes',
				'class'     => 'dpsfiwu_row',
			)
		);

		add_settings_field(
			'dpsfiwu_resize_images',
			__( 'Display Resized Images', 'dpsfiwu' ),
			array( $this, 'resize_images_callback' ),
			'dpsfiwu',
			'dpsfiwu_section',
			array(
				'label_for' => 'dpsfiwu_resize_images',
				'class'     => 'dpsfiwu_row',
			)
		);

		// SEO Section
		add_settings_section(
			'dpsfiwu_seo_section',
			__( 'SEO & Social Sharing Settings', 'dpsfiwu' ),
			array( $this, 'dpsfiwu_seo_section_callback' ),
			'dpsfiwu'
		);

		add_settings_field(
			'harikrutfiwu_enable_schema',
			__( 'Enable Enhanced Schema.org', 'dpsfiwu' ),
			array( $this, 'enable_schema_callback' ),
			'dpsfiwu',
			'dpsfiwu_seo_section',
			array(
				'label_for' => 'harikrutfiwu_enable_schema',
				'class'     => 'dpsfiwu_row',
			)
		);

		add_settings_field(
			'dpsfiwu_enable_og_twitter',
			__( 'Enable Open Graph & Twitter Cards', 'dpsfiwu' ),
			array( $this, 'enable_og_twitter_callback' ),
			'dpsfiwu',
			'dpsfiwu_seo_section',
			array(
				'label_for' => 'dpsfiwu_enable_og_twitter',
				'class'     => 'dpsfiwu_row',
			)
		);

		add_settings_field(
			'dpsfiwu_fb_app_id',
			__( 'Facebook App ID', 'dpsfiwu' ),
			array( $this, 'fb_app_id_callback' ),
			'dpsfiwu',
			'dpsfiwu_seo_section',
			array(
				'label_for' => 'dpsfiwu_fb_app_id',
				'class'     => 'dpsfiwu_row',
			)
		);

		add_settings_field(
			'dpsfiwu_twitter_site',
			__( 'Twitter Site Handle', 'dpsfiwu' ),
			array( $this, 'twitter_site_callback' ),
			'dpsfiwu',
			'dpsfiwu_seo_section',
			array(
				'label_for' => 'dpsfiwu_twitter_site',
				'class'     => 'dpsfiwu_row',
			)
		);

		add_settings_field(
			'dpsfiwu_enable_social_preview',
			__( 'Enable Social Preview in Admin Bar', 'dpsfiwu' ),
			array( $this, 'enable_social_preview_callback' ),
			'dpsfiwu',
			'dpsfiwu_seo_section',
			array(
				'label_for' => 'dpsfiwu_enable_social_preview',
				'class'     => 'dpsfiwu_row',
			)
		);

		add_settings_field(
			'dpsfiwu_fallback_og_image',
			__( 'Fallback OG Image', 'dpsfiwu' ),
			array( $this, 'fallback_og_image_callback' ),
			'dpsfiwu',
			'dpsfiwu_seo_section',
			array(
				'label_for' => 'dpsfiwu_fallback_og_image',
				'class'     => 'dpsfiwu_row',
			)
		);
	}

	/**
	 * Callback function for dpsfiwu section.
	 *
	 * @since 1.0
	 * @param array $args Arguments.
	 * @return void
	 */
	public function dpsfiwu_section_callback( $args ) {
		// Do some HTML here.
	}

	/**
	 * Callback function for disabled_posttypes field.
	 *
	 * @since 1.0
	 * @param array $args Arguments.
	 * @return void
	 */
	public function disabled_posttypes_callback( $args ) {
		// Get the value of the setting we've registered with register_setting().
		global $wp_post_types;

		$options            = get_option( DPSFIWU_OPTIONS );
		$post_types         = $this->dpsfiwu_get_posttypes( true );
		$disabled_posttypes = isset( $options['dpsfiwu_disabled_posttypes'] ) ? $options['dpsfiwu_disabled_posttypes'] : array();

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $key => $post_type ) {
				?>
				<label for="<?php echo esc_attr( $key ); ?>" style="display: block;">
					<input
						name="<?php echo esc_attr( DPSFIWU_OPTIONS . '[' . $args['label_for'] . ']' ); ?>[]"
						class="dpsfiwu_disabled_posttypes"
						id="<?php echo esc_attr( $key ); ?>"
						type="checkbox" value="<?php echo esc_attr( $key ); ?>"
						<?php echo ( in_array( $key, $disabled_posttypes, true ) ) ? 'checked="checked"' : ''; ?>
					/>
					<?php echo isset( $wp_post_types[ $key ]->label ) ? esc_html( $wp_post_types[ $key ]->label ) : esc_html( ucfirst( $key ) ); ?>
				</label>
				<?php
			}
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Please check checkbox for posttypes on which you want to disable Featured image by URL.', 'dpsfiwu' ); ?>
		</p>

		<?php
	}

	/**
	 * Callback function for resize_images field.
	 *
	 * @since 1.0
	 * @param array $args Arguments.
	 * @return void
	 */
	public function resize_images_callback( $args ) {
		// Get the value of the setting we've registered with register_setting().
		$options       = get_option( DPSFIWU_OPTIONS );
		$resize_images = isset( $options['dpsfiwu_resize_images'] ) ? $options['dpsfiwu_resize_images'] : false;
		?>
		<label for="dpsfiwu_resize_images">
			<input
				name="<?php echo esc_attr( DPSFIWU_OPTIONS . '[' . $args['label_for'] . ']' ); ?>"
				type="checkbox"
				value="1"
				id="dpsfiwu_resize_images"
				<?php echo ( ( ! defined( 'JETPACK__VERSION' ) ) ? 'disabled="disabled"' : ( ( $resize_images ) ? 'checked="checked"' : '' ) ); ?>
			/>
			<?php esc_html_e( 'Enable display resized images for image sizes like thumbnail, medium, large etc..', 'dpsfiwu' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'You need Jetpack plugin installed & connected  for enable this functionality.', 'dpsfiwu' ); ?>
		</p>

		<?php
	}

	/**
	 * Callback function for SEO section.
	 *
	 * @since 1.0.4
	 * @param array $args Arguments.
	 * @return void
	 */
	public function dpsfiwu_seo_section_callback( $args ) {
		?>
		<p>
			<?php esc_html_e( 'Configure SEO and social media settings for your external featured images.', 'dpsfiwu' ); ?>
		</p>
		<?php
	}

	/**
	 * Callback function for enable_schema field.
	 *
	 * @since 1.0.4
	 * @param array $args Arguments.
	 * @return void
	 */
	public function enable_schema_callback( $args ) {
		$options        = get_option( DPSFIWU_OPTIONS );
		$enable_schema  = isset( $options['harikrutfiwu_enable_schema'] ) ? $options['harikrutfiwu_enable_schema'] : true;
		?>
		<label for="harikrutfiwu_enable_schema">
			<input
				name="<?php echo esc_attr( DPSFIWU_OPTIONS . '[' . $args['label_for'] . ']' ); ?>"
				type="checkbox"
				value="1"
				id="harikrutfiwu_enable_schema"
				<?php checked( $enable_schema, 1 ); ?>
			/>
			<?php esc_html_e( 'Enable enhanced Schema.org structured data for external images', 'dpsfiwu' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Adds ImageObject schema with proper dimensions, alt text, and metadata for better SEO.', 'dpsfiwu' ); ?>
		</p>
		<?php
	}

	/**
	 * Callback function for enable_og_twitter field.
	 *
	 * @since 1.0.4
	 * @param array $args Arguments.
	 * @return void
	 */
	public function enable_og_twitter_callback( $args ) {
		$options            = get_option( DPSFIWU_OPTIONS );
		$enable_og_twitter  = isset( $options['dpsfiwu_enable_og_twitter'] ) ? $options['dpsfiwu_enable_og_twitter'] : true;
		?>
		<label for="dpsfiwu_enable_og_twitter">
			<input
				name="<?php echo esc_attr( DPSFIWU_OPTIONS . '[' . $args['label_for'] . ']' ); ?>"
				type="checkbox"
				value="1"
				id="dpsfiwu_enable_og_twitter"
				<?php checked( $enable_og_twitter, 1 ); ?>
			/>
			<?php esc_html_e( 'Enable Open Graph and Twitter Card meta tags for external images', 'dpsfiwu' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Automatically adds proper OG and Twitter card tags for better social media sharing.', 'dpsfiwu' ); ?>
		</p>
		<?php
	}

	/**
	 * Callback function for fb_app_id field.
	 *
	 * @since 1.0.4
	 * @param array $args Arguments.
	 * @return void
	 */
	public function fb_app_id_callback( $args ) {
		$options    = get_option( DPSFIWU_OPTIONS );
		$fb_app_id  = isset( $options['dpsfiwu_fb_app_id'] ) ? $options['dpsfiwu_fb_app_id'] : '';
		?>
		<input
			name="<?php echo esc_attr( DPSFIWU_OPTIONS . '[' . $args['label_for'] . ']' ); ?>"
			type="text"
			id="dpsfiwu_fb_app_id"
			value="<?php echo esc_attr( $fb_app_id ); ?>"
			class="regular-text"
			placeholder="1234567890123456"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your Facebook App ID for better social media analytics.', 'dpsfiwu' ); ?>
			<?php echo '<a href="https://developers.facebook.com/docs/apps/" target="_blank">' . esc_html__( 'Get Facebook App ID', 'dpsfiwu' ) . '</a>'; ?>
		</p>
		<?php
	}

	/**
	 * Callback function for twitter_site field.
	 *
	 * @since 1.0.4
	 * @param array $args Arguments.
	 * @return void
	 */
	public function twitter_site_callback( $args ) {
		$options        = get_option( DPSFIWU_OPTIONS );
		$twitter_site   = isset( $options['dpsfiwu_twitter_site'] ) ? $options['dpsfiwu_twitter_site'] : '';
		?>
		<input
			name="<?php echo esc_attr( DPSFIWU_OPTIONS . '[' . $args['label_for'] . ']' ); ?>"
			type="text"
			id="dpsfiwu_twitter_site"
			value="<?php echo esc_attr( $twitter_site ); ?>"
			class="regular-text"
			placeholder="@yourusername"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your Twitter site handle (without @) for Twitter Card attribution.', 'dpsfiwu' ); ?>
		</p>
		<?php
	}

	/**
	 * Callback function for enable_social_preview field.
	 *
	 * @since 1.0.4
	 * @param array $args Arguments.
	 * @return void
	 */
	public function enable_social_preview_callback( $args ) {
		$options                = get_option( DPSFIWU_OPTIONS );
		$enable_social_preview  = isset( $options['dpsfiwu_enable_social_preview'] ) ? $options['dpsfiwu_enable_social_preview'] : true;
		?>
		<label for="dpsfiwu_enable_social_preview">
			<input
				name="<?php echo esc_attr( DPSFIWU_OPTIONS . '[' . $args['label_for'] . ']' ); ?>"
				type="checkbox"
				value="1"
				id="dpsfiwu_enable_social_preview"
				<?php checked( $enable_social_preview, 1 ); ?>
			/>
			<?php esc_html_e( 'Show social media preview in admin bar', 'dpsfiwu' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Adds a preview button in the admin bar to see how your post looks on social media.', 'dpsfiwu' ); ?>
		</p>
		<?php
	}

	/**
	 * Callback function for fallback_og_image field.
	 *
	 * @since 1.0.0
	 * @param array $args Arguments.
	 * @return void
	 */
	public function fallback_og_image_callback( $args ) {
		$options            = get_option( DPSFIWU_OPTIONS );
		$fallback_og_image  = isset( $options['dpsfiwu_fallback_og_image'] ) ? $options['dpsfiwu_fallback_og_image'] : '';
		?>
		<input
			name="<?php echo esc_attr( DPSFIWU_OPTIONS . '[' . $args['label_for'] . ']' ); ?>"
			type="text"
			id="dpsfiwu_fallback_og_image"
			value="<?php echo esc_url( $fallback_og_image ); ?>"
			class="regular-text"
			placeholder="https://example.com/fallback-image.jpg"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter a fallback image URL to use when no Featured Image with URL is set. Recommended size: 1200x630 pixels.', 'dpsfiwu' ); ?>
		</p>
		<?php if ( ! empty( $fallback_og_image ) ) : ?>
			<div style="margin-top: 10px;">
				<img src="<?php echo esc_url( $fallback_og_image ); ?>" style="max-width: 200px; height: auto; border: 1px solid #ddd;" alt="Fallback OG Image Preview" />
				<p><small><?php esc_html_e( 'Current fallback image preview', 'dpsfiwu' ); ?></small></p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Get Post Types which supports Featured image with URL.
	 *
	 * @since 1.0.0
	 * @param bool $raw If true then return all post types.
	 * @return array
	 */
	public function dpsfiwu_get_posttypes( $raw = false ) {
		$post_types = array_diff( get_post_types( array( 'public' => true ), 'names' ), array( 'nav_menu_item', 'attachment', 'revision' ) );
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $key => $post_type ) {
				if ( ! post_type_supports( $post_type, 'thumbnail' ) ) {
					unset( $post_types[ $key ] );
				}
			}
		}
		if ( $raw ) {
			return $post_types;
		} else {
			$options            = get_option( DPSFIWU_OPTIONS );
			$disabled_posttypes = isset( $options['dpsfiwu_disabled_posttypes'] ) ? $options['dpsfiwu_disabled_posttypes'] : array();
			$post_types         = array_diff( $post_types, $disabled_posttypes );
		}

		return $post_types;
	}

	/**
	 * Render Featured image by URL in Product variation
	 *
	 * @since 1.0.0
	 *
	 * @param int    $loop           Loop.
	 * @param array  $variation_data Variation data.
	 * @param object $variation      Variation object.
	 * @return void
	 */
	public function dpsfiwu_add_product_variation_image_selector( $loop, $variation_data, $variation ) {
		$dpsfiwu_url = '';
		if ( isset( $variation_data['_dpsfiwu_url'][0] ) ) {
			$dpsfiwu_url = $variation_data['_dpsfiwu_url'][0];
			$dpsfiwu_url = maybe_unserialize( $dpsfiwu_url );
			if ( is_array( $dpsfiwu_url ) ) {
				$dpsfiwu_url = $dpsfiwu_url['img_url'];
			}
		}
		?>
		<div id="dpsfiwu_product_variation_<?php echo esc_attr( $variation->ID ); ?>" class="dpsfiwu_product_variation form-row form-row-first">
			<label for="dpsfiwu_pvar_url_<?php echo esc_attr( $variation->ID ); ?>">
				<strong>
					<?php esc_html_e( 'Product Variation Image with URL', 'dpsfiwu' ); ?>
				</strong>
			</label>

			<div id="dpsfiwu_pvar_img_wrap_<?php echo esc_attr( $variation->ID ); ?>" class="dpsfiwu_pvar_img_wrap" style="<?php echo ( ( '' === $dpsfiwu_url ) ? 'display:none' : '' ); ?>" >
				<span href="#" class="dpsfiwu_pvar_remove" data-id="<?php echo esc_attr( $variation->ID ); ?>"></span>
				<img id="dpsfiwu_pvar_img_<?php echo esc_attr( $variation->ID ); ?>" class="dpsfiwu_pvar_img" data-id="<?php echo esc_attr( $variation->ID ); ?>" src="<?php echo esc_attr( $dpsfiwu_url ); ?>" />
			</div>
			<div id="dpsfiwu_url_wrap_<?php echo esc_attr( $variation->ID ); ?>" style="<?php echo ( ( '' !== $dpsfiwu_url ) ? 'display:none' : '' ); ?>">
				<input id="dpsfiwu_pvar_url_<?php echo esc_attr( $variation->ID ); ?>" class="dpsfiwu_pvar_url" type="text" name="dpsfiwu_pvar_url[<?php echo esc_attr( $variation->ID ); ?>]" placeholder="<?php esc_attr_e( 'Product Variation Image URL', 'dpsfiwu' ); ?>" value="<?php echo esc_attr( $dpsfiwu_url ); ?>"/>
				<a id="dpsfiwu_pvar_preview_<?php echo esc_attr( $variation->ID ); ?>" class="dpsfiwu_pvar_preview button" data-id="<?php echo esc_attr( $variation->ID ); ?>">
					<?php esc_html_e( 'Preview', 'dpsfiwu' ); ?>
				</a>
			</div>
			<?php
			$nonce_name  = 'dpsfiwu_pvar_url_' . $variation->ID . '_nonce';
			$action_name = $nonce_name . '_action';
			wp_nonce_field( $action_name, $nonce_name );
			?>
		</div>
		<?php
	}

	/**
	 * Save Featured image by URL for Product variation
	 *
	 * @since 1.0.0
	 * @param int $variation_id Variation ID.
	 * @param int $i            Loop.
	 * @return void
	 */
	public function dpsfiwu_save_product_variation_image( $variation_id, $i ) {
		global $dpsfiwu;
		$nonce_name = 'dpsfiwu_pvar_url_' . $variation_id . '_nonce';

		if ( isset( $_POST[ $nonce_name ] ) && wp_verify_nonce( sanitize_key( $_POST[ $nonce_name ] ), $nonce_name . '_action' ) ) {
			$image_url = isset( $_POST['dpsfiwu_pvar_url'][ $variation_id ] ) ? esc_url_raw( wp_unslash( $_POST['dpsfiwu_pvar_url'][ $variation_id ] ) ) : '';
			if ( ! empty( $image_url ) ) {
				$img_url = get_post_meta( $variation_id, $this->image_meta_url, true );
				if ( is_array( $img_url ) && isset( $img_url['img_url'] ) && $image_url === $img_url['img_url'] ) {
						$image_url = array(
							'img_url' => $image_url,
							'width'   => $img_url['width'],
							'height'  => $img_url['height'],
						);
				} else {
					$imagesize = $dpsfiwu->common->get_image_sizes( $image_url );
					$image_url = array(
						'img_url' => $image_url,
						'width'   => isset( $imagesize[0] ) ? $imagesize[0] : '',
						'height'  => isset( $imagesize[1] ) ? $imagesize[1] : '',
					);
				}
				update_post_meta( $variation_id, $this->image_meta_url, $image_url );
			} else {
				delete_post_meta( $variation_id, $this->image_meta_url );
			}
		}
	}

	/**
	 * Sanitize variables using sanitize_text_field and wp_unslash.
	 *
	 * @param string|array $var Data to sanitize.
	 * @return string|array
	 */
	public function dpsfiwu_sanitize( $var ) {
		if ( is_array( $var ) ) {
			return array_map( array( $this, 'dpsfiwu_sanitize' ), $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( wp_unslash( $var ) ) : $var;
		}
	}

	/**
	 * Display notices for migration from "Featured Image by URL" plugin.
	 *
	 * @return void
	 */
	public function maybe_display_migrate_from_fibu_notices() {
		// Check if the migration was successful.
		if ( isset( $_GET['fibu_migration'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_migrated = sanitize_key( $_GET['fibu_migration'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'success' === $is_migrated ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php esc_html_e( 'The migration from the Featured Image by URL plugin was successful.', 'dpsfiwu' ); ?>
					</p>
				</div>
				<?php
			} elseif ( 'dismiss' === $is_migrated ) {
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php esc_html_e( 'The migration notice has been dismissed.', 'dpsfiwu' ); ?>
					</p>
				</div>
				<?php
				return;
			}
		}

		$is_active = is_plugin_active( 'featured-image-by-url/featured-image-by-url.php' );

		// Check if the plugin is active and not already migrated or dismissed.
		if ( $is_active ) {
			$is_migrated         = get_option( 'dpsfiwu_migrated_from_fibu', false );
			$is_notice_dismissed = get_option( 'dpsfiwu_migration_notice_dismissed', false );
			if ( $is_migrated || $is_notice_dismissed ) {
				return;
			}
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<?php
					printf(
						/* translators: 1: Plugin name, 2: Migration URL */
						esc_html__( 'You are currently using the %1$s plugin, which has been closed and is no longer receiving maintenance. To ensure the uninterrupted functionality of the plugin, please migrate your data from %1$s to %2$s.', 'dpsfiwu' ),
						'<strong>' . esc_html__( 'Featured Image by URL', 'featured-image-by-url' ) . '</strong>',
						'<strong>' . esc_html__( 'Featured Image with URL', 'dpsfiwu' ) . '</strong>'
					);
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'dpsfiwu_migrate_from_fibu', admin_url( 'admin-post.php' ) ), 'dpsfiwu_migrate_from_fibu_action', 'dpsfiwu_migrate_from_fibu_nonce' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Migrate Now', 'dpsfiwu' ); ?>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'dpsfiwu_migration_notice_dismissed', admin_url( 'admin-post.php' ) ), 'dpsfiwu_migration_notice_dismissed_action', 'dpsfiwu_migration_notice_dismissed_nonce' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Dismiss', 'dpsfiwu' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Handle migration from "Featured Image by URL" plugin.
	 *
	 * @return void
	 */
	public function handle_migration_from_fibu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['dpsfiwu_migrate_from_fibu_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['dpsfiwu_migrate_from_fibu_nonce'] ), 'dpsfiwu_migrate_from_fibu_action' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'dpsfiwu' ) );
		}

		// Migrate data from "Featured Image by URL" plugin.
		$this->migrate_from_fibu();

		// Redirect to the settings page.
		wp_safe_redirect( admin_url( 'options-general.php?page=dpsfiwu&fibu_migration=success' ) );
		exit;
	}

	/**
	 * Migrate data from "Featured Image by URL" plugin.
	 *
	 * @return void
	 */
	public function migrate_from_fibu() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct SQL query is required here.

		// Migrate the image url for the featured image.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s",
				'_dpsfiwu_url',
				'_knawatfibu_url'
			)
		);

		// Migrate the image alt for the featured image.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s",
				'_dpsfiwu_alt',
				'_knawatfibu_alt'
			)
		);

		// Migrate the image url for the product gallery.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s",
				'_dpsfiwu_wcgallary',
				'_knawatfibu_wcgallary'
			)
		);

		// phpcs:enable

		// Migrate the settings.
		$settings      = get_option( DPSFIWU_OPTIONS, array() );
		$fibu_settings = get_option( 'knawatfibu_options', array() );
		if ( empty( $settings ) && ! empty( $fibu_settings ) ) {
			$settings = array(
				'dpsfiwu_disabled_posttypes' => isset( $fibu_settings['disabled_posttypes'] ) ? $fibu_settings['disabled_posttypes'] : array(),
				'dpsfiwu_resize_images'      => isset( $fibu_settings['resize_images'] ) ? $fibu_settings['resize_images'] : false,
			);

			// Save the settings.
			update_option( DPSFIWU_OPTIONS, $settings );
		}

		// Set the "migrated_from_fibu" option to true.
		update_option( 'dpsfiwu_migrated_from_fibu', true );
	}

	/**
	 * Dismiss the migration notice.
	 *
	 * @return void
	 */
	public function dismiss_fibu_migration_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['dpsfiwu_migration_notice_dismissed_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['dpsfiwu_migration_notice_dismissed_nonce'] ), 'dpsfiwu_migration_notice_dismissed_action' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'dpsfiwu' ) );
		}

		// Set the "migration_notice_dismissed" option to true.
		update_option( 'dpsfiwu_migration_notice_dismissed', true );

		// Redirect to the settings page.
		wp_safe_redirect( admin_url( 'options-general.php?page=dpsfiwu&fibu_migration=dismiss' ) );
		exit;
	}

	/**
	 * Add "fibu_migration" in list of query variable names to remove.
	 *
	 * @param [] $removable_query_args An array of query variable names to remove from a URL.
	 * @return []
	 */
	public function removable_query_args( array $removable_query_args ): array {
		$removable_query_args[] = 'fibu_migration';
		return $removable_query_args;
	}
}
