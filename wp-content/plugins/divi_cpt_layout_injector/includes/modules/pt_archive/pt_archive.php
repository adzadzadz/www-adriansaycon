<?php

class sb_et_cpt_li_pt_archive extends ET_Builder_Module {

	public $vb_support = 'partial';

	protected $module_credits = array(
		'module_uri' => SB_ET_CPT_LI_STORE_URL,
		'author'     => SB_ET_CPT_LI_AUTHOR_NAME,
		'author_uri' => SB_ET_CPT_LI_AUTHOR_URL,
	);

	function init() {
		$this->name = esc_html__( 'CPT Archive', 'et_builder' );
		$this->slug = 'et_pb_cpt_archive';

		$this->fields_defaults = array(
			'fullwidth'         => array( 'off' ),
			'posts_number'      => array( 10, 'add_default_setting' ),
			'meta_date'         => array( 'M j, Y', 'add_default_setting' ),
			'columns'    => array( 2 ),
			'show_thumbnail'    => array( 'on' ),
			'show_content'      => array( 'off' ),
			'image_size'        => array( 'medium' ),
			'image_target'      => array( 'same' ),
			'image_url'         => array( 'post' ),
			'show_more'         => array( 'off' ),
			'show_author'       => array( 'on' ),
			'show_date'         => array( 'on' ),
			'show_comments'     => array( 'off' ),
			'show_pagination'   => array( 'on' ),
			'offset_number'     => array( 0, 'only_default_setting' ),
			'background_layout' => array( 'light' ),
			'use_dropshadow'    => array( 'off' ),
			'use_overlay'       => array( 'off' ),
		);

		$this->main_css_element = '%%order_class%%';

		$this->advanced_fields = array(
			'fonts'                 => array(
				'text'      => array(
					'label'       => esc_html__( 'Text', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} p",
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'headings'  => array(
					'label'       => esc_html__( 'Headings', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
					),
					'font_size'   => array( 'default' => '30px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'meta'      => array(
					'label'       => esc_html__( 'Meta', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .post-meta, {$this->main_css_element} .post-meta a",
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'read_more' => array(
					'label'       => esc_html__( 'Read More Button', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .et_pb_button.more-link",
					),
					'font_size'   => array( 'default' => '14px' ),
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

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_settings' => esc_html__( 'Main Settings', 'et_builder' ),
				),
			),
		);

		$this->custom_css_fields = array(
			'title'          => array(
				'label'    => esc_html__( 'Title', 'et_builder' ),
				'selector' => '.et_pb_post h2',
			),
			'post_meta'      => array(
				'label'    => esc_html__( 'Post Meta', 'et_builder' ),
				'selector' => '.et_pb_post .post-meta',
			),
			'pagenavi'       => array(
				'label'    => esc_html__( 'Pagenavi', 'et_builder' ),
				'selector' => '.wp_pagenavi',
			),
			'featured_image' => array(
				'label'    => esc_html__( 'Featured Image', 'et_builder' ),
				'selector' => '.et_pb_image_container',
			),
			'read_more'      => array(
				'label'    => esc_html__( 'Read More Button', 'et_builder' ),
				'selector' => '.et_pb_post .more-link',
			),
		);
	}

	function get_fields() {
		$args    = array(
			'public' => true
		);
		$output  = 'objects'; // names or objects
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

		$post_types = get_post_types( $args, $output );

		foreach ( $post_types as $post_type => $post_type_obj ) {
			$options[ $post_type ] = $post_type_obj->labels->name;
		}

		$image_options = array();
		$sizes         = get_intermediate_image_sizes();

		foreach ( $sizes as $size ) {
			$image_options[ $size ] = $size;
		}

		$fields = array(
			'post_type'      => array(
				'label'           => esc_html__( 'Post Type', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => $options,
				'description'     => esc_html__( 'Choose a post type to show', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'fullwidth'      => array(
				'label'           => esc_html__( 'Layout', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => array(
					'off'  => esc_html__( 'Grid', 'et_builder' ),
					'list' => esc_html__( 'List', 'et_builder' ),
					'on'   => esc_html__( 'Fullwidth', 'et_builder' ),
				),
				'description'     => esc_html__( 'Toggle between the various blog layout types.', 'et_builder' ),
				'affects'         => array(
					'columns'
				),
				'toggle_slug'     => 'main_settings',
			),
			'columns'        => array(
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
			'posts_number'   => array(
				'label'           => esc_html__( 'Posts Number', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'description'     => esc_html__( 'Choose how much posts you would like to display per page.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'meta_date'      => array(
				'label'           => esc_html__( 'Meta Date Format', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'description'     => esc_html__( 'If you would like to adjust the date format, input the appropriate PHP date format here.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_thumbnail' => array(
				'label'           => esc_html__( 'Show Featured Image', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'affects'         => array(
					'image_size',
					'image_url',
					'image_target',
				),
				'description'     => esc_html__( 'This will turn thumbnails on and off.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'image_size'     => array(
				'label'           => __( 'Featured Image Size', 'et_builder' ),
				'type'            => 'select',
				'options'         => $image_options,
				'depends_show_if' => 'on',
				'description'     => __( 'Pick a size for the featured image from the list. If there is no size you like in the list consider using the free <a href="https://wordpress.org/plugins/simple-image-sizes/" target="_blank">Simple Image Sizes</a> where you can define your own.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'image_url'      => array(
				'label'           => __( 'Image Link URL', 'et_builder' ),
				'type'            => 'select',
				'options'         => array(
					'post'         => 'Content Page'
				,
					'image'        => 'Larger Image'
				,
					'custom_field' => 'Custom Field'
				,
					'none'         => 'No link'
				),
				'affects'         => array(
					'image_custom_field',
				),
				'depends_show_if' => 'on',
				'description'     => __( 'What should the image link to.. nothing, the post itself, the full sized image or a custom field', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),

			'image_target'                  => array(
				'label'           => esc_html__( 'Image Link Target', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => array(
					'same' => esc_html__( 'Same Window/Tab', 'et_builder' ),
					'new'  => esc_html__( 'New Window/Tab', 'et_builder' ),
				),
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Should the link open in the same or new window/tab', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'image_custom_field'            => array(
				'label'           => esc_html__( 'Image URL - Custom Field Name', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'depends_show_if' => 'custom_field',
				'description'     => esc_html__( 'Enter the database name of the custom field', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_content'                  => array(
				'label'           => esc_html__( 'Content', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => array(
					'off'  => esc_html__( 'Show Excerpt', 'et_builder' ),
					'on'   => esc_html__( 'Show Content', 'et_builder' ),
					'none' => esc_html__( 'Show None', 'et_builder' ),
				),
				'affects'         => array(
					'show_more',
				),
				'description'     => esc_html__( 'Showing the full content will not truncate your posts on the index page. Showing the excerpt will only display your excerpt text.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_more'                     => array(
				'label'           => esc_html__( 'Read More Button', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'off' => esc_html__( 'Off', 'et_builder' ),
					'on'  => esc_html__( 'On', 'et_builder' ),
				),
				'depends_show_if' => 'off',
				'description'     => esc_html__( 'Here you can define whether to show "read more" link after the excerpts or not.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_author'                   => array(
				'label'           => esc_html__( 'Show Author', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'description'     => esc_html__( 'Turn on or off the author link.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_date'                     => array(
				'label'           => esc_html__( 'Show Date', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'description'     => esc_html__( 'Turn the date on or off.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_comments'                 => array(
				'label'           => esc_html__( 'Show Comment Count', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'description'     => esc_html__( 'Turn comment count on and off.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_pagination'               => array(
				'label'           => esc_html__( 'Show Pagination', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'description'     => esc_html__( 'Turn pagination on and off.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'offset_number'                 => array(
				'label'           => esc_html__( 'Offset Number', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'description'     => esc_html__( 'Choose how many posts you would like to offset by', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'include_tax'                   => array(
				'label'           => esc_html__( 'Include Taxonomy Only', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'description'     => esc_html__( 'This will filter the query by this taxonomy slug (advanced users only).', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'include_tax_terms'             => array(
				'label'           => esc_html__( 'Include Taxonomy Terms', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'description'     => esc_html__( 'This will filter the query by the above taxonomy and these comma separated term slugs (advanced users only).', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'include_meta'                  => array(
				'label'       => esc_html__( 'Filter on Meta Key', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'This will filter the query by this meta key (advanced users only).', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'include_meta_terms'            => array(
				'label'       => esc_html__( 'Meta Key Terms', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'This will filter the query by the above meta key and this value (advanced users only).', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'include_meta_operator'         => array(
				'label'       => esc_html__( 'Meta Key Operator', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'If filtering the meta query (above two fields) then you can use this field to set the operator (empty field means = which is default. advanced users only)', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'order_by'                      => array(
				'label'       => esc_html__( 'Results Order Field', 'et_builder' ),
				'type'        => 'select',
				'options'     => $orderby,
				'description' => esc_html__( 'Choose how you\'d like the results to be ordered.. title, date, etc...', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'order'                         => array(
				'label'       => esc_html__( 'Results Sort Order', 'et_builder' ),
				'type'        => 'select',
				'options'     => $order,
				'description' => esc_html__( 'Choose the order of the results.. Ascending or Descending.', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'use_overlay'                   => array(
				'label'           => esc_html__( 'Featured Image Overlay', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'layout',
				'options'         => array(
					'off' => esc_html__( 'Off', 'et_builder' ),
					'on'  => esc_html__( 'On', 'et_builder' ),
				),
				'affects'         => array(
					'overlay_icon_color',
					'hover_overlay_color',
					'hover_icon',
				),
				'description'     => esc_html__( 'If enabled, an overlay color and icon will be displayed when a visitors hovers over the featured image of a post.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'overlay_icon_color'            => array(
				'label'           => esc_html__( 'Overlay Icon Color', 'et_builder' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Here you can define a custom color for the overlay icon', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'hover_overlay_color'           => array(
				'label'           => esc_html__( 'Hover Overlay Color', 'et_builder' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Here you can define a custom color for the overlay', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'hover_icon'                    => array(
				'label'               => esc_html__( 'Hover Icon Picker', 'et_builder' ),
				'type'                => 'text',
				'option_category'     => 'configuration',
				'class'               => array( 'et-pb-font-icon' ),
				'renderer'            => 'select_icon',
				'renderer_with_field' => true,
				'depends_show_if'     => 'on',
				'description'         => esc_html__( 'Here you can define a custom icon for the overlay', 'et_builder' ),
				'toggle_slug'         => 'main_settings',
			),
			'background_layout'             => array(
				'label'           => esc_html__( 'Text Color', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'color_option',
				'options'         => array(
					'light' => esc_html__( 'Dark', 'et_builder' ),
					'dark'  => esc_html__( 'Light', 'et_builder' ),
				),
				'show_if'         => true,
				'description'     => esc_html__( 'Here you can choose whether your text should be light or dark. If you are working with a dark background, then your text should be light. If your background is light, then your text should be set to dark.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'masonry_tile_background_color' => array(
				'label'           => esc_html__( 'Grid Tile Background Color', 'et_builder' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'tab_slug'        => 'advanced',
				'depends_show_if' => 'off',
			),
			'use_dropshadow'                => array(
				'label'           => esc_html__( 'Use Dropshadow', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'layout',
				'options'         => array(
					'off' => esc_html__( 'Off', 'et_builder' ),
					'on'  => esc_html__( 'On', 'et_builder' ),
				),
				'tab_slug'        => 'advanced',
				'depends_show_if' => 'off',
			),
			'disabled_on'                   => array(
				'label'           => esc_html__( 'Disable on', 'et_builder' ),
				'type'            => 'multiple_checkboxes',
				'options'         => array(
					'phone'   => esc_html__( 'Phone', 'et_builder' ),
					'tablet'  => esc_html__( 'Tablet', 'et_builder' ),
					'desktop' => esc_html__( 'Desktop', 'et_builder' ),
				),
				'additional_att'  => 'disable_on',
				'option_category' => 'configuration',
				'description'     => esc_html__( 'This will disable the module on selected devices', 'et_builder' ),
			),
			'admin_label'                   => array(
				'label'       => esc_html__( 'Admin Label', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
			),
			'module_id'                     => array(
				'label'           => esc_html__( 'CSS ID', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'module_class'                  => array(
				'label'           => esc_html__( 'CSS Class', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
		);

		$fields = apply_filters( 'sb_et_divi_pt_archive_module_fields', $fields );

		return $fields;
	}

	function render( $atts, $content = null, $function_name ) {

		$this->props = apply_filters( 'sb_et_divi_pt_archive_module_props', $this->props );

		$post_type                     = $this->props['post_type'];
		$image_size                    = $this->props['image_size'];
		$image_target                  = $this->props['image_target'];
		$image_url                     = $this->props['image_url'];
		$image_custom_field            = $this->props['image_custom_field'];
		$module_id                     = $this->props['module_id'];
		$module_class                  = $this->props['module_class'];
		$fullwidth                     = $this->props['fullwidth'];
		$posts_number                  = $this->props['posts_number'];
		$meta_date                     = $this->props['meta_date'];
		$show_thumbnail                = $this->props['show_thumbnail'];
		$show_content                  = $this->props['show_content'];
		$show_author                   = $this->props['show_author'];
		$show_date                     = $this->props['show_date'];
		$show_categories               = @$this->props['show_categories'];
		$show_comments                 = $this->props['show_comments'];
		$show_pagination               = $this->props['show_pagination'];
		$background_layout             = $this->props['background_layout'];
		$show_more                     = $this->props['show_more'];
		$offset_number                 = $this->props['offset_number'];
		$masonry_tile_background_color = $this->props['masonry_tile_background_color'];
		$use_dropshadow                = $this->props['use_dropshadow'];
		$overlay_icon_color            = $this->props['overlay_icon_color'];
		$hover_overlay_color           = $this->props['hover_overlay_color'];
		$hover_icon                    = $this->props['hover_icon'];
		$use_overlay                   = $this->props['use_overlay'];
		$include_tax                   = $this->props['include_tax'];
		$include_tax_terms             = $this->props['include_tax_terms'];

		$include_meta       = $this->props['include_meta'];
		$include_meta_terms = $this->props['include_meta_terms'];

		if ( ! $include_meta_operator = trim( $this->props['include_meta_operator'] ) ) {
			$include_meta_operator = '=';
		}

		$order_by = $this->props['order_by'];
		$order    = $this->props['order'];

		if ( ! $cols = @$this->props['columns'] ) {
			$cols = 4;
		}

		global $paged;

		$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );

		$container_is_closed = false;

		// remove all filters from WP audio shortcode to make sure current theme doesn't add any elements into audio module
		remove_all_filters( 'wp_audio_shortcode_library' );
		remove_all_filters( 'wp_audio_shortcode' );
		remove_all_filters( 'wp_audio_shortcode_class' );

		if ( '' !== $masonry_tile_background_color ) {
			ET_Builder_Element::set_style( $function_name, array(
				'selector'    => '%%order_class%%.et_pb_blog_grid .et_pb_post',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $masonry_tile_background_color )
				),
			) );
		}

		if ( '' !== $overlay_icon_color ) {
			ET_Builder_Element::set_style( $function_name, array(
				'selector'    => '%%order_class%% .et_overlay:before',
				'declaration' => sprintf(
					'color: %1$s !important;',
					esc_html( $overlay_icon_color )
				),
			) );
		}

		if ( '' !== $hover_overlay_color ) {
			ET_Builder_Element::set_style( $function_name, array(
				'selector'    => '%%order_class%% .et_overlay',
				'declaration' => sprintf(
					'background-color: %1$s;',
					esc_html( $hover_overlay_color )
				),
			) );
		}

		if ( 'on' === $use_overlay ) {
			$data_icon = '' !== $hover_icon
				? sprintf(
					' data-icon="%1$s"',
					esc_attr( et_pb_process_font_icon( $hover_icon ) )
				)
				: '';

			$overlay_output = sprintf(
				'<span class="et_overlay%1$s"%2$s></span>',
				( '' !== $hover_icon ? ' et_pb_inline_icon' : '' ),
				$data_icon
			);
		}

		$overlay_class = 'on' === $use_overlay ? ' et_pb_has_overlay' : '';

		if ( 'on' !== $fullwidth ) {
			if ( 'on' === $use_dropshadow ) {
				$module_class .= ' et_pb_blog_grid_dropshadow';
			}

			wp_enqueue_script( 'salvattore' );

			$background_layout = 'light';
		} else if ( $fullwidth == 'list' ) {
			$module_class .= ' et_pb_cpt_archive_list';
		}

		$args = array( 'posts_per_page' => (int) $posts_number );

		$et_paged = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );

		if ( is_front_page() ) {
			$paged = $et_paged;
		}

		$args['post_type'] = $post_type;

		if ( $order_by && $order ) { //sort ordering
			$args['orderby'] = $order_by;
			$args['order']   = $order;
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'desc';
		}

		if ( ! is_search() ) {
			$args['paged'] = $et_paged;
		}

		if ( '' !== $offset_number && ! empty( $offset_number ) ) {
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
			$args['post__not_in'] = array( get_the_ID() );
		}

		ob_start();

		$args = apply_filters( 'sb_et_divi_pt_archive_module_args', $args );

		query_posts( $args );

		if ( have_posts() ) {

			$i = 0;

			if ( $fullwidth == 'off' ) { //grid
				echo '<div class="et_pb_row_cpt">';
			}

			while ( have_posts() ) {
				the_post();

				if ( $fullwidth == 'off' ) { //grid
					echo '<div class="et_cpt_container_column et_pb_column et_pb_column_1_' . $cols . '  et_pb_column_' . $i . ' ' . implode( ' ', get_post_class( 'et_pb_post_type et_pb_post_type_' . $post_type . ' et_pb_post' . $overlay_class ) ) . '">';
				} else {
					echo '<div class="et_pb_row ' . implode( ' ', get_post_class( 'et_pb_post_type et_pb_post_type_' . $post_type . ' et_pb_post' . $overlay_class ) ) . '">';
				}

				echo '<div class="et_pb_column">';

				$post_format = et_pb_post_format();
				$thumb       = '';
				$classtext   = 'on' === $fullwidth ? 'et_pb_post_main_image' : '';
				$titletext   = strip_tags( get_the_title() );

				if ( has_post_thumbnail( get_the_ID() ) ) {
					$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), $image_size );
					$src   = $image[0];
					$thumb = '<img src="' . $src . '" class="' . $classtext . '" alt="' . $titletext . '" title="' . $titletext . '" />';
				}

				?>

				<?php

				echo do_action( 'sb_et_ept_li_pt_archive_start', get_the_ID() );

				et_divi_post_format_content();

				if ( ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) {
					if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) :
						printf(
							'<div class="et_main_video_container">
								%1$s
							</div>',
							$first_video
						);
                    elseif ( 'gallery' === $post_format ) :
						et_pb_gallery_images( 'slider' );
                    elseif ( '' !== $thumb && 'on' === $show_thumbnail ) :
						if ( 'off' == $fullwidth ) {
							echo '<div class="et_pb_image_container">';
						}
						if ( 'list' == $fullwidth ) {
							echo '<div class="et_pb_cpt_list_image_container">';
						}

						if ( ! $image_url ) {
							$image_url = 'post';
						}

						$pre_image = $post_image = '';

						if ( $image_url != 'none' ) {
							$url = '';

							if ( $image_url == 'image' ) {
								$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'large' );
								$url   = $image[0];
							} else if ( $image_url == 'post' ) {
								$url = get_permalink( get_the_ID() );
							} else if ( $image_url == 'custom_field' && $image_custom_field ) {
								$url = get_post_meta( get_the_ID(), $image_custom_field, true );
							}

							if ( $url ) {
								$pre_image  = '<a ' . ( ! $image_target || $image_target == 'same' ? '' : 'target="_blank"' ) . ' href="' . $url . '" class="entry-featured-image-url">';
								$post_image = '</a>';
							}
						}

						echo $pre_image;
						echo $thumb;

						if ( 'on' === $use_overlay ) {
							echo $overlay_output;
						}

						echo $post_image;

						if ( 'off' == $fullwidth ) {
							echo '</div> <!-- .et_pb_image_container -->';
						}
						if ( 'list' == $fullwidth ) {
							echo '</div> <!-- .et_pb_image_container -->';
						}
					endif;
				}

				if ( 'off' === $fullwidth || ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) {
					if ( ! in_array( $post_format, array( 'link', 'audio' ) ) ) { ?>
                        <h2 class="entry-title"><a
                                    href="<?php esc_url( the_permalink() ); ?>"><?php the_title(); ?></a></h2>
					<?php }

					if ( 'on' === $show_author || 'on' === $show_date || 'on' === $show_categories || 'on' === $show_comments ) {
						printf( '<p class="post-meta">%1$s %2$s %3$s %4$s %5$s %6$s %7$s</p>',
							(
							'on' === $show_author
								? et_get_safe_localization( sprintf( __( 'by %s', 'et_builder' ), '<span class="author vcard">' . et_pb_get_the_author_posts_link() . '</span>' ) )
								: ''
							),
							(
							( 'on' === $show_author && 'on' === $show_date )
								? ' | '
								: ''
							),
							(
							'on' === $show_date
								? et_get_safe_localization( sprintf( __( '%s', 'et_builder' ), '<span class="published">' . esc_html( get_the_date( $meta_date ) ) . '</span>' ) )
								: ''
							),
							(
							( ( 'on' === $show_author || 'on' === $show_date ) && 'on' === $show_categories )
								? ' | '
								: ''
							),
							(
							'on' === $show_categories
								? get_the_category_list( ', ' )
								: ''
							),
							(
							( ( 'on' === $show_author || 'on' === $show_date || 'on' === $show_categories ) && 'on' === $show_comments )
								? ' | '
								: ''
							),
							(
							'on' === $show_comments
								? sprintf( esc_html( _nx( '1 Comment', '%s Comments', get_comments_number(), 'number of comments', 'et_builder' ) ), number_format_i18n( get_comments_number() ) )
								: ''
							)
						);
					}

					$post_content = get_the_content();

					if ( 'none' !== $show_content ) {
						// do not display the content if it contains Blog, Post Slider, Fullwidth Post Slider, or Portfolio modules to avoid infinite loops
						if ( ! has_shortcode( $post_content, 'et_pb_blog' ) && ! has_shortcode( $post_content, 'et_pb_portfolio' ) && ! has_shortcode( $post_content, 'et_pb_post_slider' ) && ! has_shortcode( $post_content, 'et_pb_fullwidth_post_slider' ) ) {
							if ( 'on' === $show_content ) {
								global $more;

								if ( et_pb_is_pagebuilder_used( get_the_ID() ) ) {
									$more = 1;
									echo do_shortcode( get_the_content() );
								} else {
									$more = null;
									echo do_shortcode( get_the_content( esc_html__( 'read more...', 'et_builder' ) ) );
								}
							} else {
								if ( has_excerpt() ) {
									echo wpautop( get_the_excerpt() );
								} else {
									echo wpautop( truncate_post( 270, apply_filters( 'excerpt_more', '' ), false ) );
								}
							}

						} else if ( has_excerpt() ) {
							the_excerpt();
						}
					}

					if ( 'on' !== $show_content ) {
						$more = 'on' == $show_more ? sprintf( ' <p><a href="%1$s" class="et_pb_button more-link" >%2$s</a></p>', esc_url( get_permalink() ), esc_html__( 'read more', 'et_builder' ) ) : '';
						echo $more;
					}
				}

				echo do_action( 'sb_et_ept_li_pt_archive_end', get_the_ID() );

				echo '</div>';
				//</article> <!-- .et_pb_post -->

				if ( $fullwidth == 'off' ) { //grid
					echo '</div>';
				} else {
					echo '</div>';
				}

				$i ++;

				if ( $i == $cols && ( $fullwidth == 'off' ) ) {
					$i = 0;

					echo '</div>';
					echo '<div class="et_pb_row_cpt">';
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
		} else {
			if ( et_is_builder_plugin_active() ) {
				include( ET_BUILDER_PLUGIN_DIR . 'includes/no-results.php' );
			} else {
				get_template_part( 'includes/no-results', 'index' );
			}
		}

		$posts = ob_get_contents();

		ob_end_clean();

		$class = " et_pb_module et_pb_bg_layout_{$background_layout}";

		$output = sprintf(
			'<div%5$s class="%1$s%3$s%6$s"%7$s>
				%2$s
			%4$s',
			( 'on' == $fullwidth || $fullwidth == 'list' ? 'et_pb_posts et_pb_fullwidth_' . $fullwidth . ' clearfix' : 'et_pb_blog_grid et_pb_cpt_archive_grid clearfix' ),
			$posts,
			esc_attr( $class ),
			( ! $container_is_closed ? '</div> <!-- .et_pb_posts -->' : '' ),
			( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
			( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
			( 'on' !== $fullwidth ? ' data-columns' : '' )
		);

		if ( 'off' == $fullwidth ) {
			$output = sprintf( '<div class="et_pb_blog_grid_wrapper et_pb_blog_grid_pt_archive_wrapper">%1$s</div>', $output );
		} else if ( 'list' == $fullwidth ) {
			$output = sprintf( '<div class="et_pb_cpt_list_wrapper et_pb_blog_list_pt_archive_wrapper">%1$s</div>', $output );
		}

		return $output;
	}
}

new sb_et_cpt_li_pt_archive;

?>