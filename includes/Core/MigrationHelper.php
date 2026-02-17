<?php
namespace NotionSync\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use NotionSync\Sync\ConnectionRepository;

/**
 * Migration Helper
 * 
 * Handles database/option migrations between plugin versions.
 */
class MigrationHelper {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Run all necessary migrations
	 */
	public function run_migrations() {
		$this->migrate_to_multi_connection();
	}

	/**
	 * Migrate single-database settings to the new multi-connection repository
	 */
	private function migrate_to_multi_connection() {
		$db_id   = get_option( 'notion_sync_db_id' );
		$mapping = get_option( 'notion_sync_mapping' );

		// If no legacy settings or if multi-connection already initialized, skip
		if ( empty( $db_id ) || ! empty( get_option( 'notion_sync_connections' ) ) ) {
			return;
		}

		Logger::get_instance()->log( 'Starting migration to multi-connection architecture...', 'info' );

		$repository = ConnectionRepository::get_instance();
		
		$initial_data = [
			'name'        => __( 'First Connection', 'notion-sync-for-wp' ),
			'database_id' => $db_id,
			'post_type'   => isset( $mapping['post_type'] ) ? $mapping['post_type'] : 'post',
			'mapping'     => $mapping,
			'output_mode' => get_option( 'notion_sync_output_mode', 'classic' ),
			'created_at'  => current_time( 'mysql' ),
		];

		$conn_id = $repository->create( $initial_data );

		if ( $conn_id ) {
			Logger::get_instance()->log( "Migration Successful. Created connection ID: {$conn_id}", 'info' );
			
			// Safely clear legacy options (optional: keep them for a few versions, but let's clear to avoid re-migration)
			delete_option( 'notion_sync_db_id' );
			// We keep notion_sync_mapping for now just in case, or we can clear it too
			// delete_option( 'notion_sync_mapping' );
			// delete_option( 'notion_sync_output_mode' );
		}
	}
}
