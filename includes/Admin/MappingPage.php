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
		add_action( 'admin_init', [ $this, 'handle_save' ] );
		add_action( 'wp_ajax_notion_sync_get_mapping_fields', [ $this, 'handle_get_mapping_fields' ] );
	}

	public function register_settings() {
		register_setting( 'notion_sync_mapping_group', 'notion_sync_mapping', [
			'sanitize_callback' => [ $this, 'sanitize_mapping_data' ],
		] );
	}

	/**
	 * Handle save action for connections
	 */
	public function handle_save() {
		if ( ! isset( $_POST['notion_sync_mapping_nonce'] ) || ! wp_verify_nonce( $_POST['notion_sync_mapping_nonce'], 'notion_sync_save_mapping' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$conn_id = isset( $_POST['connection_id'] ) ? sanitize_text_field( $_POST['connection_id'] ) : '';
		$data    = isset( $_POST['notion_sync_mapping'] ) ? $_POST['notion_sync_mapping'] : [];

		$repository = \NotionSync\Sync\ConnectionRepository::get_instance();
		
		// Map the fields correctly for the repository
		$mapping_data = [
			'name'        => sanitize_text_field( $data['connection_name'] ),
			'database_id' => sanitize_text_field( $data['database_id'] ),
			'post_type'   => sanitize_text_field( $data['post_type'] ),
			'output_mode' => sanitize_text_field( $data['output_mode'] ),
			'mapping'     => $this->sanitize_mapping_data( $data ),
		];

		// Prevent duplicates
		if ( $repository->exists_by_db_id( $mapping_data['database_id'], ( 'new' !== $conn_id ? $conn_id : null ) ) ) {
			add_settings_error(
				'notion_sync_mapping',
				'duplicate_db_id',
				__( 'This Notion Database is already connected. Please use a unique Database ID.', 'notion-sync-for-wp' )
			);
			set_transient( 'settings_errors', get_settings_errors(), 30 );
			wp_safe_redirect( admin_url( 'admin.php?page=notion-sync-for-wp-mapping' . ( 'new' !== $conn_id ? '&id=' . $conn_id : '&action=new' ) ) );
			exit;
		}

		if ( 'new' === $conn_id || empty( $conn_id ) ) {
			$conn_id = $repository->create( $mapping_data );
		} else {
			$repository->save( $conn_id, $mapping_data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=notion-sync-for-wp-mapping&id=' . $conn_id . '&updated=1' ) );
		exit;
	}

	/**
	 * Sanitize and validate mapping data
	 */
	public function sanitize_mapping_data( $mapping ) {
		if ( ! is_array( $mapping ) ) {
			return [];
		}

		// List of protected WordPress meta keys
		$protected_keys = [
			'_thumbnail_id',
			'_wp_page_template',
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_wp_attachment_metadata',
		];

		// Handle Custom Meta
		if ( ! empty( $mapping['custom_meta'] ) && is_array( $mapping['custom_meta'] ) ) {
			$sanitized_meta = [];
			foreach ( $mapping['custom_meta'] as $meta ) {
				if ( empty( $meta['key'] ) || empty( $meta['property'] ) ) {
					continue;
				}

				$key = sanitize_key( $meta['key'] );
				
				// Block protected keys and keys starting with _ (unless they are already on the site and safe)
				// For safety, we block all hidden keys (_) not explicitly allowed if needed
				if ( in_array( $key, $protected_keys, true ) || ( strpos( $key, '_' ) === 0 && ! in_array( $key, apply_filters( 'notion_sync_allowed_hidden_meta', [] ), true ) ) ) {
					add_settings_error(
						'notion_sync_mapping',
						'protected_meta_key',
						sprintf( __( 'The meta key "%s" is protected and cannot be used.', 'notion-sync-for-wp' ), $key )
					);
					continue;
				}

				if ( ! empty( $key ) ) {
					$sanitized_meta[] = [
						'key'      => $key,
						'property' => sanitize_text_field( $meta['property'] ),
					];
				}
			}
			$mapping['custom_meta'] = $sanitized_meta;
		}

		return $mapping;
	}

	public function get_wp_fields( $post_type = 'post' ) {
		$fields = [
			'post_title'     => __( 'Post Title', 'notion-sync-for-wp' ),
			'post_content'   => __( 'Post Content', 'notion-sync-for-wp' ),
			'post_slug'      => __( 'Post Slug', 'notion-sync-for-wp' ),
		];

		if ( post_type_supports( $post_type, 'excerpt' ) ) {
			$fields['post_excerpt'] = __( 'Post Excerpt', 'notion-sync-for-wp' );
		}

		$fields['post_status'] = __( 'Post Status', 'notion-sync-for-wp' );
		$fields['post_date']   = __( 'Post Date', 'notion-sync-for-wp' );

		if ( post_type_supports( $post_type, 'thumbnail' ) ) {
			$fields['_thumbnail_id'] = __( 'Featured Image', 'notion-sync-for-wp' );
		}

		return $fields;
	}

	/**
	 * Handle AJAX request for dynamic mapping fields
	 */
	public function handle_get_mapping_fields() {
		check_ajax_referer( 'notion_sync_admin_nonce', '_ajax_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'notion-sync-for-wp' ) );
		}

		$post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : 'post';
		$db_id     = isset( $_POST['database_id'] ) ? sanitize_text_field( $_POST['database_id'] ) : '';

		$mapping = [];
		if ( $db_id ) {
			// Try to find a connection with this DB ID to pre-fill mapping if possible
			$connections = \NotionSync\Sync\ConnectionRepository::get_instance()->get_all();
			foreach ( $connections as $conn ) {
				if ( $conn['database_id'] === $db_id ) {
					$mapping = isset( $conn['mapping'] ) ? $conn['mapping'] : [];
					break;
				}
			}
		} else {
			// If no DB ID provided via AJAX, try to get from current connection ID in URL
			$conn_id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
			if ( $conn_id ) {
				$conn = \NotionSync\Sync\ConnectionRepository::get_instance()->get( $conn_id );
				$mapping = isset( $conn['mapping'] ) ? $conn['mapping'] : [];
			}
		}

		ob_start();
		$this->render_mapping_parts( $post_type, $db_id, $mapping );
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}

	public function render() {
		// Load persisted errors if any
		$persisted_errors = get_transient( 'settings_errors' );
		if ( $persisted_errors ) {
			foreach ( $persisted_errors as $error ) {
				add_settings_error(
					$error['setting'],
					$error['code'],
					$error['message'],
					$error['type']
				);
			}
			delete_transient( 'settings_errors' );
		}

		$template = NOTION_SYNC_PATH . 'templates/admin/mapping-page.php';
		
		$client     = \NotionSync\Notion\NotionClient::get_instance();
		$db_service = \NotionSync\Notion\DatabaseService::get_instance();
		$mapper     = \NotionSync\Sync\FieldMapper::get_instance();

		$conn_id   = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
		$is_new    = ( 'new' === ( isset( $_GET['action'] ) ? $_GET['action'] : '' ) );
		$mapping   = [];
		$post_type = 'post';
		$db_id     = '';
		$conn_name = '';
		$output_mode = 'classic';

		if ( $conn_id ) {
			$connection = $mapper->get_mapping( $conn_id ); // Note: mapper->get_mapping returns the 'mapping' sub-array currently
			// Wait, I should probably use the repository directly here to get all data
			$full_conn = \NotionSync\Sync\ConnectionRepository::get_instance()->get( $conn_id );
			if ( $full_conn ) {
				$conn_name   = $full_conn['name'];
				$db_id       = $full_conn['database_id'];
				$post_type   = $full_conn['post_type'];
				$output_mode = isset( $full_conn['output_mode'] ) ? $full_conn['output_mode'] : 'classic';
				$mapping     = isset( $full_conn['mapping'] ) ? $full_conn['mapping'] : [];
			}
		}

		$post_types = $mapper->get_post_types();
		$properties = [];

		if ( $db_id ) {
			$raw_db   = $client->get_database( $db_id );
			$properties = ! is_wp_error( $raw_db ) ? $db_service->get_available_properties( $raw_db ) : [];
			
			// Add virtual property for Page Content (Body)
			$properties['-1'] = [
				'id'   => '-1',
				'name' => __( '-- Page Content (Body) --', 'notion-sync-for-wp' ),
				'type' => 'body',
			];
		}

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<div class="wrap"><h1>Field Mapping</h1><p>Template not found.</p></div>';
		}
	}

	/**
	 * Override render_mapping_parts call in render() to pass the correct mapping
	 */
	 public function render_dynamic_section( $post_type, $db_id, $mapping ) {
		$this->render_mapping_parts( $post_type, $db_id, $mapping );
	 }

	/**
	 * Render the dynamic parts of the mapping page
	 */
	public function render_mapping_parts( $post_type, $db_id = null, $mapping = null ) {
		$client   = \NotionSync\Notion\NotionClient::get_instance();
		$db_service = \NotionSync\Notion\DatabaseService::get_instance();
		$mapper     = \NotionSync\Sync\FieldMapper::get_instance();
		
		if ( ! $db_id ) {
			$conn_id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : '';
			if ( $conn_id ) {
				$conn = \NotionSync\Sync\ConnectionRepository::get_instance()->get( $conn_id );
				$db_id = isset( $conn['database_id'] ) ? $conn['database_id'] : '';
			} else {
				$db_id = get_option( 'notion_sync_db_id' );
			}
		}
		
		$raw_db   = $db_id ? $client->get_database( $db_id ) : null;
		$properties = ( $raw_db && ! is_wp_error( $raw_db ) ) ? $db_service->get_available_properties( $raw_db ) : [];
		
		$properties['-1'] = [
			'id'   => '-1',
			'name' => __( '-- Page Content (Body) --', 'notion-sync-for-wp' ),
			'type' => 'body',
		];
		
		if ( null === $mapping ) {
			$mapping = $mapper->get_mapping();
		}
		$wp_fields  = $this->get_wp_fields( $post_type );
		$taxonomies = $mapper->get_post_type_taxonomies( $post_type );

		$template = NOTION_SYNC_PATH . 'templates/admin/partials/mapping-fields.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}
