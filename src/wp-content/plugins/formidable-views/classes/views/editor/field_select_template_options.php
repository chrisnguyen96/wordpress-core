<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<option value=""><?php esc_html_e( '&mdash; Select &mdash;', 'formidable-views' ); ?></option>
<option value="created_at">
	<?php esc_html_e( 'Entry creation date', 'formidable-views' ); ?>
</option>
<option value="updated_at">
	<?php esc_html_e( 'Entry updated date', 'formidable-views' ); ?>
</option>
<option value="id">
	<?php esc_html_e( 'Entry ID', 'formidable-views' ); ?>
</option>
<option value="item_key">
	<?php esc_html_e( 'Entry key', 'formidable-views' ); ?>
</option>
<option value="post_id">
	<?php esc_html_e( 'Post ID', 'formidable-views' ); ?>
</option>
<option value="parent_item_id">
	<?php esc_html_e( 'Parent entry ID', 'formidable-views' ); ?>
</option>
<option value="is_draft">
	<?php esc_html_e( 'Entry status', 'formidable-views' ); ?>
</option>
<?php
if ( $form_id ) {
	FrmProFieldsHelper::get_field_options( $form_id, '', 'not', array( 'break', 'end_divider', 'divider', 'file', 'captcha', 'form' ), array( 'inc_sub' => 'include' ) );
}
?>
<option value="ip">
	<?php esc_html_e( 'IP', 'formidable-views' ); ?>
</option>
