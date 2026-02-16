=== Notion Sync for WP ===
Contributors: antigravity
Tags: notion, sync, database, block editor, auto-publish
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your Notion databases to WordPress and sync pages automatically with clean, semantic HTML and local media ownership.

== Description ==

Notion Sync for WP is a powerful, lightweight bridge between Notion and WordPress. It allows you to use Notion as your drafting environment and sync content to WordPress with a single click (or automatically).

**Key Features:**

*   **Dynamic Schema Detection:** Automatically detects your Notion database properties.
*   **Semantic HTML Engine:** Converts Notion blocks to clean, SEO-friendly HTML (H2-H4 shifts, nested lists, blockquotes).
*   **Media Ownership:** Downloads Notion images and stores them in your WordPress Media Library.
*   **Resilience & Scalability:** Uses AJAX chunking to safely process hundreds of posts without server timeouts.
*   **Duplicate-Safe:** Intelligent mapping ensures you update content rather than creating duplicates.

**Third-Party Services**

This plugin connects to and sends data to external services:

*   **Notion API** (https://api.notion.com): Used to fetch your database properties and page content.
    *   Notion Privacy Policy: https://www.notion.so/about/privacy-policy
    *   Notion Terms of Service: https://www.notion.so/about/terms-and-conditions

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Notion Sync > Settings** and enter your Notion Integration Token and Database ID.
4. Go to **Field Mapping** to link your Notion properties to WordPress fields.

== Frequently Asked Questions ==

= Does this plugin store my Notion data? =
The plugin only stores your connection token and mapping settings. Content is fetched from Notion and stored in your WordPress database as standard posts.

= Is there a limit to how many posts I can sync? =
No. The plugin uses chunked processing to safely handle databases of any size.

== Screenshots ==

1. The settings page where you configure your Notion credentials.
2. The field mapping UI for connecting properties.

== Changelog ==

= 1.0.0 =
* Initial release.
* Block-to-HTML engine with SEO heading shifts.
* Media sideloading and taxonomy mapping.
* AJAX chunked sync worker.
