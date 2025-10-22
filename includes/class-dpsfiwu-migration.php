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
		// Run migration on plugins_loaded to ensure all plugins are loaded
		add_action( 'plugins_loaded', array( $this, 'check_for_migration' ), 20 );
		add_action( 'admin_notices', array( $this, 'show_migration_notice' ) );
		add_action( 'admin_init', array( $this, 'force_migration_check' ), 1 );

		// Add a manual trigger for testing (remove in production)
		add_action( 'admin_init', array( $this, 'manual_migration_trigger' ) );
	}

	/**
	 * Force migration check on admin_init for reliability.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function force_migration_check() {
		// Also check during admin_init for more reliable migration
		$this->check_for_migration();
	}

	/**
	 * Check if migration is needed and run it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check_for_migration() {
		$last_migration = get_option( 'dpsfiwu_last_migration', '0.0.0' );
		$current_version = get_option( 'dpsfiwu_current_version', '0.0.0' );

		// Debug logging
		error_log( '[DPSFIWU Migration] Checking for migration...' );
		error_log( '[DPSFIWU Migration] Last migration: ' . $last_migration );
		error_log( '[DPSFIWU Migration] Current version: ' . $current_version );

		// Always check for migration on plugin activation/update
		if ( version_compare( $last_migration, $this->migration_version, '<' ) || version_compare( $current_version, '1.0.0', '<' ) ) {
			error_log( '[DPSFIWU Migration] Migration needed - running migration...' );
			$this->run_migration();
			update_option( 'dpsfiwu_last_migration', $this->migration_version );
			update_option( 'dpsfiwu_current_version', '1.0.0' );
		} else {
			error_log( '[DPSFIWU Migration] No migration needed' );
		}
	}

	/**
	 * Run the migration process.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function run_migration() {
		error_log( '[DPSFIWU Migration] Starting migration process...' );

		$this->migrate_post_meta();
		$this->migrate_options();
		$this->migrate_from_knawatfibu();

		// Store migration info for admin notice
		update_option( 'dpsfiwu_migration_needed', true );
		update_option( 'dpsfiwu_migration_date', current_time( 'mysql' ) );

		error_log( '[DPSFIWU Migration] Migration process completed' );
	}

	/**
	 * Migrate post meta from old plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function migrate_post_meta() {
		error_log( '[DPSFIWU Migration] Starting post meta migration...' );

		$args = array(
			'post_type' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '_harikrutfiwu_url',
					'compare' => 'EXISTS',
				),
				array(
					'key' => '_harikrutfiwu_wcgallary',
					'compare' => 'EXISTS',
				),
				array(
					'key' => '_dpsfiwu_url',
					'compare' => 'EXISTS',
				),
				array(
					'key' => '_knawatfibu_url',
					'compare' => 'EXISTS',
				),
				array(
					'key' => '_knawatfibu_wcgallary',
					'compare' => 'EXISTS',
				),
			),
		);

		$query = new WP_Query( $args );
		$migrated_count = 0;

		error_log( '[DPSFIWU Migration] Found ' . $query->found_posts . ' posts with old plugin data' );

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$migrated = $this->migrate_post_data( $post_id );
				if ( $migrated ) {
					$migrated_count++;
					error_log( '[DPSFIWU Migration] Successfully migrated post ID: ' . $post_id );
				}
			}
		}

		update_option( 'dpsfiwu_migrated_posts_count', $migrated_count );
		error_log( '[DPSFIWU Migration] Post meta migration completed. Migrated ' . $migrated_count . ' posts.' );
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

		// Get old Harikrut plugin data (note: using old meta keys)
		$old_url = get_post_meta( $post_id, '_harikrutfiwu_url', true );
		$old_alt = get_post_meta( $post_id, '_harikrutfiwu_alt', true );
		$old_og_title = get_post_meta( $post_id, '_harikrutfiwu_og_title', true );
		$old_og_description = get_post_meta( $post_id, '_harikrutfiwu_og_description', true );

		error_log( '[DPSFIWU Migration] Checking post ID ' . $post_id . ' for old plugin data' );
		error_log( '[DPSFIWU Migration] Found old URL: ' . ( $old_url ? 'YES' : 'NO' ) );

		// Check if we need to migrate from Harikrut plugin
		if ( $old_url && ! get_post_meta( $post_id, '_dpsfiwu_url', true ) ) {
			error_log( '[DPSFIWU Migration] Migrating post ID ' . $post_id . ' from Harikrut plugin' );

			// Migrate to new DPS.MEDIA meta keys
			update_post_meta( $post_id, '_dpsfiwu_url', $old_url );
			error_log( '[DPSFIWU Migration] Migrated URL for post ' . $post_id . ': ' . $old_url );

			if ( $old_alt ) {
				update_post_meta( $post_id, '_dpsfiwu_alt', $old_alt );
				error_log( '[DPSFIWU Migration] Migrated ALT for post ' . $post_id . ': ' . $old_alt );
			}

			if ( $old_og_title ) {
				update_post_meta( $post_id, '_dpsfiwu_og_title', $old_og_title );
				error_log( '[DPSFIWU Migration] Migrated OG title for post ' . $post_id . ': ' . $old_og_title );
			}

			if ( $old_og_description ) {
				update_post_meta( $post_id, '_dpsfiwu_og_description', $old_og_description );
				error_log( '[DPSFIWU Migration] Migrated OG description for post ' . $post_id );
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

		// Migrate gallery data from Harikrut plugin
		$old_gallery = get_post_meta( $post_id, '_harikrutfiwu_wcgallary', true );
		if ( $old_gallery && ! get_post_meta( $post_id, '_dpsfiwu_wcgallary', true ) ) {
			update_post_meta( $post_id, '_dpsfiwu_wcgallary', $old_gallery );
			error_log( '[DPSFIWU Migration] Migrated gallery for post ' . $post_id );
			$migrated = true;
		}

		$knawat_gallery = get_post_meta( $post_id, '_knawatfibu_wcgallary', true );
		if ( $knawat_gallery && ! get_post_meta( $post_id, '_dpsfiwu_wcgallary', true ) ) {
			update_post_meta( $post_id, '_dpsfiwu_wcgallary', $knawat_gallery );
			error_log( '[DPSFIWU Migration] Migrated Knawat gallery for post ' . $post_id );
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
		error_log( '[DPSFIWU Migration] Starting options migration...' );

		// Get old options
		$old_options = get_option( 'harikrutfiwu_options', array() );
		$new_options = get_option( 'dpsfiwu_options', array() );

		error_log( '[DPSFIWU Migration] Found ' . count( $old_options ) . ' old plugin options' );

		// Merge old options with new defaults
		$migrated_options = wp_parse_args( $new_options, $old_options );

		// Update new options
		update_option( 'dpsfiwu_options', $migrated_options );

		error_log( '[DPSFIWU Migration] Options migration completed' );
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
	 * Manual migration trigger for testing.
	 * Trigger migration when URL parameter ?dpsfiwu_force_migration=1 is present.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function manual_migration_trigger() {
		if ( isset( $_GET['dpsfiwu_force_migration'] ) && $_GET['dpsfiwu_force_migration'] == '1' && current_user_can( 'manage_options' ) ) {
			// Reset migration options to force re-run
			delete_option( 'dpsfiwu_last_migration' );
			delete_option( 'dpsfiwu_current_version' );

			// Force migration to run
			$this->check_for_migration();

			// Add admin notice
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success"><p><strong>DPSFIWU Migration:</strong> Manual migration triggered. Check debug.log for details.</p></div>';
			});
		}
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