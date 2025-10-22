<?php
/**
 * Migration class for Featured Image with URL.
 * Handles migration from old plugin versions to DPS.MEDIA version.
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
 * Migration class for Featured Image with URL.
 *
 * @since      1.0.0
 */
class DPSFIWU_Migration {

	/**
	 * Migration version.
	 *
	 * @var string
	 */
	private $migration_version = '1.0.0';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'check_for_migration' ) );
		add_action( 'admin_notices', array( $this, 'show_migration_notice' ) );
	}

	/**
	 * Check if migration is needed and run it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check_for_migration() {
		$last_migration = get_option( 'dpsfiwu_last_migration', '0.0.0' );

		if ( version_compare( $last_migration, $this->migration_version, '<' ) ) {
			$this->run_migration();
			update_option( 'dpsfiwu_last_migration', $this->migration_version );
		}
	}

	/**
	 * Run the migration process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function run_migration() {
		$this->migrate_post_meta();
		$this->migrate_options();
		$this->migrate_from_knawatfibu();

		// Store migration info for admin notice
		update_option( 'dpsfiwu_migration_needed', true );
		update_option( 'dpsfiwu_migration_date', current_time( 'mysql' ) );
	}

	/**
	 * Migrate post meta from old plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function migrate_post_meta() {
		$args = array(
			'post_type' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '_dpsfiwu_url',
					'compare' => 'EXISTS',
				),
				array(
					'key' => '_knawatfibu_url',
					'compare' => 'EXISTS',
				),
			),
		);

		$query = new WP_Query( $args );
		$migrated_count = 0;

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$migrated = $this->migrate_post_data( $post_id );
				if ( $migrated ) {
					$migrated_count++;
				}
			}
		}

		update_option( 'dpsfiwu_migrated_posts_count', $migrated_count );
	}

	/**
	 * Migrate data for a single post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return bool Whether migration occurred.
	 */
	private function migrate_post_data( $post_id ) {
		$migrated = false;

		// Get old Harikrut plugin data
		$old_url = get_post_meta( $post_id, '_dpsfiwu_url', true );
		$old_alt = get_post_meta( $post_id, '_dpsfiwu_alt', true );
		$old_og_title = get_post_meta( $post_id, '_dpsfiwu_og_title', true );
		$old_og_description = get_post_meta( $post_id, '_dpsfiwu_og_description', true );

		// Check if we need to migrate from Harikrut plugin
		if ( $old_url && ! get_post_meta( $post_id, '_dpsfiwu_url', true ) ) {
			// Migrate to new DPS.MEDIA meta keys
			update_post_meta( $post_id, '_dpsfiwu_url', $old_url );

			if ( $old_alt ) {
				update_post_meta( $post_id, '_dpsfiwu_alt', $old_alt );
			}

			if ( $old_og_title ) {
				update_post_meta( $post_id, '_dpsfiwu_og_title', $old_og_title );
			}

			if ( $old_og_description ) {
				update_post_meta( $post_id, '_dpsfiwu_og_description', $old_og_description );
			}

			$migrated = true;
		}

		// Check for KnawatFIBU plugin (very old version)
		$knawat_url = get_post_meta( $post_id, '_knawatfibu_url', true );
		if ( $knawat_url && ! get_post_meta( $post_id, '_dpsfiwu_url', true ) ) {
			update_post_meta( $post_id, '_dpsfiwu_url', $knawat_url );

			$knawat_alt = get_post_meta( $post_id, '_knawatfibu_alt', true );
			if ( $knawat_alt ) {
				update_post_meta( $post_id, '_dpsfiwu_alt', $knawat_alt );
			}

			$migrated = true;
		}

		// Migrate gallery data
		$old_gallery = get_post_meta( $post_id, '_dpsfiwu_wcgallary', true );
		if ( $old_gallery && ! get_post_meta( $post_id, '_dpsfiwu_wcgallary', true ) ) {
			update_post_meta( $post_id, '_dpsfiwu_wcgallary', $old_gallery );
			$migrated = true;
		}

		$knawat_gallery = get_post_meta( $post_id, '_knawatfibu_wcgallary', true );
		if ( $knawat_gallery && ! get_post_meta( $post_id, '_dpsfiwu_wcgallary', true ) ) {
			update_post_meta( $post_id, '_dpsfiwu_wcgallary', $knawat_gallery );
			$migrated = true;
		}

		return $migrated;
	}

	/**
	 * Migrate plugin options from old plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function migrate_options() {
		// Get old options
		$old_options = get_option( 'harikrutfiwu_options', array() );
		$new_options = get_option( 'dpsfiwu_options', array() );

		// Merge old options with new defaults
		$migrated_options = wp_parse_args( $new_options, $old_options );

		// Update new options
		update_option( 'dpsfiwu_options', $migrated_options );
	}

	/**
	 * Additional migration from KnawatFIBU plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function migrate_from_knawatfibu() {
		// Check if KnawatFIBU was active
		$knawat_options = get_option( 'knawatfibu_options', array() );
		if ( ! empty( $knawat_options ) ) {
			// Migrate some basic settings
			$new_options = get_option( 'dpsfiwu_options', array() );

			if ( isset( $knawat_options['resize_images'] ) ) {
				$new_options['dpsfiwu_resize_images'] = $knawat_options['resize_images'];
			}

			update_option( 'dpsfiwu_options', $new_options );
		}
	}

	/**
	 * Show migration notice to admin users.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function show_migration_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$migration_needed = get_option( 'dpsfiwu_migration_needed', false );
		if ( ! $migration_needed ) {
			return;
		}

		$migrated_count = get_option( 'dpsfiwu_migrated_posts_count', 0 );
		$migration_date = get_option( 'dpsfiwu_migration_date', '' );

		$class = 'notice notice-success is-dismissible';
		$message = sprintf(
			/* translators: %1$d: number of posts migrated, %2$s: migration date */
			__(
				'🎉 <strong>Featured Image with URL by DPS.MEDIA:</strong> Successfully migrated %1$d posts from the previous plugin version on %2$s. All your existing featured images and settings have been preserved.',
				'dpsfiwu'
			),
			number_format_i18n( $migrated_count ),
			date_i18n( get_option( 'date_format' ), strtotime( $migration_date ) )
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );

		// Clear the notice after displaying once
		update_option( 'dpsfiwu_migration_needed', false );
	}

	/**
	 * Get migration status.
	 *
	 * @since 1.0.0
	 * @return array Migration status information.
	 */
	public function get_migration_status() {
		return array(
			'last_migration' => get_option( 'dpsfiwu_last_migration', '0.0.0' ),
			'migrated_posts' => get_option( 'dpsfiwu_migrated_posts_count', 0 ),
			'migration_date' => get_option( 'dpsfiwu_migration_date', '' ),
			'needs_migration' => get_option( 'dpsfiwu_migration_needed', false ),
		);
	}
}