<?php
/**
 * SEO enhancement class for Featured Image with URL.
 *
 * @link       https://dps.media/
 * @since      1.0.0
 *
 * @package    DPSFIWU
 * @subpackage DPSFIWU/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO enhancement class for Featured Image with URL.
 *
 * @since      1.0.0
 */
class DPSFIWU_SEO {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$options = get_option( DPSFIWU_OPTIONS );

		// Schema.org structured data filters
		if ( ! isset( $options['harikrutfiwu_enable_schema'] ) || $options['harikrutfiwu_enable_schema'] ) {
			add_filter( 'woocommerce_structured_data_product', array( $this, 'enhance_product_schema' ), 100, 2 );
			add_filter( 'wpseo_schema_graph', array( $this, 'add_image_schema_to_wpseo' ), 11, 1 );
			add_action( 'wp_head', array( $this, 'add_custom_schema_markup' ), 5 );
		}

		// Open Graph and Twitter Card filters
		if ( ! isset( $options['dpsfiwu_enable_og_twitter'] ) || $options['dpsfiwu_enable_og_twitter'] ) {
			// Higher priority to override other SEO plugins
			add_filter( 'wpseo_opengraph_image', array( $this, 'add_opengraph_image' ), 999, 2 );
			add_filter( 'wpseo_twitter_image', array( $this, 'add_twitter_image' ), 999, 2 );

			// Also add direct OG filters for more compatibility
			add_filter( 'aioseo_opengraph_image', array( $this, 'add_aioseo_opengraph_image' ), 10, 2 );
			add_filter( 'rank_math/opengraph/facebook/image', array( $this, 'add_rank_math_opengraph_image' ), 10, 2 );

			// Add custom OG tags as fallback with higher priority
			add_action( 'wp_head', array( $this, 'add_custom_og_twitter_tags' ), 1 );
		}

		// Social sharing enhancements
		add_action( 'wp_head', array( $this, 'add_social_meta_tags' ), 7 );

		// Social preview in admin bar
		if ( ! isset( $options['dpsfiwu_enable_social_preview'] ) || $options['dpsfiwu_enable_social_preview'] ) {
			add_action( 'admin_bar_menu', array( $this, 'add_social_preview_menu' ), 100 );
		}

		// Facebook debug tool in admin bar (check inside the action)
		add_action( 'admin_bar_menu', array( $this, 'add_facebook_debug_menu' ), 101 );

		// Image validation and fallback
		add_filter( 'dpsfiwu_external_image_url', array( $this, 'validate_external_image' ), 10, 2 );
	}

	/**
	 * Enhanced Schema.org structured data for products
	 *
	 * @param array $markup Existing structured data
	 * @param WC_Product $product Product object
	 * @return array Enhanced structured data
	 */
	public function enhance_product_schema( $markup, $product ) {
		if ( ! isset( $markup['image'] ) || empty( $markup['image'] ) ) {
			global $dpsfiwu;
			$product_id = $product->get_id();

			if ( ! $dpsfiwu->common->dpsfiwu_is_disallow_posttype( 'product' ) && $product_id > 0 ) {
				$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $product_id );

				if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
					$markup['image'] = $this->create_image_object_schema( $image_data['img_url'], $image_data );
				}
			}
		}

		// Enhanced gallery images
		if ( isset( $markup['image'] ) && is_array( $markup['image'] ) ) {
			global $dpsfiwu;
			$product_id = $product->get_id();
			$gallery_images = $dpsfiwu->common->dpsfiwu_get_wcgallary_meta( $product_id );

			if ( ! empty( $gallery_images ) ) {
				$gallery_schema = array();
				foreach ( $gallery_images as $gallery_image ) {
					if ( isset( $gallery_image['url'] ) ) {
						$gallery_schema[] = $this->create_image_object_schema( $gallery_image['url'], $gallery_image );
					}
				}

				if ( ! empty( $gallery_schema ) ) {
					if ( count( $markup['image'] ) === 1 && isset( $markup['image'][0] ) ) {
						$markup['image'] = array_merge( $markup['image'], $gallery_schema );
					} else {
						$markup['image'] = is_array( $markup['image'] ) ? $markup['image'] : array( $markup['image'] );
						$markup['image'] = array_merge( $markup['image'], $gallery_schema );
					}
				}
			}
		}

		return $markup;
	}

	/**
	 * Create ImageObject schema for external images
	 *
	 * @param string $image_url Image URL
	 * @param array $image_data Image metadata
	 * @return array ImageObject schema
	 */
	private function create_image_object_schema( $image_url, $image_data = array() ) {
		$image_schema = array(
			'@type' => 'ImageObject',
			'url' => $image_url,
			'contentUrl' => $image_url,
		);

		// Add dimensions if available
		if ( isset( $image_data['width'] ) && ! empty( $image_data['width'] ) ) {
			$image_schema['width'] = (int) $image_data['width'];
		}
		if ( isset( $image_data['height'] ) && ! empty( $image_data['height'] ) ) {
			$image_schema['height'] = (int) $image_data['height'];
		}

		// Add alt text if available
		if ( isset( $image_data['img_alt'] ) && ! empty( $image_data['img_alt'] ) ) {
			$image_schema['name'] = $image_data['img_alt'];
			$image_schema['caption'] = $image_data['img_alt'];
		}

		// Try to get image dimensions if not provided
		if ( ( ! isset( $image_schema['width'] ) || ! isset( $image_schema['height'] ) ) && function_exists( 'getimagesize' ) ) {
			$size = @getimagesize( $image_url );
			if ( $size ) {
				$image_schema['width'] = $size[0];
				$image_schema['height'] = $size[1];
			}
		}

		// Add encoding format
		$image_extension = strtolower( pathinfo( $image_url, PATHINFO_EXTENSION ) );
		if ( ! empty( $image_extension ) ) {
			$image_schema['encodingFormat'] = 'image/' . $image_extension;
		}

		return $image_schema;
	}

	/**
	 * Add image schema to Yoast SEO graph
	 *
	 * @param array $graph Existing schema graph
	 * @return array Enhanced schema graph
	 */
	public function add_image_schema_to_wpseo( $graph ) {
		if ( is_singular() && ! is_front_page() ) {
			global $post;
			$post_id = $post->ID;

			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				$image_schema = $this->create_image_object_schema( $image_data['img_url'], $image_data );

				// Find the primary entity (WebPage, Article, etc.)
				foreach ( $graph as $piece ) {
					if ( isset( $piece['@type'] ) && in_array( $piece['@type'], array( 'WebPage', 'Article', 'Product' ) ) ) {
						if ( ! isset( $piece['image'] ) ) {
							$piece['image'] = $image_schema;
						} elseif ( is_array( $piece['image'] ) ) {
							$piece['image'][] = $image_schema;
						}
						break;
					}
				}
			}
		}

		return $graph;
	}

	/**
	 * Add custom schema markup to head
	 */
	public function add_custom_schema_markup() {
		if ( is_singular() && ! is_front_page() ) {
			global $post;
			$post_id = $post->ID;

			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				$schema = array(
					'@context' => 'https://schema.org',
					'@type' => 'ImageObject',
					'url' => $image_data['img_url'],
					'contentUrl' => $image_data['img_url'],
				);

				if ( ! empty( $image_data['img_alt'] ) ) {
					$schema['name'] = $image_data['img_alt'];
					$schema['caption'] = $image_data['img_alt'];
				}

				if ( isset( $image_data['width'] ) && ! empty( $image_data['width'] ) ) {
					$schema['width'] = (int) $image_data['width'];
				}
				if ( isset( $image_data['height'] ) && ! empty( $image_data['height'] ) ) {
					$schema['height'] = (int) $image_data['height'];
				}

				echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
			}
		}
	}

	/**
	 * Add Open Graph image
	 *
	 * @param string $image Current OG image
	 * @param int $post_id Post ID
	 * @return string Enhanced OG image URL
	 */
	public function add_opengraph_image( $image, $post_id ) {
		if ( empty( $image ) ) {
			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				return $image_data['img_url'];
			}
		}
		return $image;
	}

	/**
	 * Add Twitter Card image
	 *
	 * @param string $image Current Twitter image
	 * @param int $post_id Post ID
	 * @return string Enhanced Twitter image URL
	 */
	public function add_twitter_image( $image, $post_id ) {
		if ( empty( $image ) ) {
			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				return $image_data['img_url'];
			}
		}
		return $image;
	}

	/**
	 * Add custom Open Graph and Twitter tags
	 */
	public function add_custom_og_twitter_tags() {
		if ( is_singular() && ! is_front_page() ) {
			global $post;
			$post_id = $post->ID;

			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );
			$options = get_option( DPSFIWU_OPTIONS );

			$image_url = '';
			$image_alt = '';

			// Use external image if available
			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				$image_url = $image_data['img_url'];
				$image_alt = isset( $image_data['img_alt'] ) ? $image_data['img_alt'] : '';
			}
			// Use fallback OG image if set and no external image
			elseif ( ! empty( $options['dpsfiwu_fallback_og_image'] ) ) {
				$image_url = $options['dpsfiwu_fallback_og_image'];
				$image_alt = get_bloginfo( 'name' );
			}

			if ( ! empty( $image_url ) ) {
				// Always add OG image tags - this ensures they're present even if other plugins miss them
				echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
				echo '<meta property="og:image:secure_url" content="' . esc_url( $image_url, 'https' ) . '" />' . "\n";

				// Add image dimensions if available
				if ( isset( $image_data['width'] ) && ! empty( $image_data['width'] ) ) {
					echo '<meta property="og:image:width" content="' . esc_attr( $image_data['width'] ) . '" />' . "\n";
				}
				if ( isset( $image_data['height'] ) && ! empty( $image_data['height'] ) ) {
					echo '<meta property="og:image:height" content="' . esc_attr( $image_data['height'] ) . '" />' . "\n";
				}

				// Add image type
				$image_extension = strtolower( pathinfo( $image_url, PATHINFO_EXTENSION ) );
				if ( ! empty( $image_extension ) ) {
					echo '<meta property="og:image:type" content="image/' . esc_attr( $image_extension ) . '" />' . "\n";
				}

				// Add alt text if available
				if ( ! empty( $image_alt ) ) {
					echo '<meta property="og:image:alt" content="' . esc_attr( $image_alt ) . '" />' . "\n";
				}

				// Add custom OG title and description if available
				$og_title = get_post_meta( $post_id, '_dpsfiwu_og_title', true );
				$og_description = get_post_meta( $post_id, '_dpsfiwu_og_description', true );

				if ( ! empty( $og_title ) ) {
					echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '" />' . "\n";
				}
				if ( ! empty( $og_description ) ) {
					echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '" />' . "\n";
				}

				// Twitter Card tags - always add for consistency
				echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
				echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";

				if ( ! empty( $image_alt ) ) {
					echo '<meta name="twitter:image:alt" content="' . esc_attr( $image_alt ) . '" />' . "\n";
				}

				// Add custom OG title and description for Twitter if available
				if ( ! empty( $og_title ) ) {
					echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '" />' . "\n";
				}
				if ( ! empty( $og_description ) ) {
					echo '<meta name="twitter:description" content="' . esc_attr( $og_description ) . '" />' . "\n";
				}

				// Force Facebook crawler refresh
				echo '<meta property="og:image:url" content="' . esc_url( $image_url ) . '" />' . "\n";
			}
		}
	}

	/**
	 * Add comprehensive social meta tags
	 */
	public function add_social_meta_tags() {
		if ( is_singular() && ! is_front_page() ) {
			global $post;
			$post_id = $post->ID;

			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				$image_url = $image_data['img_url'];
				$options = get_option( DPSFIWU_OPTIONS );

				// Pinterest Rich Pins
				echo '<meta property="pinterest-rich-pin" content="true" />' . "\n";

				// Facebook specific optimizations
				if ( ! empty( $options['dpsfiwu_fb_app_id'] ) ) {
					echo '<meta property="fb:app_id" content="' . esc_attr( $options['dpsfiwu_fb_app_id'] ) . '" />' . "\n";
				}

				// Twitter site handle
				if ( ! empty( $options['dpsfiwu_twitter_site'] ) ) {
					$twitter_handle = $options['dpsfiwu_twitter_site'];
					if ( strpos( $twitter_handle, '@' ) !== 0 ) {
						$twitter_handle = '@' . $twitter_handle;
					}
					echo '<meta name="twitter:site" content="' . esc_attr( $twitter_handle ) . '" />' . "\n";
				}

				// Additional social media tags
				echo '<meta name="image" content="' . esc_url( $image_url ) . '" />' . "\n";

				// Add image verification
				$image_info = $this->validate_external_image( $image_url );
				if ( $image_info['accessible'] ) {
					echo '<meta name="image:status" content="accessible" />' . "\n";
					echo '<meta name="image:size" content="' . esc_attr( $image_info['size'] ) . '" />' . "\n";
				} else {
					echo '<meta name="image:status" content="inaccessible" />' . "\n";
				}
			}
		}
	}

	/**
	 * Add social preview to admin bar
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar object
	 */
	public function add_social_preview_menu( $admin_bar ) {
		if ( is_singular() && current_user_can( 'edit_posts' ) ) {
			global $post;
			$post_id = $post->ID;

			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				$admin_bar->add_menu( array(
					'id'    => 'dpsfiwu-social-preview',
					'title' => '🔍 Social Preview',
					'href'  => '#dpsfiwu-social-preview',
					'meta'  => array(
						'title' => 'Preview how this post looks on social media',
						'class' => 'dpsfiwu-social-preview-trigger'
					),
				));

				// Add social preview modal content
				$this->add_social_preview_modal( $image_data );
			}
		}
	}

	/**
	 * Add social preview modal
	 *
	 * @param array $image_data Image data
	 */
	private function add_social_preview_modal( $image_data ) {
		?>
		<div id="dpsfiwu-social-preview-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:99999; overflow:auto;">
			<div style="background:white; max-width:600px; margin:50px auto; padding:20px; border-radius:8px;">
				<h3>Social Media Preview</h3>
				<p><strong>Facebook/Open Graph:</strong></p>
				<div style="border:1px solid #ddd; padding:10px; margin-bottom:20px;">
					<img src="<?php echo esc_url( $image_data['img_url'] ); ?>" style="max-width:100%; height:auto; display:block; margin-bottom:10px;" alt="Preview">
					<p><strong>Title:</strong> <?php echo esc_html( get_the_title() ); ?></p>
					<p><strong>Description:</strong> <?php echo esc_html( get_the_excerpt() ? get_the_excerpt() : get_bloginfo( 'description' ) ); ?></p>
					<p><strong>Image URL:</strong> <code><?php echo esc_html( $image_data['img_url'] ); ?></code></p>
					<?php if ( ! empty( $image_data['img_alt'] ) ) : ?>
					<p><strong>Alt Text:</strong> <?php echo esc_html( $image_data['img_alt'] ); ?></p>
					<?php endif; ?>
				</div>

				<p><strong>Twitter Card:</strong></p>
				<div style="border:1px solid #ddd; padding:10px; margin-bottom:20px;">
					<img src="<?php echo esc_url( $image_data['img_url'] ); ?>" style="max-width:100%; height:auto; display:block; margin-bottom:10px;" alt="Preview">
					<p><strong>Title:</strong> <?php echo esc_html( get_the_title() ); ?></p>
					<p><strong>Description:</strong> <?php echo esc_html( get_the_excerpt() ? get_the_excerpt() : get_bloginfo( 'description' ) ); ?></p>
				</div>

				<p><button onclick="document.getElementById('dpsfiwu-social-preview-modal').style.display='none'" style="background:#0073aa; color:white; border:none; padding:10px 20px; cursor:pointer;">Close</button></p>
			</div>
		</div>

		<script>
		document.addEventListener('click', function(e) {
			if (e.target && e.target.classList.contains('dpsfiwu-social-preview-trigger')) {
				e.preventDefault();
				document.getElementById('dpsfiwu-social-preview-modal').style.display='block';
			}
		});
		</script>
		<?php
	}

	/**
	 * Validate external image accessibility
	 *
	 * @param string $image_url Image URL
	 * @param array $image_data Additional image data
	 * @return array Validation results
	 */
	public function validate_external_image( $image_url, $image_data = array() ) {
		$result = array(
			'url' => $image_url,
			'accessible' => false,
			'size' => 'unknown',
			'error' => '',
		);

		// Basic URL validation
		if ( ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			$result['error'] = 'Invalid URL format';
			return $result;
		}

		// Check if URL is accessible
		$response = wp_remote_head( $image_url, array(
			'timeout' => 5,
			'sslverify' => false,
		));

		if ( is_wp_error( $response ) ) {
			$result['error'] = $response->get_error_message();
			return $result;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			$result['error'] = 'HTTP ' . $response_code;
			return $result;
		}

		$result['accessible'] = true;

		// Get content length if available
		$content_length = wp_remote_retrieve_header( $response, 'content-length' );
		if ( $content_length ) {
			$result['size'] = size_format( $content_length );
		}

		return $result;
	}

	
	/**
	 * Add Open Graph image for All in One SEO
	 *
	 * @param string $image Current OG image
	 * @param int $post_id Post ID
	 * @return string Enhanced OG image URL
	 */
	public function add_aioseo_opengraph_image( $image, $post_id ) {
		if ( empty( $image ) ) {
			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				return $image_data['img_url'];
			}
		}
		return $image;
	}

	/**
	 * Add Open Graph image for Rank Math
	 *
	 * @param string $image Current OG image
	 * @param int $post_id Post ID
	 * @return string Enhanced OG image URL
	 */
	public function add_rank_math_opengraph_image( $image, $post_id ) {
		if ( empty( $image ) ) {
			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				return $image_data['img_url'];
			}
		}
		return $image;
	}

	/**
	 * Add Facebook debug tool to admin bar
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar object
	 */
	public function add_facebook_debug_menu( $admin_bar ) {
		// Check user capabilities inside the function, not in constructor
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( is_singular() ) {
			global $post;
			$post_id = $post->ID;

			global $dpsfiwu;
			$image_data = $dpsfiwu->admin->dpsfiwu_get_image_meta( $post_id );

			if ( ! empty( $image_data ) && isset( $image_data['img_url'] ) && ! empty( $image_data['img_url'] ) ) {
				$current_url = get_permalink( $post_id );
				$facebook_debug_url = 'https://developers.facebook.com/tools/debug/?q=' . urlencode( $current_url );

				$admin_bar->add_menu( array(
					'id'    => 'dpsfiwu-facebook-debug',
					'title' => '🔧 Debug Facebook OG',
					'href'  => $facebook_debug_url,
					'meta'  => array(
						'title' => 'Debug Facebook Open Graph tags',
						'target' => '_blank'
					),
				));
			}
		}
	}
}