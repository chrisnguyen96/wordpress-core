<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm_hidden">
	<?php
	FrmAppHelper::icon_by_class( 'frmfont frm_plus_icon' );
	?>
	<svg id="frm_view_icon_grid" class="frmsvg" style="transform: translateY(10px);">
		<use xlink:href="#frm_views_grid_icon"></use>
	</svg>
	<svg id="frm_views_calendar_type" class="frmsvg">
		<use xlink:href="#frm_views_calendar_icon"></use>
	</svg>
	<svg id="frm_views_table_type" class="frmsvg">
		<use xlink:href="#frm_views_table_icon"></use>
	</svg>
	<svg id="frm_views_editor_coming_soon_table" class="frmsvg">
		<use xlink:href="#frm_views_coming_soon_table"></use>
	</svg>
	<svg id="frm_views_editor_coming_soon_calendar" class="frmsvg">
		<use xlink:href="#frm_views_coming_soon_calendar"></use>
	</svg>
	<svg id="frm_views_editor_classic_view_icon" class="frmsvg" style="transform: translateY(10px);">
		<use xlink:href="#frm_classic_view_icon"></use>
	</svg>
	<div id="frm-hover-icons-template" class="frm-hover-icons">
		<a role="button" href="#" class="frm-create-view frm-create-hover-icon" aria-label="<?php esc_attr_e( 'Create view', 'formidable-views' ); ?>" title="<?php esc_attr_e( 'Create view', 'formidable-views' ); ?>">
			<svg class="frmsvg">
				<use xlink:href="#frm_plus_icon"></use>
			</svg>
		</a>
	</div>
</div>
