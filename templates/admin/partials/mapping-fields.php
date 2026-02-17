<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2 class="title"><?php esc_html_e( 'Field Mapping', 'notion-sync-for-wp' ); ?></h2>
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

<?php if ( ! empty( $taxonomies ) ) : ?>
	<h2 class="title"><?php esc_html_e( 'Taxonomy Mapping', 'notion-sync-for-wp' ); ?></h2>
	<p><?php esc_html_e( 'Map Notion multi-select or select properties to WordPress taxonomies.', 'notion-sync-for-wp' ); ?></p>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'WP Taxonomy', 'notion-sync-for-wp' ); ?></th>
				<th><?php esc_html_e( 'Notion Property', 'notion-sync-for-wp' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $taxonomies as $tax_slug => $tax_obj ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $tax_obj->label ); ?></strong> (<?php echo esc_html( $tax_slug ); ?>)</td>
					<td>
						<select name="notion_sync_mapping[taxonomies][<?php echo esc_attr( $tax_slug ); ?>]" class="regular-text">
							<option value=""><?php esc_html_e( '-- Select Property --', 'notion-sync-for-wp' ); ?></option>
							<?php foreach ( $properties as $notion_sync_prop ) : ?>
								<option value="<?php echo esc_attr( $notion_sync_prop['id'] ); ?>" <?php selected( isset( $mapping['taxonomies'][ $tax_slug ] ) ? $mapping['taxonomies'][ $tax_slug ] : '', $notion_sync_prop['id'] ); ?>>
									<?php echo esc_html( $notion_sync_prop['name'] ); ?> (<?php echo esc_html( $notion_sync_prop['type'] ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
