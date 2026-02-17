<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap notion-sync-for-wp-wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html__( 'Notion Connections', 'notion-sync-for-wp' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=notion-sync-for-wp-mapping&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Connection', 'notion-sync-for-wp' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Connection deleted.', 'notion-sync-for-wp' ); ?></p></div>
	<?php endif; ?>

	<table class="wp-list-table widefat fixed striped table-view-list connections">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-name"><?php esc_html_e( 'Connection Name', 'notion-sync-for-wp' ); ?></th>
				<th scope="col" class="manage-column column-db-id"><?php esc_html_e( 'Database ID', 'notion-sync-for-wp' ); ?></th>
				<th scope="col" class="manage-column column-post-type"><?php esc_html_e( 'Target Post Type', 'notion-sync-for-wp' ); ?></th>
				<th scope="col" class="manage-column column-actions"><?php esc_html_e( 'Actions', 'notion-sync-for-wp' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $connections ) ) : ?>
				<tr>
					<td colspan="4"><?php esc_html_e( 'No connections found. Click "Add New Connection" to get started.', 'notion-sync-for-wp' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $connections as $id => $conn ) : ?>
					<tr>
						<td class="column-name">
							<strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=notion-sync-for-wp-mapping&id=' . $id ) ); ?>"><?php echo esc_html( $conn['name'] ); ?></a></strong>
						</td>
						<td class="column-db-id"><code><?php echo esc_html( $conn['database_id'] ); ?></code></td>
						<td class="column-post-type"><span class="post-state"><?php echo esc_html( $conn['post_type'] ); ?></span></td>
						<td class="column-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=notion-sync-for-wp-mapping&id=' . $id ) ); ?>"><?php esc_html_e( 'Edit', 'notion-sync-for-wp' ); ?></a> | 
							<a href="#" class="notion-sync-test-conn" data-dbid="<?php echo esc_attr( $conn['database_id'] ); ?>"><?php esc_html_e( 'Test', 'notion-sync-for-wp' ); ?></a> | 
							<a href="#" class="notion-sync-run-single" data-id="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Sync Now', 'notion-sync-for-wp' ); ?></a> | 
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=notion-sync-for-wp&action=delete&id=' . $id ), 'delete_connection_' . $id ) ); ?>" class="submitdelete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this connection?', 'notion-sync-for-wp' ); ?>');" style="color:#d63638;"><?php esc_html_e( 'Delete', 'notion-sync-for-wp' ); ?></a>
							<span class="spinner"></span>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
