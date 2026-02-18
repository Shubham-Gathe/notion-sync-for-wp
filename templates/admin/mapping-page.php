<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap notion-sync-for-wp-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<?php settings_errors( 'notion_sync_mapping' ); ?>

	<p><?php esc_html_e( 'Map Notion database properties to WordPress post fields.', 'notion-sync-for-wp' ); ?></p>

	<?php if ( empty( $properties ) && ! $is_new ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'No Notion properties found. Please check your API credentials and Database ID.', 'notion-sync-for-wp' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="notion-sync-mapping-form">
		<?php wp_nonce_field( 'notion_sync_save_mapping', 'notion_sync_mapping_nonce' ); ?>
		<input type="hidden" name="connection_id" value="<?php echo esc_attr( $conn_id ? $conn_id : 'new' ); ?>">
		
		<div class="notion-sync-for-wp-section">
			<h2 class="title"><?php esc_html_e( 'Connection Identity', 'notion-sync-for-wp' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="notion_sync_connection_name"><?php esc_html_e( 'Connection Name', 'notion-sync-for-wp' ); ?></label></th>
					<td>
						<input type="text" name="notion_sync_mapping[connection_name]" id="notion_sync_connection_name" value="<?php echo esc_attr( $conn_name ); ?>" class="regular-text" required placeholder="e.g. Blog Sync">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="notion_sync_database_id"><?php esc_html_e( 'Notion Database ID', 'notion-sync-for-wp' ); ?></label></th>
					<td>
						<input type="text" name="notion_sync_mapping[database_id]" id="notion_sync_database_id" value="<?php echo esc_attr( $db_id ); ?>" class="regular-text" required placeholder="Paste your Notion Database ID here">
						<button type="button" id="notion-sync-for-wp-test-connection" class="button button-secondary">
							<?php esc_html_e( 'Test Connection', 'notion-sync-for-wp' ); ?>
						</button>
						<span class="spinner"></span>
					</td>
				</tr>
			</table>
		</div>

		<div class="notion-sync-for-wp-section">
			<h2 class="title"><?php esc_html_e( 'Sync Destination', 'notion-sync-for-wp' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="notion_sync_post_type"><?php esc_html_e( 'Target Post Type', 'notion-sync-for-wp' ); ?></label></th>
					<td>
						<select name="notion_sync_mapping[post_type]" id="notion_sync_post_type" class="regular-text">
							<?php foreach ( $post_types as $pt_slug => $pt_obj ) : ?>
								<option value="<?php echo esc_attr( $pt_slug ); ?>" <?php selected( $post_type, $pt_slug ); ?>>
									<?php echo esc_html( $pt_obj->label ); ?> (<?php echo esc_html( $pt_slug ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="notion_sync_output_mode"><?php esc_html_e( 'Output Mode', 'notion-sync-for-wp' ); ?></label></th>
					<td>
						<select name="notion_sync_mapping[output_mode]" id="notion_sync_output_mode" class="regular-text">
							<option value="classic" <?php selected( $output_mode, 'classic' ); ?>><?php esc_html_e( 'Classic (HTML)', 'notion-sync-for-wp' ); ?></option>
							<option value="gutenberg" <?php selected( $output_mode, 'gutenberg' ); ?>><?php esc_html_e( 'Gutenberg (Blocks)', 'notion-sync-for-wp' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		</div>

			<div id="notion-sync-for-wp-dynamic-mapping">
				<?php $this->render_mapping_parts( $post_type, $db_id, $mapping ); ?>
			</div>

			<div class="notion-sync-for-wp-actions">
				<?php submit_button( __( 'Save Mapping', 'notion-sync-for-wp' ) ); ?>
			</div>
		</form>
</div>
