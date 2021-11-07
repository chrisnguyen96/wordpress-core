<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsDisplay {

	/**
	 * Create a blank view.
	 *
	 * @param int    $form_id
	 * @param string $show_count
	 * @return int view id
	 */
	public static function create( $form_id = 0, $show_count = 'all' ) {
		$view_id = wp_insert_post(
			array(
				'post_type' => FrmViewsDisplaysController::$post_type,
			)
		);
		$options = self::get_default_opts_for_create();

		if ( $form_id ) {
			add_post_meta( $view_id, 'frm_form_id', $form_id );
		}

		if ( 'grid' === $show_count ) {
			add_post_meta( $view_id, 'frm_grid_view', 1 );
			$show_count = 'all';
		} elseif ( 'table' === $show_count ) {
			$show_count = 'all';
		}

		add_post_meta( $view_id, 'frm_show_count', $show_count );
		add_post_meta( $view_id, 'frm_options', $options );

		return $view_id;
	}

	/**
	 * FrmViewsDisplay::create sets frm_options based off of defaults defined from FrmViewsDisplaysHelper::get_default_opts().
	 * However, FrmViewsDisplaysHelper::get_default_opts() includes information that we do not want saved in frm_options.
	 * This includes the keys for other values like name, content, type, etc.
	 * This function removes the keys we don't want to save in frm_options and return only the defaults we want.
	 *
	 * @return array
	 */
	private static function get_default_opts_for_create() {
		$options                     = FrmViewsDisplaysHelper::get_default_opts();
		$keys_that_map_to_view       = array( 'name', 'description', 'content' );
		$keys_that_map_to_frm_prefix = FrmViewsDisplaysHelper::get_keys_that_map_to_an_frm_prefixed_option();
		$old_keys_we_do_not_need     = array( 'display_key' );
		$keys_to_remove              = array_merge( $keys_that_map_to_view, $keys_that_map_to_frm_prefix, $old_keys_we_do_not_need );

		foreach ( $keys_to_remove as $key ) {
			unset( $options[ $key ] );
		}

		return $options;
	}

	public static function duplicate( $id, $copy_keys = false, $blog_id = false ) {
		global $wpdb;

		$values = self::getOne( $id, $blog_id, true );

		if ( ! $values || ! is_numeric( $values->frm_form_id ) ) {
			return false;
		}

		$new_values = array();
		foreach ( array( 'post_name', 'post_title', 'post_excerpt', 'post_content', 'post_status', 'post_type' ) as $k ) {
			$new_values[ $k ] = $values->{$k};
			unset( $k );
		}

		$meta = array();
		foreach ( array( 'form_id', 'entry_id', 'dyncontent', 'param', 'type', 'show_count', 'active_preview_filter' ) as $k ) {
			$meta[ $k ] = $values->{'frm_' . $k};
			unset( $k );
		}

		$default         = FrmViewsDisplaysHelper::get_default_opts();
		$meta['options'] = array();
		foreach ( $default as $k => $v ) {
			if ( isset( $meta[ $k ] ) ) {
				continue;
			}

			$meta['options'][ $k ] = $values->{'frm_' . $k};
			unset( $k, $v );
		}
		$meta['options']['copy'] = false;

		if ( $blog_id ) {
			$old_form        = FrmForm::getOne( $values->frm_form_id, $blog_id );
			$new_form        = FrmForm::getOne( $old_form->form_key );
			$meta['form_id'] = $new_form->id;
		} else {
			$meta['form_id'] = $values->frm_form_id;
		}

		$post_ID    = wp_insert_post( $new_values );
		$new_values = array_merge( (array) $new_values, $meta );

		self::update( $post_ID, $new_values );
		FrmViewsLayout::duplicate_layouts( $id, $post_ID );

		return $post_ID;
	}

	public static function update( $id, $values ) {
		$new_values              = array();
		$new_values['frm_param'] = isset( $values['param'] ) ? sanitize_title_with_dashes( $values['param'] ) : '';

		$fields = array( 'dyncontent', 'type', 'show_count', 'form_id', 'entry_id', 'active_preview_filter' );
		foreach ( $fields as $field ) {
			if ( isset( $values[ $field ] ) ) {
				$new_values[ 'frm_' . $field ] = $values[ $field ];
			}
		}

		if ( isset( $values['options'] ) ) {
			$new_values['frm_options'] = array();
			foreach ( $values['options'] as $key => $value ) {
				$new_values['frm_options'][ $key ] = $value;
			}
		}

		foreach ( $new_values as $key => $val ) {
			if ( 'frm_param' === $key ) {
				$last_param = get_post_meta( $id, $key, true );
				if ( $last_param != $val ) {
					update_post_meta( $id, $key, $val );
					add_rewrite_endpoint( $val, EP_PERMALINK | EP_PAGES );
					flush_rewrite_rules();
				}
			} else {
				update_post_meta( $id, $key, $val );
			}

			unset( $key, $val );
		}
	}

	public static function getOne( $id, $blog_id = false, $get_meta = false, $atts = array() ) {
		global $wpdb;

		if ( $blog_id && is_multisite() ) {
			switch_to_blog( $blog_id );
		}

		$id = sanitize_title( $id );
		if ( ! is_numeric( $id ) ) {
			$id = FrmDb::get_var(
				$wpdb->posts,
				array(
					'post_name'     => $id,
					'post_type'     => 'frm_display',
					'post_status !' => 'trash',
				),
				'ID'
			);

			if ( is_multisite() && empty( $id ) ) {
				return false;
			}
		}

		if ( empty( $id ) ) {
			// don't let it get the current page
			return false;
		}

		$post = get_post( $id );
		if ( ! $post || 'frm_display' !== $post->post_type || 'trash' === $post->post_status ) {
			$args  = array(
				'post_type'   => 'frm_display',
				'meta_key'    => 'frm_old_id',
				'meta_value'  => $id,
				'numberposts' => 1,
				'post_status' => 'publish',
			);
			$posts = get_posts( $args );

			if ( $posts ) {
				$post = reset( $posts );
			}
		}

		if ( $post && 'trash' === $post->post_status ) {
			return false;
		}

		if ( $post && $get_meta ) {
			$check_post = isset( $atts['check_post'] ) ? $atts['check_post'] : false;
			$post       = FrmViewsDisplaysHelper::setup_edit_vars( $post, $check_post );
		}

		if ( $blog_id && is_multisite() ) {
			restore_current_blog();
		}

		return $post;
	}

	public static function getAll( $where = array(), $order_by = 'post_date', $limit = 99 ) {
		if ( ! is_numeric( $limit ) ) {
			$limit = (int) $limit;
		}

		$order = 'DESC';
		if ( strpos( $order_by, ' ' ) ) {
			list( $order_by, $order ) = explode( ' ', $order_by );
		}

		$query = array(
			'numberposts' => $limit,
			'orderby'     => $order_by,
			'order'       => $order,
			'post_type'   => 'frm_display',
			'post_status' => array( 'publish', 'private' ),
		);
		$query = array_merge( (array) $where, $query );

		$results = get_posts( $query );
		return $results;
	}

	/**
	 * Check for a qualified view.
	 * Qualified:   1. set to show calendar or dynamic
	 *              2. published
	 *              3. form has posts/entry is linked to a post
	 *
	 * @param array $args
	 */
	public static function get_auto_custom_display( $args ) {
		$defaults = array(
			'post_id'  => false,
			'form_id'  => false,
			'entry_id' => false,
		);
		$args     = wp_parse_args( $args, $defaults );

		global $wpdb;

		if ( $args['form_id'] ) {
			$display_ids = self::get_display_ids_by_form( $args['form_id'] );

			if ( ! $display_ids ) {
				return false;
			}

			if ( ! $args['post_id'] && ! $args['entry_id'] ) {
				// does form have posts?
				$args['entry_id'] = FrmDb::get_var( 'frm_items', array( 'form_id' => $args['form_id'] ), 'post_id' );
			}
		}

		if ( $args['post_id'] && ! $args['entry_id'] ) {
			// is post linked to an entry?
			$args['entry_id'] = FrmDb::get_var( $wpdb->prefix . 'frm_items', array( 'post_id' => $args['post_id'] ) );
		}

		// this post does not have an auto display
		if ( ! $args['entry_id'] ) {
			return false;
		}

		$query = array(
			'pm.meta_key'   => 'frm_show_count',
			'post_type'     => 'frm_display',
			'pm.meta_value' => array( 'dynamic', 'calendar', 'one' ),
			'p.post_status' => 'publish',
		);

		if ( isset( $display_ids ) ) {
			$query['p.ID'] = $display_ids;
		}

		$display = FrmDb::get_row( $wpdb->posts . ' p LEFT JOIN ' . $wpdb->postmeta . ' pm ON (p.ID = pm.post_ID)', $query, 'p.*', array( 'order_by' => 'p.ID ASC' ) );

		return $display;
	}

	public static function get_display_ids_by_form( $form_id ) {
		global $wpdb;
		return FrmDb::get_col(
			$wpdb->postmeta,
			array(
				'meta_key'   => 'frm_form_id',
				'meta_value' => $form_id,
			),
			'post_ID'
		);
	}

	public static function get_form_custom_display( $form_id ) {
		global $wpdb;

		$display_ids = self::get_display_ids_by_form( $form_id );

		if ( ! $display_ids ) {
			return false;
		}

		$display = FrmDb::get_row(
			$wpdb->posts . ' p LEFT JOIN ' . $wpdb->postmeta . ' pm ON (p.ID = pm.post_ID)',
			array(
				'pm.meta_key'   => 'frm_show_count',
				'post_type'     => 'frm_display',
				'p.ID'          => $display_ids,
				'pm.meta_value' => array( 'dynamic', 'calendar', 'one' ),
				'p.post_status' => 'publish',
			),
			'p.*',
			array( 'order_by' => 'p.ID ASC' )
		);

		return $display;
	}

	/**
	 * @param int $form_id
	 * @return array
	 */
	public static function get_form_action_displays( $form_id ) {
		global $wpdb;

		$where       = array(
			'meta_key'   => 'frm_form_id',
			'meta_value' => $form_id,
		);
		$display_ids = FrmDb::get_col( $wpdb->postmeta, $where, 'post_ID' );

		if ( ! $display_ids ) {
			return array();
		}

		$query_args = array(
			'pm.meta_key'   => 'frm_show_count',
			'post_type'     => 'frm_display',
			'pm.meta_value' => array( 'dynamic', 'calendar', 'one' ),
			'p.post_status' => array( 'publish', 'private' ),
			'p.ID'          => $display_ids,
		);
		$displays   = FrmDb::get_results(
			$wpdb->posts . ' p LEFT JOIN ' . $wpdb->postmeta . ' pm ON (p.ID = pm.post_ID)',
			$query_args,
			'p.ID, p.post_title',
			array( 'order_by' => 'p.post_title ASC' )
		);

		return $displays;
	}

	/**
	 * @param int     $form_id
	 * @param WP_Post $form_action
	 * @return object|false
	 */
	public static function get_form_action_display( $form_id, $form_action ) {
		$display = false;
		if ( isset( $form_action->post_content['display_id'] ) ) {
			if ( is_numeric( $form_action->post_content['display_id'] ) ) {
				$display = self::getOne( $form_action->post_content['display_id'], false, true );
			}
		} elseif ( ! is_numeric( $form_action->post_content['post_content'] ) ) {
			$display = self::get_form_custom_display( $form_id );
			if ( $display ) {
				$display = FrmViewsDisplaysHelper::setup_edit_vars( $display, true );
			}
		}
		return $display;
	}

	public static function save_wppost_action_displays( $settings, $action ) {
		$form_id  = $action['menu_order'];
		$settings = FrmProForm::save_wppost_actions( $settings, $action );

		if ( empty( $settings['display_id'] ) ) {
			return $settings;
		}

		if ( is_numeric( $settings['display_id'] ) ) {
			// updating View
			$type = get_post_meta( $settings['display_id'], 'frm_show_count', true );

			if ( 'one' === $type ) {
				$display                 = get_post( $settings['display_id'], ARRAY_A );
				$display['post_content'] = $_POST['dyncontent']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				wp_insert_post( $display );
			} else {
				update_post_meta( $settings['display_id'], 'frm_dyncontent', $_POST['dyncontent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		} elseif ( 'new' === $settings['display_id'] ) {
			// Get form name for View title
			$form = FrmForm::getOne( $form_id );
			if ( ! empty( $form->name ) ) {
				$post_title = $form->name;
			} else {
				$post_title = __( 'Single Post', 'formidable-views' );
			}

			// create new
			$cd_values = array(
				'post_status'  => 'publish',
				'post_type'    => 'frm_display',
				'post_title'   => $post_title,
				'post_excerpt' => __( 'Used for the single post page', 'formidable-views' ),
				'post_content' => $_POST['dyncontent'], // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			);

			$display_id             = wp_insert_post( $cd_values );
			$settings['display_id'] = $display_id;

			unset( $cd_values );

			update_post_meta( $display_id, 'frm_param', 'entry' );
			update_post_meta( $display_id, 'frm_type', 'display_key' );
			update_post_meta( $display_id, 'frm_show_count', 'one' );
			update_post_meta( $display_id, 'frm_form_id', $form_id );
		}

		return $settings;
	}

	/**
	 * Get the ID of a View using the key
	 *
	 * @param string $key
	 * @return int
	 */
	public static function get_id_by_key( $key ) {
		$id = FrmDb::get_var( 'posts', array( 'post_name' => sanitize_title( $key ) ) );
		return $id;
	}

	public static function post_options_for_views( $display, $form_id, $action ) {
		$displays = self::get_form_action_displays( $form_id );
		if ( ! $displays ) {
			$display = false;
		}
		$display_id_field_name = $action->get_field_name( 'display_id' );
		require FrmViewsAppHelper::plugin_path() . '/classes/views/actions/post_options.php';
	}

	/**
	 * Get ordered and filtered entries for Views
	 *
	 * @param array $where
	 * @param array $args
	 * @return array
	 */
	public static function get_view_results( $where, $args ) {
		global $wpdb;

		$defaults = array(
			'order_by_array' => array(),
			'order_array'    => array(),
			'limit'          => '',
			'posts'          => array(),
			'display'        => false,
		);

		$args               = wp_parse_args( $args, $defaults );
		$args['time_field'] = false;

		$query = array(
			'select' => 'SELECT it.id FROM ' . $wpdb->prefix . 'frm_items it',
			'where'  => $where,
			'order'  => 'ORDER BY it.created_at, it.id ASC',
		);

		// if order is set
		if ( ! empty( $args['order_by_array'] ) ) {
			self::prepare_entries_query( $query, $args );
		}

		$query = apply_filters( 'frm_view_order', $query, $args );

		if ( ! empty( $query['where'] ) ) {
			$query['where'] = FrmDb::prepend_and_or_where( 'WHERE ', $query['where'] );
		}

		$query['order'] = rtrim( $query['order'], ', ' );
		$query          = implode( ' ', $query ) . $args['limit'];
		$entry_ids      = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $entry_ids;
	}

	private static function prepare_entries_query( &$query, &$args ) {
		if ( in_array( 'rand', $args['order_by_array'], true ) ) {
			// If random is set, set the order to random
			$query['order'] = ' ORDER BY RAND()';
			return;
		}

		// Remove other ordering fields if created_at or updated_at is selected for first ordering field
		if ( reset( $args['order_by_array'] ) === 'created_at' || reset( $args['order_by_array'] ) === 'updated_at' ) {
			foreach ( $args['order_by_array'] as $o_key => $order_by_field ) {
				if ( is_numeric( $order_by_field ) ) {
					unset( $args['order_by_array'][ $o_key ] );
					unset( $args['order_array'][ $o_key ] );
				}
			}
			$numeric_order_array = array();
		} else {
			// Get number of fields in $args['order_by_array'] - this will not include created_at, updated_at, or random
			$numeric_order_array = array_filter( $args['order_by_array'], 'is_numeric' );
		}

		if ( ! count( $numeric_order_array ) ) {
			// If ordering by creation date and/or update date without any fields
			$query['order'] = ' ORDER BY';

			foreach ( $args['order_by_array'] as $o_key => $order_by ) {
				FrmDb::esc_order_by( $args['order_array'][ $o_key ] );
				$query['order'] .= ' it.' . sanitize_title( $order_by ) . ' ' . $args['order_array'][ $o_key ] . ', ';
				unset( $order_by );
			}
			return;
		}

		// If ordering by at least one field (not just created_at, updated_at, or entry ID)
		$order_fields = array();
		foreach ( $args['order_by_array'] as $o_key => $order_by_field ) {
			if ( is_numeric( $order_by_field ) ) {
				$order_fields[ $o_key ] = FrmField::getOne( $order_by_field );
			} else {
				$order_fields[ $o_key ] = $order_by_field;
			}
		}

		// Get all post IDs for this form
		$linked_posts = array();
		foreach ( $args['posts'] as $post_meta ) {
			$linked_posts[ $post_meta->post_id ] = $post_meta->id;
		}

		$first_order    = true;
		$query['order'] = 'ORDER BY ';
		foreach ( $order_fields as $o_key => $o_field ) {
			self::prepare_ordered_entries_query( $query, $args, $o_key, $o_field, $first_order );
			$first_order = false;
			unset( $o_field );
		}
	}

	private static function prepare_ordered_entries_query( &$query, &$args, $o_key, $o_field, $first_order ) {
		global $wpdb;

		$order = $args['order_array'][ $o_key ];
		FrmDb::esc_order_by( $order );

		$o_key = sanitize_title( $o_key );

		// if field is some type of post field
		if ( isset( $o_field->field_options['post_field'] ) && $o_field->field_options['post_field'] ) {

			// if field is custom field
			if ( 'post_custom' === $o_field->field_options['post_field'] ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$query['select'] .= $wpdb->prepare( ' LEFT JOIN ' . $wpdb->postmeta . ' pm' . $o_key . ' ON pm' . $o_key . '.post_id=it.post_id AND pm' . $o_key . '.meta_key = %s ', $o_field->field_options['custom_field'] );
				$query['order']  .= 'CASE when pm' . $o_key . '.meta_value IS NULL THEN 1 ELSE 0 END, pm' . $o_key . '.meta_value ';
				$query['order']  .= FrmProAppHelper::maybe_query_as_number( $o_field->type );
				$query['order']  .= $order . ', ';
			} elseif ( 'post_category' !== $o_field->field_options['post_field'] ) {
				// if field is a non-category post field
				$query['select'] .= $first_order ? ' INNER ' : ' LEFT ';
				$query['select'] .= 'JOIN ' . sanitize_title( $wpdb->posts ) . ' p' . $o_key . ' ON p' . $o_key . '.ID=it.post_id ';
				$query['order']  .= 'CASE p' . $o_key . '.' . sanitize_title( $o_field->field_options['post_field'] ) . " WHEN '' THEN 1 ELSE 0 END, p$o_key." . sanitize_title( $o_field->field_options['post_field'] ) . ' ' . $order . ', ';
			}
		} elseif ( is_numeric( $args['order_by_array'][ $o_key ] ) ) {
			// if ordering by a normal, non-post field
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$query['select'] .= $wpdb->prepare( ' LEFT JOIN ' . $wpdb->prefix . 'frm_item_metas em' . $o_key . ' ON em' . $o_key . '.item_id=it.id AND em' . $o_key . '.field_id=%d ', $o_field->id );
			$query['order']  .= 'CASE when em' . $o_key . '.meta_value IS NULL THEN 1 ELSE 0 END, em' . $o_key . '.meta_value ';
			$query['order']  .= FrmProAppHelper::maybe_query_as_number( $o_field->type );
			$query['order']  .= $order . ', ';

			// Meta value is only necessary for time field reordering and only if time field is first ordering field
			// Check if time field (for time field ordering)
			if ( $first_order && 'time' === $o_field->type ) {
				$args['time_field'] = $o_field;
			}
		} else {
			$query['order'] .= 'it.' . sanitize_title( $o_field ) . ' ' . $order . ', ';
		}
	}
}
