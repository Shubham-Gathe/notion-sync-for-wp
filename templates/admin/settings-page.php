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
			<span class="spinner"></span>
		</div>
	</form>
</div>
