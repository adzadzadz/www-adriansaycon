<?php

class sb_et_cpt_li_title_module extends ET_Builder_Module {

	public $vb_support = 'partial';

	protected $module_credits = array(
		'module_uri' => SB_ET_CPT_LI_STORE_URL,
		'author'     => SB_ET_CPT_LI_AUTHOR_NAME,
		'author_uri' => SB_ET_CPT_LI_AUTHOR_URL,
	);

	function init() {
		$this->name = __( 'CPT Title', 'et_builder' );
		$this->slug = 'et_pb_cpt_title';

		$this->fields_defaults = array(
			'background_layout' => array( 'light' ),
			'text_orientation'  => array( 'left' ),
		);

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_settings' => esc_html__( 'Main Settings', 'et_builder' ),
				),
			),
		);

		$this->custom_css_fields = array(
			'meta' => array(
				'label'    => 'Meta (catgory, date, etc..)',
				'selector' => 'p.et_pb_title_meta_container',
			),
		);

		$this->main_css_element = '%%order_class%%';

		$this->advanced_fields = array(
			'fonts'                 => array(
				'headings' => array(
					'label'       => esc_html__( 'Headings', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
					),
					'font_size'   => array( 'default' => '30px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'meta'     => array(
					'label'       => esc_html__( 'Meta', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} p.et_pb_title_meta_container, {$this->main_css_element} p.et_pb_title_meta_container a",
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
	}

	function get_fields() {
		$fields = array(
			'background_layout' => array(
				'label'           => esc_html__( 'Text Color', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'configuration',
				'options'         => array(
					'light' => esc_html__( 'Dark', 'et_builder' ),
					'dark'  => esc_html__( 'Light', 'et_builder' ),
				),
				'description'     => esc_html__( 'Here you can choose the value of your text. If you are working with a dark background, then your text should be set to light. If you are working with a light background, then your text should be dark.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'text_orientation'  => array(
				'label'           => esc_html__( 'Text Orientation', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'layout',
				'options'         => et_builder_get_text_orientation_options(),
				'description'     => esc_html__( 'This controls the how your text is aligned within the module.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'title'             => array(
				'label'           => esc_html__( 'Hide Title', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'off' => esc_html__( 'No', 'et_builder' ),
					'on'  => esc_html__( 'Yes', 'et_builder' ),
				),
				'description'     => esc_html__( 'Here you can choose whether or not hide the Post Title', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'meta'              => array(
				'label'           => esc_html__( 'Show Meta', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'affects'         => array(
					'author',
					'date',
					'categories',
					'comments',
				),
				'description'     => esc_html__( 'Here you can choose whether or not display the Post Meta', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'author'            => array(
				'label'           => esc_html__( 'Show Author', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Here you can choose whether or not display the Author Name in Post Meta', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'date'              => array(
				'label'           => esc_html__( 'Show Date', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'depends_show_if' => 'on',
				'affects'         => array(
					'date_format'
				),
				'description'     => esc_html__( 'Here you can choose whether or not display the Date in Post Meta', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'date_format'       => array(
				'label'           => esc_html__( 'Date Format', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Here you can define the Date Format in Post Meta. Default is \'M j, Y\'', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			/*'categories'        => array(
				'label'           => esc_html__( 'Show Post Categories', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Here you can choose whether or not display the Categories in Post Meta. Note: This option doesn\'t work with custom post types.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),*/
			'comments'          => array(
				'label'           => esc_html__( 'Show Comments Count', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Here you can choose whether or not display the Comments Count in Post Meta.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'max_width'         => array(
				'label'           => esc_html__( 'Max Width', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'layout',
				'mobile_options'  => true,
				'tab_slug'        => 'advanced',
				'validate_unit'   => true,
			),
			'max_width_tablet'  => array(
				'type'     => 'skip',
				'tab_slug' => 'advanced',
			),
			'max_width_phone'   => array(
				'type'     => 'skip',
				'tab_slug' => 'advanced',
			),
			'admin_label'       => array(
				'label'       => __( 'Admin Label', 'et_builder' ),
				'type'        => 'text',
				'description' => __( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
			),
			'module_id'         => array(
				'label'           => esc_html__( 'CSS ID', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'module_class'      => array(
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
		$id    = sb_et_cpt_li_get_id();
		$is_vb = sb_et_cpt_li_is_vb();

		$module_id         = $this->props['module_id'];
		$module_class      = $this->props['module_class'];
		$hide_title        = $this->props['title'];
		$meta              = $this->props['meta'];
		$author            = $this->props['author'];
		$date              = $this->props['date'];
		$date_format       = ( $this->props['date_format'] ? $this->props['date_format'] : get_option( 'date_format' ) );
		$comments          = $this->props['comments'];
		$background_layout = $this->props['background_layout'];
		$text_orientation  = $this->props['text_orientation'];
		$max_width         = $this->props['max_width'];
		$max_width_tablet  = $this->props['max_width_tablet'];
		$max_width_phone   = $this->props['max_width_phone'];

		$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );

		if ( '' !== $max_width_tablet || '' !== $max_width_phone || '' !== $max_width ) {
			$max_width_values = array(
				'desktop' => $max_width,
				'tablet'  => $max_width_tablet,
				'phone'   => $max_width_phone,
			);

			et_pb_generate_responsive_css( $max_width_values, '%%order_class%%', 'max-width', $function_name );
		}

		if ( is_rtl() && 'left' === $text_orientation ) {
			$text_orientation = 'right';
		}

		//////////////////////////////////////////////////////////////////////

		$output = '';

		if ( $hide_title != 'on' ) {
			if ( is_et_pb_preview() && isset( $_POST['post_title'] ) && wp_verify_nonce( $_POST['et_pb_preview_nonce'], 'et_pb_preview_nonce' ) ) {
				$post_title = sanitize_text_field( wp_unslash( $_POST['post_title'] ) );
			} else {
				$post_title = get_the_title();
			}

			if ( ! $is_vb || $post_title ) {
				//vb look at context here. archive or single
				if ( is_single() || is_page() ) {
					$output .= '<h1 itemprop="name" class="cpt_title page_title entry-title">' . $post_title . '</h1>';
				} else {
					$output .= '<h2 itemprop="name" class="cpt_title page_title entry-title"><a href="' . get_permalink( $id ) . '">' . $post_title . '</a></h2>';
				}
			} else {
				$output .= '<h1 itemprop="name" class="cpt_title page_title entry-title">Post Title Here</h1>';
			}

		}

		if ( 'on' === $meta ) {
			$meta_array = array();
			//foreach ( array( 'author', 'date', 'categories', 'comments' ) as $single_meta ) {
			//if ( 'on' === $$single_meta && ( 'categories' !== $single_meta || ( 'categories' === $single_meta ) ) ) {
			foreach ( array( 'author', 'date', 'comments' ) as $single_meta ) {
				if ( 'on' === $$single_meta ) {
					$meta_array[] = $single_meta;
				}
			}

			if ( ! $is_vb || $id ) {
				$output .= sprintf( '<p class="et_pb_title_meta_container">%1$s</p>',
					et_pb_postinfo_meta( $meta_array, $date_format, esc_html__( '0 comments', 'et_builder' ), esc_html__( '1 comment', 'et_builder' ), '% ' . esc_html__( 'comments', 'et_builder' ) )
				);
			} else {
				$output .= '<p class="et_pb_title_meta_container"> ' . ( $author == 'on' ? 'by <span class="author vcard">Username</span> | ' : '' ) . ( $date == 'on' ? '<span class="published">01/01/2019</span> | ' : '' ) . ( $comments == 'on' ? '<span class="comments-number"><a href="#respond">1 comment</a></span>' : '' ) . '</p>';
			}
		}

		//////////////////////////////////////////////////////////////////////

		if ( $output ) {
			$output = sprintf(
				'<div%5$s class="%1$s%3$s%6$s">
																										%2$s
																								%4$s',
				'clearfix ',
				$output,
				esc_attr( 'et_pb_module et_pb_bg_layout_' . $background_layout . ' et_pb_text_align_' . $text_orientation ),
				'</div>',
				( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
				( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
			);
		}

		return $output;
	}
}

new sb_et_cpt_li_title_module();

?>