<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap notion-sync-for-wp-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p><?php esc_html_e( 'Map Notion database properties to WordPress post fields.', 'notion-sync-for-wp' ); ?></p>

	<?php if ( empty( $properties ) ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'No Notion properties found. Please check your API credentials and Database ID.', 'notion-sync-for-wp' ); ?></p>
		</div>
	<?php else : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'notion_sync_mapping_group' ); ?>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'WordPress Field', 'notion-sync-for-wp' ); ?></th>
						<th><?php esc_html_e( 'Notion Property', 'notion-sync-for-wp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $wp_fields as $notion_sync_key => $notion_sync_label ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $notion_sync_label ); ?></strong></td>
							<td>
								<select name="notion_sync_mapping[<?php echo esc_attr( $notion_sync_key ); ?>]" class="regular-text">
									<option value=""><?php esc_html_e( '-- Select Property --', 'notion-sync-for-wp' ); ?></option>
									<?php foreach ( $properties as $notion_sync_prop ) : ?>
										<option value="<?php echo esc_attr( $notion_sync_prop['id'] ); ?>" <?php selected( isset( $mapping[ $notion_sync_key ] ) ? $mapping[ $notion_sync_key ] : '', $notion_sync_prop['id'] ); ?>>
											<?php echo esc_html( $notion_sync_prop['name'] ); ?> (<?php echo esc_html( $notion_sync_prop['type'] ); ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2 class="title"><?php esc_html_e( 'Custom Meta Mapping', 'notion-sync-for-wp' ); ?></h2>
			<p><?php esc_html_e( 'Map Notion properties to specific WordPress Custom Fields (Post Meta).', 'notion-sync-for-wp' ); ?></p>
			
			<table class="wp-list-table widefat fixed striped" id="notion-sync-for-wp-meta-mapping">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Custom Meta Key', 'notion-sync-for-wp' ); ?></th>
						<th><?php esc_html_e( 'Notion Property', 'notion-sync-for-wp' ); ?></th>
						<th style="width: 50px;"></th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$notion_sync_custom_meta = isset( $mapping['custom_meta'] ) ? $mapping['custom_meta'] : [];
					foreach ( $notion_sync_custom_meta as $notion_sync_index => $notion_sync_meta_mapping ) : 
						if ( empty( $notion_sync_meta_mapping['key'] ) ) continue;
					?>
						<tr>
							<td>
								<input type="text" name="notion_sync_mapping[custom_meta][<?php echo $notion_sync_index; ?>][key]" value="<?php echo esc_attr( $notion_sync_meta_mapping['key'] ); ?>" class="regular-text" placeholder="meta_key">
							</td>
							<td>
								<select name="notion_sync_mapping[custom_meta][<?php echo $notion_sync_index; ?>][property]" class="regular-text">
									<option value=""><?php esc_html_e( '-- Select Property --', 'notion-sync-for-wp' ); ?></option>
									<?php foreach ( $properties as $notion_sync_prop ) : ?>
										<option value="<?php echo esc_attr( $notion_sync_prop['id'] ); ?>" <?php selected( $notion_sync_meta_mapping['property'], $notion_sync_prop['id'] ); ?>>
											<?php echo esc_html( $notion_sync_prop['name'] ); ?> (<?php echo esc_html( $notion_sync_prop['type'] ); ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</td>
							<td><button type="button" class="button remove-meta-row">&times;</button></td>
						</tr>
					<?php endforeach; ?>
					
					<!-- Template for new rows (handled by JS ideally, but for now just one empty row) -->
					<tr>
						<td>
							<input type="text" name="notion_sync_mapping[custom_meta][new][key]" value="" class="regular-text" placeholder="meta_key">
						</td>
						<td>
							<select name="notion_sync_mapping[custom_meta][new][property]" class="regular-text">
								<option value=""><?php esc_html_e( '-- Select Property --', 'notion-sync-for-wp' ); ?></option>
								<?php foreach ( $properties as $notion_sync_prop ) : ?>
									<option value="<?php echo esc_attr( $notion_sync_prop['id'] ); ?>">
										<?php echo esc_html( $notion_sync_prop['name'] ); ?> (<?php echo esc_html( $notion_sync_prop['type'] ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td></td>
					</tr>
				</tbody>
			</table>

			<div class="notion-sync-for-wp-actions">
				<?php submit_button( __( 'Save Mapping', 'notion-sync-for-wp' ) ); ?>
			</div>
		</form>
	<?php endif; ?>
</div>
