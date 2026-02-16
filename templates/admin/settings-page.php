<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap notion-sync-for-wp-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<form method="post" action="options.php">
		<?php
		settings_fields( 'notion_sync_settings_group' );
		do_settings_sections( 'notion-sync-for-wp' );
		?>
		<div class="notion-sync-for-wp-actions">
			<?php submit_button( __( 'Save Credentials', 'notion-sync-for-wp' ), 'primary', 'submit', false ); ?>
			<button type="button" id="notion-sync-for-wp-test-connection" class="button button-secondary">
				<?php esc_html_e( 'Test Connection', 'notion-sync-for-wp' ); ?>
			</button>
			<button type="button" id="notion-sync-for-wp-run-mass" class="button button-secondary" style="background: #2271b1; color: #fff; border-color: #2271b1;">
				<?php esc_html_e( 'Sync All "Ready" Posts', 'notion-sync-for-wp' ); ?>
			</button>
			<span class="spinner"></span>
		</div>
	</form>

	<div id="notion-sync-for-wp-test-result" style="margin-top: 20px;"></div>

	<div id="notion-sync-for-wp-mass-log" style="margin-top: 20px; display: none; background: #f0f0f1; padding: 15px; border-left: 4px solid #2271b1; border-radius: 4px;">
		<h3 style="margin-top: 0;"><?php esc_html_e( 'Sync Log', 'notion-sync-for-wp' ); ?></h3>
		<ul id="notion-sync-for-wp-log-list" style="margin-bottom: 0;"></ul>
	</div>
</div>
