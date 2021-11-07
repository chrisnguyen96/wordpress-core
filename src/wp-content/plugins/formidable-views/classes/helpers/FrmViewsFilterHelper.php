<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmViewsFilterHelper {

	/**
	 * @var object $view
	 */
	private $view;

	/**
	 * @var array $atts
	 */
	private $atts;

	/**
	 * @var array $all_entry_ids_for_view
	 */
	private $all_entry_ids_for_view;

	/**
	 * @param object $view
	 * @param array  $atts
	 */
	public function __construct( $view, $atts ) {
		$this->view = $view;
		$this->atts = $atts;
	}

	/**
	 * @param array $where
	 */
	public function update_where_based_on_view_filters( &$where ) {
		$where_by_group     = $this->group_where();
		$count              = count( $where_by_group );
		$first_filter_group = reset( $where_by_group );
		$original_where     = $where;
		$where              = $this->get_where_for_filter_group( $where, $first_filter_group );

		$entry_ids = isset( $where['it.id'] ) ? $where['it.id'] : false;

		for ( $index = 1; $index < $count; ++ $index ) {
			$where_index = $this->get_where_for_filter_group( $original_where, $where_by_group[ $index ] );
			$keys        = array_keys( $where_by_group[ $index ] );
			$key         = reset( $keys );

			if ( isset( $where_index['it.id'] ) && 1 === count( $where_index['it.id'] ) ) {
				if ( false === $entry_ids ) {
					$entry_ids = $where_index['it.id'];
				} else {
					$or = ! empty( $this->view->frm_where_group_or[ $key ] );
					if ( $or ) {
						$entry_ids = array_unique( array_merge( $entry_ids, $where_index['it.id'] ) );
					} else {
						$entry_ids = array_intersect( $entry_ids, $where_index['it.id'] );
					}
				}
			} else {
				$or = ! empty( $this->view->frm_where_group_or[ $key ] );
				if ( $or ) {
					if ( isset( $where_index['it.is_draft '] ) ) {
						$is_draft = $where_index['it.is_draft '];
						unset( $where_index['it.is_draft '] );

						if ( isset( $where['it.is_draft '] ) ) {
							if ( is_array( $where['it.is_draft '] ) ) {
								$where['it.is_draft '][] = $is_draft;
							} else {
								$where['it.is_draft '] = array( $where['it.is_draft '], $is_draft );
							}
						} else {
							$where['it.is_draft'] = $is_draft;
						}

						if ( ! $where_index ) {
							// if the only condition in group was is_draft, avoid adding empty set to where.
							continue;
						}
					}

					// if it's "group or", we need every group's conditions separated within an "and" condition that is also checking things like form id, draft.
					$where[]     = $where_index;
					$where['or'] = 1;
				} else {
					foreach ( $where_index as $where_index_key => $where_index_value ) {
						while ( isset( $where[ $where_index_key ] ) ) {
							$where_index_key .= ' ';
						}
						$where[ $where_index_key ] = $where_index_value;
					}
				}
			}
		}

		if ( false !== $entry_ids ) {
			$where['it.id'] = $entry_ids;
		}
	}

	private function get_all_entry_ids_for_view() {
		if ( ! isset( $this->all_entry_ids_for_view ) ) {
			$this->all_entry_ids_for_view = FrmViewsDisplaysController::get_all_entry_ids_for_view( $this->view );
		}
		return $this->all_entry_ids_for_view;
	}

	/**
	 * @param array $where
	 * @param array $where_for_group
	 * @return array
	 */
	private function get_where_for_filter_group( $where, $where_for_group ) {
		$view = $this->view;
		$atts = $this->atts;

		foreach ( $where_for_group as $i => $where_field ) {
			// If no value is saved for where field or current filter is a unique filter, move on
			if ( '' === $where_field || 0 === strpos( $view->frm_where_is[ $i ], 'group_by' ) ) {
				continue;
			}

			if ( self::prepare_where_val( $i, $view ) === false ) {
				if ( ! FrmViewsDisplaysController::entries_are_possible( $view ) ) {
					break;
				}
				continue;
			}

			self::prepare_where_is( $i, $view );

			if ( ! is_numeric( $where_field ) ) {
				// Filter by a standard frm_items database column
				self::add_to_frm_items_query( $view, $i, $where );
				continue;
			}

			// Filter by a field value

			if ( isset( $where['it.id'] ) ) {
				$previous_item_ids = $where['it.id'];
			} else {
				$previous_item_ids = array();
				$where['it.id']    = $this->get_all_entry_ids_for_view();
			}

			$or = isset( $view->frm_where_or ) && ! empty( $view->frm_where_or[ $i ] );

			if ( $or ) {
				$where['it.id'] = $this->get_all_entry_ids_for_view();
				self::update_entry_ids_with_field_filter( $view, $i, $atts, $where );
				$where['it.id'] = array_merge( $where['it.id'], $previous_item_ids );
			} else {
				self::update_entry_ids_with_field_filter( $view, $i, $atts, $where );
			}

			if ( empty( $where['it.id'] ) && ! $or ) {
				// if any condition within a group is empty, the "and" condition can quit early.
				break;
			}
		}

		return $where;
	}

	/**
	 * @return array
	 */
	private function group_where() {
		$where_by_group = array();
		foreach ( $this->view->frm_where as $i => $where_field ) {
			$group_id = isset( $this->view->frm_where_group[ $i ] ) ? $this->view->frm_where_group[ $i ] : 0;
			if ( ! isset( $where_by_group[ $group_id ] ) ) {
				$where_by_group[ $group_id ] = array();
			}
			$where_by_group[ $group_id ][ $i ] = $where_field;
		}
		return $where_by_group;
	}

	/**
	 * Filter down entry IDs with a View field filter
	 *
	 * @param object $view
	 * @param int    $i
	 * @param array  $atts
	 * @param array  $where
	 */
	private static function update_entry_ids_with_field_filter( $view, $i, $atts, &$where ) {
		$args = array(
			'where_opt'   => $view->frm_where[ $i ],
			'where_is'    => $view->frm_where_is[ $i ],
			'where_val'   => $view->frm_where_val[ $i ],
			'form_id'     => $view->frm_form_id,
			'form_posts'  => $atts['form_posts'],
			'after_where' => true,
			'display'     => $view,
			'drafts'      => 'both',
			'use_ids'     => false,
		);

		if ( count( $where['it.id'] ) < 100 ) {
			// Only use the entry IDs in DB calls if it won't make the query too long
			$args['use_ids'] = true;
		}

		$filter_opts = apply_filters( 'frm_display_filter_opt', $args );

		$where['it.id'] = FrmProAppHelper::filter_where( $where['it.id'], $filter_opts );
	}

	/**
	 * Prepare the where_is value for a View filter
	 *
	 * @param int    $i
	 * @param object $view
	 */
	private static function prepare_where_is( $i, &$view ) {
		$where_is = $view->frm_where_is[ $i ];

		if ( is_array( $view->frm_where_val[ $i ] ) && ! empty( $view->frm_where_val[ $i ] ) ) {
			if ( strpos( $where_is, '!' ) === false && strpos( $where_is, 'not' ) === false ) {
				$where_is = 'in';
			} else {
				$where_is = 'not in';
			}
		}

		$view->frm_where_is[ $i ] = $where_is;
	}

	/**
	 * Prepare the where value in a View filter
	 *
	 * @param int    $i
	 * @param object $view
	 * @return bool true if there is a value to filter
	 */
	private static function prepare_where_val( $i, &$view ) {
		if ( ! isset( $view->frm_where_val[ $i ] ) ) {
			$view->frm_where_val[ $i ] = '';
		}

		$orig_where_val            = $view->frm_where_val[ $i ];
		$view->frm_where_val[ $i ] = FrmProFieldsHelper::get_default_value( $orig_where_val, false, true, true );

		if ( preg_match( "/\[(get|get-(.?))\b(.*?)(?:(\/))?\]/s", $orig_where_val ) && '' == $view->frm_where_val[ $i ] ) {
			// If where_val contains [get] or [get-param] shortcode and the param isn't set, ignore this filter
			return false;
		}

		if ( self::ignore_entry_id_filter( $orig_where_val, $i, $view ) ) {
			return false;
		}

		self::convert_current_user_val_to_current_user_id( $view->frm_where_val[ $i ] );

		if ( 'current_user' === $view->frm_where_val[ $i ] ) {
			self::set_entries_as_impossible( $view );
			return false;
		}

		self::do_shortcode_in_where_val( $view->frm_where_val[ $i ] );
		self::prepare_where_val_for_date_columns( $view, $i, $view->frm_where_val[ $i ] );
		self::prepare_where_val_for_id_and_key_columns( $view, $i, $view->frm_where_val[ $i ] );

		return true;
	}

	/**
	 * Adds indication in View object that no entries will be found
	 *
	 * @param object $view
	 */
	private static function set_entries_as_impossible( &$view ) {
		$view->frm_limit = 0;
	}

	/**
	 * Convert current_user to the current user's ID
	 *
	 * @param string|array $where_val
	 */
	private static function convert_current_user_val_to_current_user_id( &$where_val ) {
		if ( 'current_user' === $where_val && is_user_logged_in() ) {
			$where_val = get_current_user_id();
		}
	}

	/**
	 * For stinking reverse compatibility
	 * Ignore an "Entry ID is equal to [get param=entry old_filter=1]" filter in a single entry View
	 * if the retrieved entry doesn't exist in the current form
	 *
	 * @param string $orig_where_val
	 * @param int    $i
	 * @param object $view
	 * @return bool
	 */
	private static function ignore_entry_id_filter( $orig_where_val, $i, &$view ) {
		$ignore = false;

		if ( 'one' === $view->frm_show_count && 'id' === $view->frm_where[ $i ] && '[get param=entry old_filter=1]' === $orig_where_val ) {
			$where = array( 'form_id' => $view->frm_form_id );
			if ( ! is_numeric( $view->frm_where_val[ $i ] ) ) {
				$where['item_key'] = $view->frm_where_val[ $i ];
				$entry_id_in_form  = FrmDb::get_var( 'frm_items', $where );
				if ( $entry_id_in_form ) {
					$view->frm_where_val[ $i ] = $entry_id_in_form;
				}
			} else {
				$where['id']      = $view->frm_where_val[ $i ];
				$entry_id_in_form = FrmDb::get_var( 'frm_items', $where );
				if ( ! $entry_id_in_form ) {
					$ignore = true;
				}
			}
		}

		return $ignore;
	}

	/**
	 * Do shortcodes in the where value
	 *
	 * @param string|array $where_val
	 */
	private static function do_shortcode_in_where_val( &$where_val ) {
		if ( ! is_array( $where_val ) ) {
			$where_val = do_shortcode( $where_val );
		}
	}

	/**
	 * Prepare the where value for date columns
	 *
	 * @param object $view
	 * @param int    $i
	 * @param string $where_val
	 */
	private static function prepare_where_val_for_date_columns( $view, $i, &$where_val ) {
		if ( ! in_array( $view->frm_where[ $i ], array( 'created_at', 'updated_at' ), true ) ) {
			return;
		}

		FrmProContent::get_gmt_for_filter( $view->frm_where_is[ $i ], $where_val );
	}

	/**
	 * Prepare the where value for id, item_key, and post_id columns
	 *
	 * @param object       $view
	 * @param int          $i
	 * @param string|array $where_val
	 */
	private static function prepare_where_val_for_id_and_key_columns( $view, $i, &$where_val ) {
		if ( in_array( $view->frm_where[ $i ], array( 'id', 'item_key', 'post_id' ), true ) && ! is_array( $where_val ) ) {
			if ( strpos( $where_val, ',' ) ) {
				$where_val = explode( ',', $where_val );
				$where_val = array_filter( $where_val );
			} elseif ( in_array( $view->frm_where_is[ $i ], array( '=', 'LIKE' ), true ) ) {
				$where_val = (array) $where_val;
			}
		}
	}

	/**
	 * Add a standard frm_items column filter to the where array
	 *
	 * @param object $view
	 * @param int    $i
	 * @param array  $where
	 */
	private static function add_to_frm_items_query( $view, $i, &$where ) {
		$array_key = 'it.' . sanitize_title( $view->frm_where[ $i ] ) . FrmDb::append_where_is( $view->frm_where_is[ $i ] );

		if ( 'it.id ' === $array_key ) {
			$array_key = rtrim( $array_key );
		}

		$or = ! empty( $view->frm_where_or[ $i ] );

		if ( isset( $where[ $array_key ] ) ) {
			if ( in_array( $array_key, array( 'it.id', 'it.item_key ' ), true ) ) {
				if ( $or ) {
					$view->frm_where_val[ $i ] = array_unique( array_merge( $where[ $array_key ], $view->frm_where_val[ $i ] ) );
				} else {
					$view->frm_where_val[ $i ] = array_intersect( $where[ $array_key ], $view->frm_where_val[ $i ] );
				}
			} elseif ( $or ) {
				$group_already_exists = ! empty( $where[ $array_key ]['or'] );

				if ( $group_already_exists ) {
					$group                              = $where[ $array_key ];
					$next_available_array_key           = self::next_available_array_key( $array_key, $group );
					$group[ $next_available_array_key ] = $view->frm_where_val[ $i ];
					$where[ $array_key ]                = $group;
				} elseif ( 'it.is_draft ' === $array_key ) {
					if ( is_array( $where[ $array_key ] ) ) {
						$group   = $where[ $array_key ];
						$group[] = $view->frm_where_val[ $i ];
					} else {
						$group = array( $where[ $array_key ], $view->frm_where_val[ $i ] );
					}
					$group = array_unique( $group );
				} else {
					$group = array(
						'or'             => 1,
						$array_key       => $where[ $array_key ],
						$array_key . ' ' => $view->frm_where_val[ $i ],
					);
				}

				$where[ $array_key ] = $group;
				return;
			} else {
				$array_key = self::next_available_array_key( $array_key, $where );
			}
		}

		$where[ $array_key ] = $view->frm_where_val[ $i ];

		if ( $or && $i > 0 && count( $where ) > 1 ) {
			$where['or'] = 1;
		}
	}

	/**
	 * Avoid overwriting a previous filter value by adding a space after its key.
	 * This will continue to add a space until the key is not yet set in the array.
	 *
	 * @param string $key
	 * @param array  $array
	 * @return string
	 */
	private static function next_available_array_key( $key, $array ) {
		while ( isset( $array[ $key ] ) ) {
			$key .= ' ';
		}
		return $key;
	}
}
