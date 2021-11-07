<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsPreviewController {

	/**
	 * @var object $view
	 */
	private $view;

	/**
	 * @var FrmForm $form
	 */
	private $form;

	/**
	 * @var array<stdClass> $entries (indexed by entry id)
	 */
	private $entries;

	/**
	 * @var int $shortcode_count used to count shortcode index in self::process_shortcodes
	 */
	private $shortcode_count;

	private function __construct( $view_id ) {
		$this->view = FrmViewsDisplay::getOne( $view_id );
		if ( ! $this->view ) {
			wp_send_json_error( 'unable to find view' );
		}

		$detail_slug           = FrmAppHelper::get_param( 'detailSlug', '', 'post', 'sanitize_text_field' );
		$this->view->frm_param = $detail_slug ? $detail_slug : 'entry';

		$parameter_value      = FrmAppHelper::get_param( 'parameterValue', '', 'post', 'sanitize_text_field' );
		$this->view->frm_type = $parameter_value;

		$form_id = FrmAppHelper::get_param( 'form', '', 'post', 'absint' );
		if ( ! $form_id ) {
			wp_send_json_error( 'view requires a form data source' );
		}

		$this->form = FrmForm::getOne( $form_id );
	}

	/**
	 * Check for formidable views AJAX requests and handle routing
	 *
	 * @param string $action sent via $_POST['action'].
	 */
	public static function route_ajax( $action ) {
		FrmAppHelper::permission_check( 'frm_edit_displays' );
		check_ajax_referer( 'frm_ajax', 'nonce' );
		switch ( $action ) {
			case 'frm_views_process_box_preview':
				self::handle_process_box_preview_routing();
				break;
		}
	}

	private static function handle_process_box_preview_routing() {
		$view_id = FrmAppHelper::get_param( 'view', '', 'post', 'absint' );
		if ( ! $view_id ) {
			wp_send_json_error( 'unable to preview without a specified view id' );
		}

		$controller = new self( $view_id );
		$controller->process_box_preview();
	}

	private function process_box_preview() {
		$data = FrmAppHelper::get_param( 'data', '', 'post' );
		$this->set_entries_for_preview();
		$response_data      = $this->process_entries( $data );
		$additional_details = array(
			'entryCount' => $this->get_entry_count(),
		);
		$tab                = FrmAppHelper::get_param( 'tab', '', 'post', 'sanitize_text_field' );
		if ( 'listing' === $tab ) {
			$before_content = FrmAppHelper::get_param( 'beforeContent', '', 'post' );
			if ( $before_content ) {
				$additional_details['beforeContent'] = do_shortcode( $before_content );
			}

			$after_content = FrmAppHelper::get_param( 'afterContent', '', 'post' );
			if ( $after_content ) {
				$additional_details['afterContent'] = do_shortcode( $after_content );
			}
		}
		$additional_details = $this->maybe_add_google_graphs_to_additional_details( $additional_details );
		$response_data[]    = $additional_details;
		wp_send_json_success( $response_data );
	}

	/**
	 * @param array $details
	 * @return array
	 */
	private function maybe_add_google_graphs_to_additional_details( $details ) {
		global $frm_vars;
		if ( ! empty( $frm_vars['google_graphs'] ) ) {
			$details['googleGraphs'] = $frm_vars['google_graphs'];
		}
		return $details;
	}

	private function get_entry_count() {
		$where = FrmViewsPreviewHelper::get_last_where_query();
		if ( isset( $where['it.id'] ) && ! $where['it.id'] ) {
			return 0;
		}
		return FrmEntry::getRecordCount( $where );
	}

	/**
	 * Set a small set of real entry data to use for our preview
	 */
	private function set_entries_for_preview() {
		$filter    = FrmViewsPreviewHelper::sanitize_filter( FrmAppHelper::get_param( 'filter', '', 'post' ) );
		$sort      = FrmViewsPreviewHelper::sanitize_sort( FrmAppHelper::get_param( 'sort', '', 'post' ) );
		$limit     = $this->get_limit_value_from_post_request();
		$page_size = $this->get_page_size_value_from_post_request();
		$entry_ids = FrmViewsPreviewHelper::get_ordered_entry_ids( $this->form->id, $filter, $sort, $limit, $page_size );

		if ( ! $entry_ids ) {
			$this->entries = array();
			return;
		}

		$where         = array( 'it.id' => $entry_ids );
		$order_by      = 'ORDER BY FIELD(it.id,' . implode( ',', $entry_ids ) . ')';
		$this->entries = FrmEntry::getAll( $where, $order_by );
	}

	/**
	 * @return int|string
	 */
	private function get_limit_value_from_post_request() {
		return $this->get_absint_value_from_post_request_with_empty_string_support( 'limit' );
	}

	/**
	 * @return int|string
	 */
	private function get_page_size_value_from_post_request() {
		return $this->get_absint_value_from_post_request_with_empty_string_support( 'pageSize' );
	}

	/**
	 * Limit and page size can be empty strings, and absint converts that to a 0, which we don't want.
	 * This function will only use absint on numbers, and everything else is treated as no limit.
	 *
	 * @param string $key 'limit' or 'pageSize'.
	 * @return string|int
	 */
	private function get_absint_value_from_post_request_with_empty_string_support( $key ) {
		$value = FrmAppHelper::get_param( $key, '', 'post' );
		if ( is_numeric( $value ) ) {
			return absint( $value );
		}
		return '';
	}

	/**
	 * @param string $data sent from the editor.
	 * @return array the processed response
	 */
	private function process_entries( $data ) {
		if ( ! $this->entries ) {
			return array();
		}

		add_filter( 'frm_display_entry_content', 'FrmProContent::replace_shortcodes', 10, 7 );

		$keys          = range( 0, count( $this->entries ) - 1 );
		$response_data = array_fill_keys( $keys, array() );

		if ( ! $data ) {
			$box_id      = 0;
			$entry_index = 0;
			foreach ( $this->entries as $entry ) {
				$response_data[ $entry_index ++ ][ $box_id ] = '';
			}
		} elseif ( ! is_array( $data ) ) {
			// handle single column format (backwards compatibility)
			$box_id      = 0;
			$entry_index = 0;
			$content     = $data;
			foreach ( $this->entries as $entry ) {
				$response_data[ $entry_index ++ ][ $box_id ] = $this->process_shortcodes( $entry, $content );
			}
		} else {
			// handle layout format
			foreach ( $data as $box_data ) {
				if ( ! isset( $box_data['box'] ) || empty( $box_data['content'] ) ) {
					continue;
				}

				$box_id      = absint( $box_data['box'] );
				$content     = $box_data['content'];
				$entry_index = 0;
				foreach ( $this->entries as $entry ) {
					$response_data[ $entry_index ++ ][ $box_id ] = $this->process_shortcodes( $entry, $content );
				}
			}
		}

		return $response_data;
	}

	/**
	 * @param object $entry
	 * @param string $content
	 * @return string
	 */
	private function process_shortcodes( $entry, $content ) {
		$this->remove_media_library_posts_filter();

		// some shortcodes require this global is set (like frm-entry-links).
		FrmViewsAppHelper::force_view_as_post_global( $this->view );

		$shortcodes      = FrmProDisplaysHelper::get_shortcodes( $content, $this->form->id );
		$args            = array(
			'count'     => $this->shortcode_count ++,
			'entry_ids' => array_keys( $this->entries ),
		);
		$preview_content = $this->replace_event_date_shortcode( $entry, $content );
		$preview_content = apply_filters( 'frm_display_entry_content', $preview_content, $entry, $shortcodes, $this->view, 'all', 'odd', $args );
		FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $preview_content );
		$filter = FrmAppHelper::get_param( 'activePreviewFilter', '', 'post', 'sanitize_text_field' );
		FrmViewsDisplaysController::maybe_filter_content( array( 'filter' => $filter ), $preview_content );

		return $preview_content;
	}

	/**
	 * @param object $entry
	 * @param string $content
	 * @return string
	 */
	private function replace_event_date_shortcode( $entry, $content ) {
		$current_entry_date = $this->get_current_entry_date( $entry );
		$content            = str_replace( array( '[event_date]', '[event_date ' ), array( '[calendar_date]', '[calendar_date ' ), $content );
		$content            = FrmProContent::replace_calendar_date_shortcode( $content, $current_entry_date );
		return $content;
	}

	/**
	 * @param object $entry
	 * @return string
	 */
	private function get_current_entry_date( $entry ) {
		$options = get_post_meta( $this->view->ID, 'frm_options', true );

		if ( ! $options ) {
			return '';
		}

		$options            = maybe_unserialize( $options );
		$current_entry_date = '';

		if ( isset( $options['date_field_id'] ) ) {
			$date_field_id = $options['date_field_id'];
			if ( is_numeric( $date_field_id ) ) {
				$current_entry_date = FrmDb::get_var(
					'frm_item_metas',
					array(
						'item_id'  => $entry->id,
						'field_id' => $date_field_id,
					),
					'meta_value'
				);
			} elseif ( 'created_at' === $date_field_id ) {
				$current_entry_date = $entry->created_at;
			} elseif ( 'updated_at' === $date_field_id ) {
				$current_entry_date = $entry->updated_at;
			}
		}

		return $current_entry_date;
	}

	/**
	 * Remove a file field filter that prevents the preview from rendering formidable images in a gallery.
	 * This filter is added everywhere on the admin side, so galleries ultimately don't work on side side without disabling this filter.
	 */
	private function remove_media_library_posts_filter() {
		remove_action( 'pre_get_posts', 'FrmProFileField::filter_media_library', 99 );
	}
}
