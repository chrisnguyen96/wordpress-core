<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="post-type-frm_display">
	<div id="frm_top_bar" class="frm_view_editor_publish_wrapper">
		<div id="frm-publishing">	
			<?php FrmViewsEditorController::publish_button(); ?>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=formidable' ) ); ?>" class="frm-header-logo">
			<?php FrmAppHelper::show_header_logo(); ?>
		</a>
		<div id="frm_bs_dropdown">
			<h1>
				<span><?php esc_html_e( 'Views', 'formidable-views' ); ?></span>
			</h1>
		</div>
		<div style="clear: both;"></div>
	</div>
</div>
