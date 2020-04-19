<?php

class sb_et_cpt_li_read_more_module extends ET_Builder_Module {

	public $vb_support = 'partial';

	function init() {
		$this->name = __( 'CPT Read More', 'et_builder' );
		$this->slug = 'et_pb_cpt_read_more';

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_settings' => esc_html__( 'Main Settings', 'et_builder' ),
				),
			),
		);

		$this->fields_defaults = array(
			'background_layout' => array( 'light' ),
			'text_orientation'  => array( 'left' ),
		);

		$this->main_css_element = '%%order_class%%';

		$this->advanced_fields = array(
			'fonts'                 => array(
				'text' => array(
					'label'       => esc_html__( 'Text', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element}.et_cpt_read_more.et_pb_button",
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
			'button_text'       => array(
				'label'           => esc_html__( 'Button Text', 'et_builder' ),
				'type'            => 'text',
				'toggle_slug'     => 'main_settings',
				'option_category' => 'layout',
				'description'     => esc_html__( 'What should the label button say? Defaults to "Find out more"', 'et_builder' ),
			),
			'background_layout' => array(
				'label'           => esc_html__( 'Text Color', 'et_builder' ),
				'type'            => 'select',
				'toggle_slug'     => 'main_settings',
				'option_category' => 'configuration',
				'options'         => array(
					'light' => esc_html__( 'Dark', 'et_builder' ),
					'dark'  => esc_html__( 'Light', 'et_builder' ),
				),
				'description'     => esc_html__( 'Here you can choose the value of your text. If you are working with a dark background, then your text should be set to light. If you are working with a light background, then your text should be dark.', 'et_builder' ),
			),
			'text_orientation'  => array(
				'label'           => esc_html__( 'Text Orientation', 'et_builder' ),
				'type'            => 'select',
				'toggle_slug'     => 'main_settings',
				'option_category' => 'layout',
				'options'         => et_builder_get_text_orientation_options(),
				'description'     => esc_html__( 'This controls the how your text is aligned within the module.', 'et_builder' ),
			),
			'max_width'         => array(
				'label'           => esc_html__( 'Max Width', 'et_builder' ),
				'type'            => 'text',
				'toggle_slug'     => 'main_settings',
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

		$module_id         = $this->props['module_id'];
		$module_class      = $this->props['module_class'];
		$button_text       = $this->props['button_text'];
		$background_layout = $this->props['background_layout'];
		$text_orientation  = $this->props['text_orientation'];
		$max_width         = $this->props['max_width'];
		$max_width_tablet  = $this->props['max_width_tablet'];
		$max_width_phone   = $this->props['max_width_phone'];

		if ( ! $button_text ) {
			$button_text = 'Find Out More';
		}

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

		$content = '<a class="et_cpt_read_more et_pb_button" href="' . get_permalink( get_the_ID() ) . '">' . $button_text . '</a>';

		//////////////////////////////////////////////////////////////////////

		$output = sprintf(
			'<div%5$s class="%1$s%3$s%6$s">
                                                    %2$s
                                                %4$s',
			'clearfix ',
			$content,
			esc_attr( 'et_pb_module et_pb_bg_layout_' . $background_layout . ' et_pb_text_align_' . $text_orientation ),
			'</div>',
			( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
			( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
		);

		return $output;
	}
}

new sb_et_cpt_li_read_more_module();

?>