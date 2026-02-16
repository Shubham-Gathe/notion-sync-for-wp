# Notion Sync for WP

Sync Notion databases and pages to WordPress effortlessly.

## Architecture
This plugin follows a modular 5-tier architecture:
- **Core**: Infrastructure, Autoloader, Security, Logger.
- **Admin**: Menu registration and Page controllers.
- **Notion**: Notion API client and Database services.
- **Sync**: Synchronization engine and Field mapping.
- **Helpers**: Utility classes.

## Getting Started
1. Activate the plugin.
2. Go to **Notion Sync > Settings** to enter your API key.
3. Use **Field Mapping** to configure your database sync.
