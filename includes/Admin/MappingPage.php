<?php
namespace NotionSync\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mapping Page Controller
 */
class MappingPage {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_settings() {
		register_setting( 'notion_sync_mapping_group', 'notion_sync_mapping' );
	}

	public function get_wp_fields() {
		return [
			'post_title'     => __( 'Post Title', 'notion-sync-for-wp' ),
			'post_content'   => __( 'Post Content', 'notion-sync-for-wp' ),
			'post_slug'      => __( 'Post Slug', 'notion-sync-for-wp' ),
			'post_excerpt'   => __( 'Post Excerpt', 'notion-sync-for-wp' ),
			'post_status'    => __( 'Post Status', 'notion-sync-for-wp' ),
			'post_date'      => __( 'Post Date', 'notion-sync-for-wp' ),
			'category'       => __( 'Category', 'notion-sync-for-wp' ),
			'post_tag'       => __( 'Tags', 'notion-sync-for-wp' ),
			'_thumbnail_id'  => __( 'Featured Image', 'notion-sync-for-wp' ),
		];
	}

	public function render() {
		$template = NOTION_SYNC_PATH . 'templates/admin/mapping-page.php';
		
		$client   = \NotionSync\Notion\NotionClient::get_instance();
		$db_service = \NotionSync\Notion\DatabaseService::get_instance();
		$db_id    = get_option( 'notion_sync_db_id' );
		
		$raw_db   = $client->get_database( $db_id );
		$properties = ! is_wp_error( $raw_db ) ? $db_service->get_available_properties( $raw_db ) : [];
		
		// Add virtual property for Page Content (Body)
		$properties['-1'] = [
			'id'   => '-1',
			'name' => __( '-- Page Content (Body) --', 'notion-sync-for-wp' ),
			'type' => 'body',
		];
		
		$wp_fields = $this->get_wp_fields();
		$mapping   = \NotionSync\Sync\FieldMapper::get_instance()->get_mapping();

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<div class="wrap"><h1>Field Mapping</h1><p>Template not found.</p></div>';
		}
	}
}
