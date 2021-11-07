<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm_page_container frm_forms" style="background: #fff;">
	<?php
	FrmAppHelper::get_admin_header(
		array(
			'form'    => $form,
			'publish' => array( 'FrmViewsEditorController::publish_button', array() ),
		)
	);
	?>
</div>
