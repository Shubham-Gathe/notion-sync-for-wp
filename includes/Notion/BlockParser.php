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
	 * Parse an array of Notion blocks into HTML or Gutenberg Blocks
	 *
	 * @param array $blocks
	 * @param int $post_id
	 * @param string $mode 'classic' or 'gutenberg'
	 * @return string Content
	 */
	public function parse_blocks( $blocks, $post_id = 0, $mode = 'classic' ) {
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
				$list_buffer[] = $this->parse_block( $block, $post_id, $mode );
				continue;
			}

			// If we were in a list and now encounter a non-list item, flush buffer
			if ( $list_type ) {
				$list_html = "<{$list_type}>\n" . implode( '', $list_buffer ) . "</{$list_type}>\n";
				if ( 'gutenberg' === $mode ) {
					$html .= "<!-- wp:list -->\n" . $list_html . "<!-- /wp:list -->\n";
				} else {
					$html .= $list_html;
				}
				$list_buffer = [];
				$list_type   = '';
			}

			$html .= $this->parse_block( $block, $post_id, $mode ) . "\n";
		}

		// Final buffer flush
		if ( $list_type ) {
			$list_html = "<{$list_type}>\n" . implode( '', $list_buffer ) . "</{$list_type}>\n";
			if ( 'gutenberg' === $mode ) {
				$html .= "<!-- wp:list -->\n" . $list_html . "<!-- /wp:list -->\n";
			} else {
				$html .= $list_html;
			}
		}

		return $html;
	}

	/**
	 * Parse a single block
	 */
	private function parse_block( $block, $post_id = 0, $mode = 'classic' ) {
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
				$content = $this->parse_paragraph( $data );
				return ( 'gutenberg' === $mode ) ? "<!-- wp:paragraph -->\n{$content}\n<!-- /wp:paragraph -->" : $content;
			case 'heading_1':
				$content = $this->parse_heading( $data, 2 );
				return ( 'gutenberg' === $mode ) ? "<!-- wp:heading {\"level\":2} -->\n{$content}\n<!-- /wp:heading -->" : $content;
			case 'heading_2':
				$content = $this->parse_heading( $data, 3 );
				return ( 'gutenberg' === $mode ) ? "<!-- wp:heading {\"level\":3} -->\n{$content}\n<!-- /wp:heading -->" : $content;
			case 'heading_3':
				$content = $this->parse_heading( $data, 4 );
				return ( 'gutenberg' === $mode ) ? "<!-- wp:heading {\"level\":4} -->\n{$content}\n<!-- /wp:heading -->" : $content;
			case 'bulleted_list_item':
			case 'numbered_list_item':
				return $this->parse_list_item( $data, $post_id, $children, $mode );
			case 'image':
				return $this->parse_image( $data, $post_id, $mode );
			case 'code':
				return $this->parse_code( $data, $mode );
			case 'quote':
				return $this->parse_quote( $data, $mode );
			case 'divider':
				return ( 'gutenberg' === $mode ) ? "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->" : '<hr />';
			default:
				return '<!-- Unsupported block type: ' . esc_html( $type ) . ' -->';
		}
	}

	/**
	 * Parse code block
	 */
	private function parse_code( $data, $mode = 'classic' ) {
		$content = isset( $data['rich_text'] ) ? $this->parse_rich_text( $data['rich_text'] ) : '';
		$lang    = $data['language'] ?? '';
		$html    = '<pre><code class="language-' . esc_attr( $lang ) . '">' . $content . '</code></pre>';
		
		if ( 'gutenberg' === $mode ) {
			return "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code class=\"language-" . esc_attr( $lang ) . "\">" . $content . "</code></pre>\n<!-- /wp:code -->";
		}
		
		return $html;
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
	private function parse_list_item( $data, $post_id = 0, $children = [], $mode = 'classic' ) {
		$content = $this->parse_rich_text( $data['rich_text'] ?? [] );
		$html    = '<li>' . $content;
		
		if ( ! empty( $children ) ) {
			$html .= "\n" . $this->parse_blocks( $children, $post_id, $mode );
		}
		
		$html .= '</li>';
		return $html;
	}

	/**
	 * Parse quote
	 */
	private function parse_quote( $data, $mode = 'classic' ) {
		$content = $this->parse_rich_text( $data['rich_text'] ?? [] );
		if ( 'gutenberg' === $mode ) {
			return "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>" . $content . "</p></blockquote>\n<!-- /wp:quote -->";
		}
		return '<blockquote>' . $content . '</blockquote>';
	}

	/**
	 * Parse image
	 */
	private function parse_image( $data, $post_id = 0, $mode = 'classic' ) {
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
		
		if ( 'gutenberg' === $mode ) {
			$img_id_attr = ( ! is_wp_error( $attachment_id ) ) ? ',"id":' . $attachment_id : '';
			$html = "<!-- wp:image {\"sizeSlug\":\"full\"" . $img_id_attr . "} -->\n";
			$html .= '<figure class="wp-block-image size-full"><img src="' . esc_attr( $url ) . '" alt="' . esc_attr( $caption ) . '"';
			if ( ! is_wp_error( $attachment_id ) ) {
				$html .= ' class="wp-image-' . $attachment_id . '"';
			}
			$html .= '/>';
			if ( $caption ) {
				$html .= '<figcaption>' . $caption . '</figcaption>';
			}
			$html .= "</figure>\n<!-- /wp:image -->";
			return $html;
		}

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
