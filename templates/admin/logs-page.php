<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap notion-sync-for-wp-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p><?php esc_html_e( 'View the history of Notion synchronization tasks and run manual updates.', 'notion-sync-for-wp' ); ?></p>

	<div class="notion-sync-for-wp-section">
		<h2><?php esc_html_e( 'Manual Synchronization', 'notion-sync-for-wp' ); ?></h2>
		<p><?php esc_html_e( 'Choose a connection and trigger a manual sync batch for posts marked as "Ready" in Notion.', 'notion-sync-for-wp' ); ?></p>
		
		<div class="sync-controls">
			<select id="notion_sync_target_connection" class="regular-text">
				<option value=""><?php esc_html_e( '-- Select Connection --', 'notion-sync-for-wp' ); ?></option>
				<?php foreach ( ( $connections ?? [] ) as $id => $conn ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $active_conn ?? '', $id ); ?>>
						<?php echo esc_html( $conn['name'] ); ?> (<?php echo esc_html( $conn['post_type'] ); ?>)
					</option>
				<?php endforeach; ?>
			</select>
			
			<button type="button" class="button button-primary" id="notion-sync-for-wp-run-mass">
				<?php esc_html_e( 'Run Sync', 'notion-sync-for-wp' ); ?>
			</button>
			<span class="spinner"></span>
		</div>

		<div id="notion-sync-for-wp-mass-log" style="margin-top: 20px; display: none;">
			<div class="notion-sync-log-container" style="background: #f0f0f1; border: 1px solid #ccd0d4; padding: 15px; max-height: 400px; overflow-y: auto; border-radius: 4px;">
				<ul id="notion-sync-for-wp-log-list" style="margin: 0; padding: 0; list-style: none; font-family: monospace;"></ul>
			</div>
		</div>
	</div>
</div>
