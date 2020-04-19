<?php

class sb_et_cpt_li_loop_archive extends ET_Builder_Module {

	public $vb_support = 'partial';

	protected $module_credits = array(
		'module_uri' => SB_ET_CPT_LI_STORE_URL,
		'author'     => SB_ET_CPT_LI_AUTHOR_NAME,
		'author_uri' => SB_ET_CPT_LI_AUTHOR_URL,
	);

	function init() {
		$this->name = esc_html__( 'CPT Loop Archive', 'et_builder' );
		$this->slug = 'et_pb_cpt_loop_archive';

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_settings' => esc_html__( 'Main Settings', 'et_builder' ),
				),
			),
		);

		$this->fields_defaults = array(
			'loop_layout'     => array( 'on' ),
			'fullwidth'       => array( 'on' ),
			'columns'         => array( '3' ),
			'posts_number'    => array( 10, 'add_default_setting' ),
			'show_pagination' => array( 'on' ),
			'offset_number'   => array( 0, 'only_default_setting' ),
		);

		$this->main_css_element = '%%order_class%% .et_pb_post .et_pb_post_type';

		$this->advanced_fields = array(
			'fonts'                 => array(
				'text'     => array(
					'label'       => esc_html__( 'Text', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} p",
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'headings' => array(
					'label'       => esc_html__( 'Headings', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h3, {$this->main_css_element} h4",
					),
					'font_size'   => array( 'default' => '30px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
			),
			'background'            => array(
				'settings' => array(
					'color' => 'alpha',
				),
			),
			'border'                => array(),
			'custom_margin_padding' => array(
				'css' => array(
					'important' => 'all',
				),
			),
		);
	}

	function get_fields() {
		$options = $orderby = $order = array();

		$orderby = array(
			'date'          => 'Order by date'
		,
			'ID'            => 'Order by post id'
		,
			'author'        => 'Order by author'
		,
			'title'         => 'Order by title'
		,
			'name'          => 'Order by post name (post slug)'
		,
			'modified'      => 'Order by last modified date'
		,
			'rand'          => 'Random order'
		,
			'comment_count' => 'Order by number of comments'
		,
			'menu_order'    => 'Menu Order (Custom Order)'
		);
		$order   = array(
			'desc' => 'Descending'
		,
			'asc'  => 'Ascending'
		);

		$layout_query = array(
			'post_type'      => 'et_pb_layout'
		,
			'posts_per_page' => - 1
		,
			'meta_query'     => array(
				array(
					'key'     => '_et_pb_predefined_layout',
					'compare' => 'NOT EXISTS',
				),
			)
		);

		$layouts = get_posts( $layout_query );

		foreach ( $layouts as $layout ) {
			$options[ $layout->ID ] = $layout->post_title;
		}

		$args       = array(
			'public' => true
		);
		$output     = 'objects'; // names or objects
		$pt_options = array();

		$post_types = get_post_types( $args, $output );

		foreach ( $post_types as $post_type => $post_type_obj ) {
			$pt_options[ $post_type ] = $post_type_obj->labels->name;
		}

		$fields = array(
			'title'                 => array(
				'label'       => esc_html__( 'Title', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'Optionally add a title above the posts', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'loop_layout'           => array(
				'label'           => esc_html__( 'Loop Layout', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => $options,
				'description'     => esc_html__( 'Choose a layout to use for each post in this archive loop', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'fullwidth'             => array(
				'label'           => esc_html__( 'Layout', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => array(
					'list' => esc_html__( 'List', 'et_builder' ),
					'off'  => esc_html__( 'Grid', 'et_builder' ),
				),
				'description'     => esc_html__( 'Toggle between the various blog layout types.', 'et_builder' ),
				'affects'         => array(
					'columns'
				),
				'toggle_slug'     => 'main_settings',
			),
			'columns'               => array(
				'label'           => esc_html__( 'Grid Columns', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'depends_show_if' => 'off',
				'options'         => array(
					2 => esc_html__( 'Two', 'et_builder' ),
					3 => esc_html__( 'Three', 'et_builder' ),
					4 => esc_html__( 'Four', 'et_builder' ),
				),
				'description'     => esc_html__( 'When in grid mode please select the number of columns you\'d like to see.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_pagination'       => array(
				'label'           => esc_html__( 'Show Pagination', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'description'     => esc_html__( 'Turn pagination on and off. Using the free plugin WP Page Navi from the WP.org directory will make this show page numbers rather than the default older and newer posts.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'new_query'             => array(
				'label'       => esc_html__( 'Custom Query', 'et_builder' ),
				'type'        => 'yes_no_button',
				'options'     => array(
					'off' => esc_html__( 'No', 'et_builder' ),
					'on'  => esc_html__( 'Yes', 'et_builder' ),
				),
				'affects'     => array(
					'post_type'
				,
					'posts_number'
				,
					'offset_number'
				,
					'include_tax'
				,
					'include_tax_terms'
				,
					'include_meta'
				,
					'include_meta_terms'
				,
					'include_meta_operator'
				,
					'order_by'
				,
					'order'
				),
				'description' => esc_html__( 'When used on an archive page turn this off. If you want to use on a normal WP page then select "ON" here and complete the settings below.', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'post_type'             => array(
				'label'           => esc_html__( 'Post Type', 'et_builder' ),
				'type'            => 'select',
				'options'         => $pt_options,
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Choose a post type to show', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'posts_number'          => array(
				'label'           => esc_html__( 'Posts Number', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Choose how many posts you would like to display per page.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'offset_number'         => array(
				'label'           => esc_html__( 'Offset Number', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Choose how many posts you would like to offset by', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'include_tax'           => array(
				'label'           => esc_html__( 'Include Taxonomy Only', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'This will filter the query by this taxonomy slug (advanced users only).', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'include_tax_terms'     => array(
				'label'           => esc_html__( 'Include Taxonomy Terms', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'This will filter the query by the above taxonomy and these comma separated term slugs (advanced users only).', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'include_meta'          => array(
				'label'           => esc_html__( 'Filter on Meta Key', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'This will filter the query by this meta key (advanced users only).', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'include_meta_terms'    => array(
				'label'           => esc_html__( 'Meta Key Terms', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'This will filter the query by the above meta key and this value (advanced users only).', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'include_meta_operator' => array(
				'label'           => esc_html__( 'Meta Key Operator', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'If filtering the meta query (above two fields) then you can use this field to set the operator (empty field means = which is default. advanced users only)', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'hide_if_no_data'       => array(
				'label'       => esc_html__( 'Hide if no Results', 'et_builder' ),
				'type'        => 'yes_no_button',
				'options'     => array(
					'off' => esc_html__( 'No', 'et_builder' ),
					'on'  => esc_html__( 'Yes', 'et_builder' ),
				),
				'description' => esc_html__( 'If no results there will be a "Sorry no results". Should this be hidden?', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'order_by'              => array(
				'label'       => esc_html__( 'Results Order Field', 'et_builder' ),
				'type'        => 'select',
				'options'     => $orderby,
				'description' => esc_html__( 'Choose how you\'d like the results to be ordered.. title, date, etc...', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'order'                 => array(
				'label'       => esc_html__( 'Results Sort Order', 'et_builder' ),
				'type'        => 'select',
				'options'     => $order,
				'description' => esc_html__( 'Choose the order of the results.. Ascending or Descending.', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'admin_label'           => array(
				'label'       => esc_html__( 'Admin Label', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
			),
			'module_id'             => array(
				'label'           => esc_html__( 'CSS ID', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'module_class'          => array(
				'label'           => esc_html__( 'CSS Class', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
		);

		return $fields;
	}

	function render( $atts, $content = null, $function_name ) {
		global $sb_et_cpt_li_in_loop;

		$id           = sb_et_cpt_li_get_id();
		$is_vb        = sb_et_cpt_li_is_vb();
		$show_vb_note = false;

		$title              = $this->props['title'];
		$loop_layout        = $this->props['loop_layout'];
		$cols               = $this->props['columns'];
		$module_id          = $this->props['module_id'];
		$module_class       = $this->props['module_class'];
		$fullwidth          = $this->props['fullwidth'];
		$posts_number       = $this->props['posts_number'];
		$post_type          = $this->props['post_type'];
		$custom_query       = $this->props['new_query'];
		$show_pagination    = $this->props['show_pagination'];
		$offset_number      = $this->props['offset_number'];
		$order_by           = $this->props['order_by'];
		$order              = $this->props['order'];
		$include_meta       = $this->props['include_meta'];
		$include_meta_terms = $this->props['include_meta_terms'];

		if ( ! $include_meta_operator = trim( $this->props['include_meta_operator'] ) ) {
			$include_meta_operator = '=';
		}

		$include_tax       = $this->props['include_tax'];
		$include_tax_terms = $this->props['include_tax_terms'];
		$hide_if_no_data   = ( $this->props['hide_if_no_data'] ? $this->props['hide_if_no_data'] : 'no' );

		global $paged;

		$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );

		$container_is_closed = false;

		// remove all filters from WP audio shortcode to make sure current theme doesn't add any elements into audio module
		remove_all_filters( 'wp_audio_shortcode_library' );
		remove_all_filters( 'wp_audio_shortcode' );
		remove_all_filters( 'wp_audio_shortcode_class' );

		if ( $fullwidth == 'list' ) {
			$module_class .= ' et_pb_cpt_archive_list';
		} else {
			$module_class .= ' et_pb_cpt_archive_grid';
		}

		if ( $custom_query == 'on' ) {
			$args = array( 'posts_per_page' => (int) $posts_number );

			if ( $order_by && $order ) { //sort ordering
				$args['orderby'] = $order_by;
				$args['order']   = $order;
			}

			$et_paged = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );

			if ( is_front_page() ) {
				$paged = $et_paged;
			}

			$args['post_type'] = $post_type;

			if ( ! is_search() ) {
				$args['paged'] = $et_paged;
			}

			if ( '' !== $offset_number && ! empty( $offset_number ) ) {
				/**
				 * Offset + pagination don't play well. Manual offset calculation required
				 * @see: https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
				 */
				if ( $paged > 1 ) {
					$args['offset'] = ( ( $et_paged - 1 ) * intval( $posts_number ) ) + intval( $offset_number );
				} else {
					$args['offset'] = intval( $offset_number );
				}
			}

			if ( $include_tax && $include_tax_terms ) {
				if ( strpos( $include_tax, '|' ) !== false ) {
					$include_tax       = explode( '|', $include_tax );
					$include_tax_terms = explode( '|', $include_tax_terms );

					$args['tax_query'] = array();

					for ( $i = 0; $i < count( $include_tax ); $i ++ ) {
						$args['tax_query'][] = array(
							'taxonomy' => $include_tax[ $i ],
							'field'    => 'slug',
							'terms'    => explode( ',', $include_tax_terms[ $i ] ),
						);
					}
				} else {
					$args['tax_query'] = array(
						array(
							'taxonomy' => $include_tax,
							'field'    => 'slug',
							'terms'    => explode( ',', $include_tax_terms ),
						)
					);
				}
			}

			if ( $include_meta && $include_meta_terms ) {
				if ( strpos( $include_meta, '|' ) !== false ) {
					$include_meta       = explode( '|', $include_tax );
					$include_meta_terms = explode( '|', $include_meta_terms );

					$args['meta_query'] = array();

					for ( $i = 0; $i < count( $include_tax ); $i ++ ) {
						$args['meta_query'][] = array(
							'key'     => $include_meta[ $i ],
							'value'   => $include_meta_terms[ $i ],
							'compare' => $include_meta_operator
						);
					}
				} else {
					$args['meta_query'] = array(
						array(
							'key'     => $include_meta,
							'value'   => $include_meta_terms,
							'compare' => $include_meta_operator
						)
					);
				}
			}

			if ( is_single() && ! isset( $args['post__not_in'] ) ) {
				$args['post__not_in'] = array( $id );
			}

			$args = apply_filters( 'sb_et_divi_pt_loop_archive_module_args', $args );

			//echo '<pre>';
			//print_r($args);
			//echo '</pre>';

			if ( class_exists( 'EM_Event_Post' ) ) {
				remove_action( 'parse_query', array( 'EM_Event_Post', 'parse_query' ) );
			}

			query_posts( $args );

			if ( class_exists( 'EM_Event_Post' ) ) {
				add_action( 'parse_query', array( 'EM_Event_Post', 'parse_query' ) );
			}

		} else if ( $is_vb ) {
			$show_vb_note = true;
			query_posts( array( 'post_type' => 'post' ) ); //just so we have something to show
		}

		$hide = false;
		ob_start();

		if ( $show_vb_note ) {
			echo '<p style="font-size: 10px; color: red;">As you are using the Visual Builder for this module and are not using the "Custom Query" option, during editing only POSTS will be shown but when you save the page the module will show the correct post type and context</p>';
		}

		if ( $title ) {
			echo '<h2 class="loop-archive-title">' . $title . '</h2>';
		}

		if ( have_posts() ) {
			$sb_et_cpt_li_in_loop = true;
			$i = 0;

			if ( $fullwidth == 'off' ) { //grid
				echo '<div class="et_pb_row et_pb_row_cpt">';
			}

			while ( have_posts() ) {
				the_post();

				if ( $fullwidth == 'off' ) { //grid
					echo '<div class="et_cpt_container_column et_pb_column et_pb_column_1_' . $cols . '  et_pb_column_' . $i . '">';
				}

				echo do_action( 'sb_et_ept_li_loop_archive_start', $id );
				echo do_shortcode( '[et_pb_section global_module="' . $loop_layout . '"][/et_pb_section]' );
				echo do_action( 'sb_et_ept_li_loop_archive_end', $id );

				if ( $fullwidth == 'off' ) { //grid
					echo '</div>';
				}

				$i ++;

				if ( $i == $cols && ( $fullwidth == 'off' ) ) {
					$i = 0;

					echo '</div>';
					echo '<div class="et_pb_row et_pb_row_cpt">';
				}
			} // endwhile

			if ( $fullwidth == 'off' ) { //grid
				echo '</div>';
			}

			if ( 'on' === $show_pagination && ! is_search() ) {
				echo '</div> <!-- .et_pb_posts -->';

				$container_is_closed = true;

				if ( function_exists( 'wp_pagenavi' ) ) {
					wp_pagenavi();
				} else {
					if ( et_is_builder_plugin_active() ) {
						include( ET_BUILDER_PLUGIN_DIR . 'includes/navigation.php' );
					} else {
						get_template_part( 'includes/navigation', 'index' );
					}
				}
			}

			wp_reset_query();

			$sb_et_cpt_li_in_loop = false;
		} else {
			if ( $hide_if_no_data == 'on' ) {
				$hide = true;
			} else {
				if ( et_is_builder_plugin_active() ) {
					include( ET_BUILDER_PLUGIN_DIR . 'includes/no-results.php' );
				} else {
					get_template_part( 'includes/no-results', 'index' );
				}
			}
		}

		$posts = ob_get_contents();

		ob_end_clean();

		$class = " et_pb_module et_pb_cpt_loop_archive ";

		$output = sprintf(
			'<div%5$s class="%1$s%3$s%6$s"%7$s>
				%2$s
			%4$s',
			( $fullwidth == 'list' ? 'et_pb_posts' : 'et_pb_blog_grid clearfix' ),
			$posts,
			esc_attr( $class ),
			( ! $container_is_closed ? '</div> <!-- .et_pb_posts -->' : '' ),
			( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
			( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
			( 'on' !== $fullwidth ? ' data-columns' : '' )
		);

		if ( 'off' == $fullwidth ) {
			$output = sprintf( '<div class="et_pb_blog_grid_wrapper">%1$s</div>', $output );
		} else if ( 'list' == $fullwidth ) {
			$output = sprintf( '<div class="et_pb_cpt_list_wrapper">%1$s</div>', $output );
		}

		if ( $hide ) { //hide as no results
			$output = false;
		}

		return $output;
	}
}

new sb_et_cpt_li_loop_archive;

?>