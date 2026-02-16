<?php
namespace NotionSync\Notion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notion Block Parser
 */
class BlockParser {
	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Parse an array of Notion blocks into HTML
	 *
	 * @param array $blocks
	 * @return string HTML content
	 */
	public function parse_blocks( $blocks, $post_id = 0 ) {
		if ( empty( $blocks ) || ! is_array( $blocks ) ) {
			return '';
		}

		$html            = '';
		$list_buffer     = [];
		$list_type       = ''; // 'ul' or 'ol'

		foreach ( $blocks as $block ) {
			$type = $block['type'] ?? '';

			// Handle List Grouping
			if ( 'bulleted_list_item' === $type || 'numbered_list_item' === $type ) {
				$current_list_type = ( 'bulleted_list_item' === $type ) ? 'ul' : 'ol';

				if ( $list_type && $list_type !== $current_list_type ) {
					// Close previous list if type changed
					$html .= "<{$list_type}>\n" . implode( '', $list_buffer ) . "</{$list_type}>\n";
					$list_buffer = [];
				}

				$list_type     = $current_list_type;
				$list_buffer[] = $this->parse_block( $block, $post_id );
				continue;
			}

			// If we were in a list and now encounter a non-list item, flush buffer
			if ( $list_type ) {
				$html .= "<{$list_type}>\n" . implode( '', $list_buffer ) . "</{$list_type}>\n";
				$list_buffer = [];
				$list_type   = '';
			}

			$html .= $this->parse_block( $block, $post_id ) . "\n";
		}

		// Final buffer flush
		if ( $list_type ) {
			$html .= "<{$list_type}>\n" . implode( '', $list_buffer ) . "</{$list_type}>\n";
		}

		return $html;
	}

	/**
	 * Parse a single block
	 */
	private function parse_block( $block, $post_id = 0 ) {
		$type = $block['type'] ?? '';
		if ( ! $type ) {
			return '';
		}

		$data = $block[ $type ] ?? [];

		// Recursively fetch children if has_children is true and children are not already present
		$children = [];
		if ( ! empty( $block['has_children'] ) ) {
			if ( ! empty( $block['children'] ) ) {
				$children = $block['children'];
			} else {
				$client   = \NotionSync\Notion\NotionClient::get_instance();
				$response = $client->get_block_children( $block['id'] );
				if ( ! is_wp_error( $response ) ) {
					$children = $response['results'] ?? [];
				}
			}
		}

		switch ( $type ) {
			case 'paragraph':
				return $this->parse_paragraph( $data );
			case 'heading_1':
				return $this->parse_heading( $data, 2 ); // Shift H1 to H2
			case 'heading_2':
				return $this->parse_heading( $data, 3 ); // Shift H2 to H3
			case 'heading_3':
				return $this->parse_heading( $data, 4 ); // Shift H3 to H4
			case 'bulleted_list_item':
			case 'numbered_list_item':
				return $this->parse_list_item( $data, $post_id, $children );
			case 'image':
				return $this->parse_image( $data, $post_id );
			case 'code':
				return $this->parse_code( $data );
			case 'quote':
				return $this->parse_quote( $data );
			case 'divider':
				return '<hr />';
			default:
				return '<!-- Unsupported block type: ' . esc_html( $type ) . ' -->';
		}
	}

	/**
	 * Parse code block
	 */
	private function parse_code( $data ) {
		$content = isset( $data['rich_text'] ) ? $this->parse_rich_text( $data['rich_text'] ) : '';
		$lang    = $data['language'] ?? '';
		return '<pre><code class="language-' . esc_attr( $lang ) . '">' . $content . '</code></pre>';
	}

	/**
	 * Parse paragraph
	 */
	private function parse_paragraph( $data ) {
		$content = $this->parse_rich_text( $data['rich_text'] ?? [] );
		return empty( $content ) ? '<p>&nbsp;</p>' : '<p>' . $content . '</p>';
	}

	/**
	 * Parse heading
	 */
	private function parse_heading( $data, $level ) {
		$content = $this->parse_rich_text( $data['rich_text'] ?? [] );
		return '<h' . $level . '>' . $content . '</h' . $level . '>';
	}

	/**
	 * Parse list item
	 */
	private function parse_list_item( $data, $post_id = 0, $children = [] ) {
		$content = $this->parse_rich_text( $data['rich_text'] ?? [] );
		$html    = '<li>' . $content;
		
		if ( ! empty( $children ) ) {
			$html .= "\n" . $this->parse_blocks( $children, $post_id );
		}
		
		$html .= '</li>';
		return $html;
	}

	/**
	 * Parse quote
	 */
	private function parse_quote( $data ) {
		$content = $this->parse_rich_text( $data['rich_text'] ?? [] );
		return '<blockquote>' . $content . '</blockquote>';
	}

	/**
	 * Parse image
	 */
	private function parse_image( $data, $post_id = 0 ) {
		$url = '';
		if ( 'external' === $data['type'] ) {
			$url = $data['external']['url'] ?? '';
		} elseif ( 'file' === $data['type'] ) {
			$url = $data['file']['url'] ?? '';
		}

		if ( ! $url ) {
			return '';
		}

		// Sideload inline images to WordPress library
		$media_service = \NotionSync\Sync\MediaService::get_instance();
		$attachment_id = $media_service->sideload_image( $url, $post_id );
		if ( ! is_wp_error( $attachment_id ) ) {
			$url = wp_get_attachment_url( $attachment_id );
		}

		$caption = $this->parse_rich_text( $data['caption'] ?? [] );
		$html = '<figure class="wp-block-image"><img src="' . esc_attr( $url ) . '" alt="' . esc_attr( $caption ) . '" />';
		if ( $caption ) {
			$html .= '<figcaption>' . $caption . '</figcaption>';
		}
		$html .= '</figure>';

		return $html;
	}

	/**
	 * Parse rich text array into HTML with formatting
	 */
	public function parse_rich_text( $rich_text ) {
		if ( empty( $rich_text ) ) {
			return '';
		}

		$html = '';
		foreach ( $rich_text as $text ) {
			$snippet = esc_html( $text['plain_text'] ?? '' );
			$ann     = $text['annotations'] ?? [];

			if ( ! empty( $ann['bold'] ) ) {
				$snippet = '<strong>' . $snippet . '</strong>';
			}
			if ( ! empty( $ann['italic'] ) ) {
				$snippet = '<em>' . $snippet . '</em>';
			}
			if ( ! empty( $ann['strikethrough'] ) ) {
				$snippet = '<del>' . $snippet . '</del>';
			}
			if ( ! empty( $ann['underline'] ) ) {
				$snippet = '<span style="text-decoration: underline;">' . $snippet . '</span>';
			}
			if ( ! empty( $ann['code'] ) ) {
				$snippet = '<code>' . $snippet . '</code>';
			}
			
			if ( ! empty( $text['href'] ) ) {
				$snippet = '<a href="' . esc_attr( $text['href'] ) . '">' . $snippet . '</a>';
			}

			$html .= $snippet;
		}

		return $html;
	}
}
