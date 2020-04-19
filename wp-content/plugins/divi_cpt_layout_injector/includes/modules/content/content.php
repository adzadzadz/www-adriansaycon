<?php

class sb_et_cpt_li_content_module extends ET_Builder_Module {

	public $vb_support = 'partial';

	protected $module_credits = array(
		'module_uri' => SB_ET_CPT_LI_STORE_URL,
		'author'     => SB_ET_CPT_LI_AUTHOR_NAME,
		'author_uri' => SB_ET_CPT_LI_AUTHOR_URL,
	);

	function init() {
		$this->name = __( 'CPT Content', 'et_builder' );
		$this->slug = 'et_pb_cpt_text';

		$this->fields_defaults = array(
			'background_layout' => array( 'light' ),
			'text_orientation'  => array( 'left' ),
		);

		$this->main_css_element = '%%order_class%%';

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_settings' => esc_html__( 'Main Settings', 'et_builder' ),
				),
			),
		);

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
				'buttons'  => array(
					'label' => esc_html__( 'Read More Button', 'et_builder' ),
					'css'   => array(
						'main' => "{$this->main_css_element} .et_pb_more_button",
					),
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
			'excerpt_only'      => array(
				'label'           => __( 'Excerpt Only?', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'off' => __( 'No', 'et_builder' ),
					'on'  => __( 'Yes', 'et_builder' ),
				),
				'description'     => __( 'Should this show content only or excerpt?', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'show_read_more'    => array(
				'label'           => __( 'Show Read More?', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'off' => __( 'No', 'et_builder' ),
					'on'  => __( 'Yes', 'et_builder' ),
				),
				'affects'         => array( 'read_more_label' ),
				'description'     => __( 'Should a read more button be shown below the content?', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'read_more_label'   => array(
				'label'           => __( 'Read More Label', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'on',
				'description'     => __( 'What should the read more button be labelled as? Defaults to "Read More".', 'et_builder' ),
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
		$excerpt_only      = $this->props['excerpt_only'];
		$show_read_more    = $this->props['show_read_more'];
		$read_more_label   = $this->props['read_more_label'];
		$background_layout = $this->props['background_layout'];
		$text_orientation  = $this->props['text_orientation'];
		$max_width         = $this->props['max_width'];
		$max_width_tablet  = $this->props['max_width_tablet'];
		$max_width_phone   = $this->props['max_width_phone'];

		$read_more_label = ( $read_more_label ? $read_more_label : 'Read More' );

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

		if (!$is_vb || $id) {
			if ( $excerpt_only == 'on' ) {
				//ob_start();
				//the_excerpt();
				//$content = ob_get_clean();
				$content = apply_filters( 'the_content', get_the_excerpt() );
			} else {
				$content = get_the_content();
				$content = apply_filters( 'the_content', $content );

				//$content = wpautop( get_the_content() );
			}
		} else {
			if ( $excerpt_only == 'on' ) {
				$content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed tempus nibh sed elimttis adipiscing.';
			} else {
				$content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed tempus nibh sed elimttis adipiscing. Fusce in hendrerit purus. Suspendisse potenti. Proin quis eros odio, dapibus dictum mauris. Donec nisi libero, adipiscing id pretium eget, consectetur sit amet leo. Nam at eros quis mi egestas fringilla non nec purus.';
			}

			$content = apply_filters( 'the_content', $content );
		}

		if ( $show_read_more == 'on' ) {
			$content .= '<p><a class="button et_pb_button et_pb_more_button" href="' . get_permalink( $id ) . '">' . $read_more_label . '</a></p>';
		}

		//////////////////////////////////////////////////////////////////////

		$output = sprintf(
			'<div%5$s class="%1$s%3$s%6$s">
                            %2$s
                        %4$s',
			'clearfix ',
			$content,
			esc_attr( 'et_pb_module et_pb_cpt_text et_pb_bg_layout_' . $background_layout . ' et_pb_text_align_' . $text_orientation ),
			'</div>',
			( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
			( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
		);

		return $output;
	}
}

new sb_et_cpt_li_content_module();

?>