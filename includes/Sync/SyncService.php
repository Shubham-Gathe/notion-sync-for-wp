<?php
namespace NotionSync\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synchronization Service
 */
class SyncService {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Poll Notion for all "Ready" posts and sync them
	 *
	 * @return array Results summary
	 */
	/**
	 * Poll Notion for "Ready" posts in chunks
	 *
	 * @param string|null $cursor Notion pagination cursor
	 * @param int $limit Max items to sync in this batch
	 * @return array|WP_Error Results summary and pagination info
	 */
	public function sync_database( $connection_id, $cursor = null, $limit = 10 ) {
		$repository = \NotionSync\Sync\ConnectionRepository::get_instance();
		$connection = $repository->get( $connection_id );
		
		if ( ! $connection ) {
			return new \WP_Error( 'invalid_connection', __( 'Connection not found.', 'notion-sync-for-wp' ) );
		}

		$client = \NotionSync\Notion\NotionClient::get_instance();
		$db_id  = $connection['database_id'];
		
		if ( ! $db_id ) {
			return new \WP_Error( 'missing_db_id', __( 'Database ID is missing for this connection.', 'notion-sync-for-wp' ) );
		}

		$mapping = isset( $connection['mapping'] ) ? $connection['mapping'] : [];
		if ( empty( $mapping['post_title'] ) ) {
			return new \WP_Error( 'missing_mapping', __( 'Field mapping is not configured for this connection.', 'notion-sync-for-wp' ) );
		}

		// Construct filter for Status = "Ready"
		$filter = [
			'property' => 'Status',
			'select'   => [
				'equals' => 'Ready',
			],
		];

		// Fetch a page of results
		$response = $client->query_database( $db_id, $filter, $cursor );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$results = [
			'success'     => 0,
			'errors'      => 0,
			'details'     => [],
			'next_cursor' => $response['next_cursor'] ?? null,
			'has_more'    => $response['has_more'] ?? false,
		];

		$pages_to_sync = $response['results'] ?? [];
		
		// Limit the number of pages to sync in this single request to prevent timeouts
		$pages_to_sync = array_slice( $pages_to_sync, 0, $limit );

		foreach ( $pages_to_sync as $page ) {
			try {
				$sync_result = $this->sync_page( $page['id'], $connection );
				
				if ( is_wp_error( $sync_result ) ) {
					$results['errors']++;
					$results['details'][] = sprintf( "[%s] Error syncing page %s: %s", $connection['name'], $page['id'], $sync_result->get_error_message() );
				} else {
					$results['success']++;
					$results['details'][] = sprintf( "[%s] Successfully synced page %s -> Post %d", $connection['name'], $page['id'], $sync_result );
				}
			} catch ( \Exception $e ) {
				$results['errors']++;
				$results['details'][] = sprintf( "[%s] Critical failure syncing page %s: %s", $connection['name'], $page['id'], $e->getMessage() );
			}
		}

		return $results;
	}

	/**
	 * Sync a specific page from Notion to WordPress
	 *
	 * @param string $page_id
	 * @return int|WP_Error Post ID on success
	 */
	public function sync_page( $page_id, $connection ) {
		$client     = \NotionSync\Notion\NotionClient::get_instance();
		$parser     = \NotionSync\Notion\BlockParser::get_instance();
		$mapping    = isset( $connection['mapping'] ) ? $connection['mapping'] : [];
		$output_mode = isset( $connection['output_mode'] ) ? $connection['output_mode'] : 'classic';

		// 1. Fetch Page Metadata (Properties)
		$page = $client->get_page( $page_id );
		if ( is_wp_error( $page ) ) {
			return $page;
		}

		// 1. Check for existing post early to handle image attachments better
		$existing_posts = get_posts( [
			'post_type'      => 'any',
			'meta_key'       => '_notion_page_id',
			'meta_value'     => $page_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post_status'    => 'any',
		] );
		$post_id = ! empty( $existing_posts ) ? $existing_posts[0] : 0;

		// 2. Extract Data based on Mapping
		$post_type = ! empty( $mapping['post_type'] ) ? $mapping['post_type'] : 'post';
		if ( ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'invalid_post_type', sprintf( __( 'Post type "%s" does not exist.', 'notion-sync-for-wp' ), $post_type ) );
		}

		$post_data = [
			'post_type' => $post_type,
		];
		if ( $post_id ) {
			$post_data['ID'] = $post_id;
		}

		foreach ( $mapping as $wp_field => $notion_prop_id ) {
			if ( ! $notion_prop_id || in_array( $wp_field, [ 'custom_meta', 'taxonomies' ], true ) ) {
				continue;
			}

			// Handle regular properties
			if ( empty( $post_data[ $wp_field ] ) && isset( $page['properties'] ) ) {
				// Try direct lookup by Name (key)
				$val = null;
				if ( isset( $page['properties'][ $notion_prop_id ] ) ) {
					$val = $this->extract_property_value( $page['properties'][ $notion_prop_id ] );
				} else {
					// Fallback to loop for ID-based matching
					foreach ( $page['properties'] as $prop ) {
						if ( $prop['id'] === $notion_prop_id ) {
							$val = $this->extract_property_value( $prop );
							break;
						}
					}
				}

				if ( null !== $val ) {
					if ( 'post_status' === $wp_field ) {
						$converted_status = $this->convert_to_wp_status( $val );
						$post_data['post_status'] = $converted_status;
						\NotionSync\Core\Logger::get_instance()->log( "Status Mapping: Notion '{$val}' -> WP '{$converted_status}'", 'debug' );
					} else {
						$post_data[ $wp_field ] = $val;
					}
				}
			}

			// Handle special "Page Content" (-1)
			if ( '-1' === $notion_prop_id && 'post_content' === $wp_field ) {
				$blocks = $client->get_block_children( $page_id );
				if ( ! is_wp_error( $blocks ) ) {
					$post_data['post_content'] = $parser->parse_blocks( $blocks['results'] ?? [], $post_id, $output_mode );
				}
			}
		}

		// Auto-detect Status if not mapped
		if ( empty( $post_data['post_status'] ) && isset( $page['properties']['Status'] ) ) {
			$val = $this->extract_property_value( $page['properties']['Status'] );
			$post_data['post_status'] = $this->convert_to_wp_status( $val );
			\NotionSync\Core\Logger::get_instance()->log( "Auto-detected Status Mapping: Notion '{$val}' -> WP '{$post_data['post_status']}'", 'debug' );
		}

		// 3. Insert or Update Post
		if ( empty( $post_data['post_status'] ) ) {
			$post_data['post_status'] = 'draft';
		}

		if ( empty( $post_data['post_title'] ) ) {
			return new \WP_Error( 'missing_title', __( 'Sync Failed: Post Title is empty. Please check your field mapping.', 'notion-sync-for-wp' ) );
		}
		
		\NotionSync\Core\Logger::get_instance()->log( "Final Post Data before save: " . json_encode( $post_data ), 'debug' );

		if ( $post_id ) {
			$post_id = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
			if ( ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_notion_page_id', $page_id );
			}
		}

		if ( is_wp_error( $post_id ) ) {
			\NotionSync\Core\Logger::get_instance()->log( "Sync Failed for Page {$page_id}: " . $post_id->get_error_message(), 'error' );
			return $post_id;
		}

		// 4. Handle Taxonomies Dynamically
		if ( ! is_wp_error( $post_id ) && ! empty( $mapping['taxonomies'] ) ) {
			\NotionSync\Core\Logger::get_instance()->log( "Taxonomy Mapping found: " . json_encode( $mapping['taxonomies'] ), 'debug' );
			foreach ( $mapping['taxonomies'] as $tax_slug => $notion_prop_id ) {
				if ( ! $notion_prop_id ) continue;
				if ( ! taxonomy_exists( $tax_slug ) ) {
					\NotionSync\Core\Logger::get_instance()->log( "Taxonomy '{$tax_slug}' does not exist", 'debug' );
					continue;
				}

				$term_names = [];
				$found = false;
				foreach ( $page['properties'] as $prop_name => $prop ) {
					if ( ( isset( $prop['id'] ) && $prop['id'] === $notion_prop_id ) || $prop_name === $notion_prop_id ) {
						$val = $this->extract_property_value( $prop );
						if ( is_array( $val ) ) {
							$term_names = $val;
						} elseif ( ! empty( $val ) ) {
							$term_names = [ $val ];
						}
						\NotionSync\Core\Logger::get_instance()->log( "Found terms for taxonomy '{$tax_slug}' in property '{$prop_name}': " . json_encode( $term_names ), 'debug' );
						$found = true;
						break;
					}
				}

				if ( ! $found ) {
					\NotionSync\Core\Logger::get_instance()->log( "Could not find Notion property matching '{$notion_prop_id}' for taxonomy '{$tax_slug}'", 'debug' );
				}

				if ( ! empty( $term_names ) ) {
					$term_ids = $this->ensure_terms( $term_names, $tax_slug );
					\NotionSync\Core\Logger::get_instance()->log( "Setting terms for taxonomy '{$tax_slug}': " . json_encode( $term_ids ), 'debug' );
					wp_set_object_terms( $post_id, $term_ids, $tax_slug );
				}
			}
		}


		// 5. Handle Featured Image
		if ( ! is_wp_error( $post_id ) && ! empty( $post_data['_thumbnail_id'] ) ) {
			$image_url = $post_data['_thumbnail_id'];
			if ( filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
				$media_service = \NotionSync\Sync\MediaService::get_instance();
				$attachment_id = $media_service->sideload_image( $image_url, $post_id, $post_data['post_title'] );
				
				if ( ! is_wp_error( $attachment_id ) ) {
					set_post_thumbnail( $post_id, $attachment_id );
				}
			}
		}

		// 6. Handle Custom Meta
		if ( ! empty( $mapping['custom_meta'] ) ) {
			\NotionSync\Core\Logger::get_instance()->log( "Custom Meta Mapping found: " . json_encode( $mapping['custom_meta'] ), 'debug' );
			foreach ( $mapping['custom_meta'] as $meta_mapping ) {
				if ( empty( $meta_mapping['key'] ) || empty( $meta_mapping['property'] ) ) {
					\NotionSync\Core\Logger::get_instance()->log( "Skipping empty meta mapping: " . json_encode( $meta_mapping ), 'debug' );
					continue;
				}
				$meta_key = sanitize_key( $meta_mapping['key'] );
				if ( ! $meta_key ) {
					\NotionSync\Core\Logger::get_instance()->log( "Invalid meta key after sanitization: " . $meta_mapping['key'], 'debug' );
					continue;
				}
				
				$found = false;
				foreach ( $page['properties'] as $prop_name => $prop ) {
					if ( ( isset( $prop['id'] ) && $prop['id'] === $meta_mapping['property'] ) || $prop_name === $meta_mapping['property'] ) {
						$val = $this->extract_property_value( $prop );
						\NotionSync\Core\Logger::get_instance()->log( "Updating meta '{$meta_key}' with value from property '{$prop_name}' (ID: {$prop['id']}): " . json_encode( $val ), 'debug' );
						update_post_meta( $post_id, $meta_key, $val );
						$found = true;
						break;
					}
				}
				if ( ! $found ) {
					\NotionSync\Core\Logger::get_instance()->log( "Could not find Notion property matching '{$meta_mapping['property']}' for meta key '{$meta_key}'", 'debug' );
				}
			}
		} else {
			\NotionSync\Core\Logger::get_instance()->log( "No Custom Meta Mapping found in mapping: " . json_encode( $mapping ), 'debug' );
		}


		\NotionSync\Core\Logger::get_instance()->log( "Sync Successful for Page {$page_id} -> Post {$post_id}", 'info' );
		return $post_id;
	}

	/**
	 * Ensure terms exist and return their IDs
	 */
	private function ensure_terms( $names, $taxonomy ) {
		$ids = [];
		foreach ( $names as $name ) {
			if ( empty( $name ) ) continue;
			
			$term = term_exists( $name, $taxonomy );
			if ( ! $term ) {
				$term = wp_insert_term( $name, $taxonomy );
			}
			
			if ( ! is_wp_error( $term ) && ! empty( $term ) ) {
				$ids[] = intval( is_array( $term ) ? $term['term_id'] : $term );
			}
		}
		return array_unique( $ids );
	}

	/**
	 * Convert Notion status to WordPress post status
	 */
	private function convert_to_wp_status( $notion_status ) {
		$status = strtolower( (string) $notion_status );
		
		switch ( $status ) {
			case 'published':
			case 'ready':
			case 'publish':
			case 'live':
				return 'publish';
			case 'draft':
			case 'in progress':
			case 'todo':
				return 'draft';
			case 'private':
				return 'private';
			default:
				return 'draft';
		}
	}

	/**
	 * Extract plain value from Notion property object
	 */
	private function extract_property_value( $prop ) {
		$type = $prop['type'] ?? '';
		switch ( $type ) {
			case 'title':
				return \NotionSync\Notion\BlockParser::get_instance()->parse_rich_text( $prop['title'] ?? [] );
			case 'rich_text':
				return \NotionSync\Notion\BlockParser::get_instance()->parse_rich_text( $prop['rich_text'] ?? [] );
			case 'select':
				return $prop['select']['name'] ?? '';
			case 'status':
				return $prop['status']['name'] ?? '';
			case 'multi_select':
				return array_column( $prop['multi_select'] ?? [], 'name' );
			case 'number':
				return $prop['number'] ?? 0;
			case 'date':
				return $prop['date']['start'] ?? '';
			case 'url':
				return $prop['url'] ?? '';
			case 'email':
				return $prop['email'] ?? '';
			case 'files':
				if ( ! empty( $prop['files'] ) ) {
					$file = $prop['files'][0];
					return $file['file']['url'] ?? $file['external']['url'] ?? '';
				}
				return '';
			default:
				return '';
		}
	}
}
