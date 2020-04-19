<?php

class et_pb_acf_table_item extends ET_Builder_Module {
	function init() {
		$this->name            = esc_html__( 'ACF Field', 'et_builder' );
		$this->slug            = 'et_pb_acf_table_item';
		$this->type            = 'child';
		$this->child_title_var = 'title';
		$this->vb_support      = 'partial';

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_settings' => esc_html__( 'Main Settings', 'et_builder' ),
				),
			),
		);

		$this->advanced_setting_title_text = esc_html__( 'New ACF Field', 'et_builder' );
		$this->settings_text               = esc_html__( 'ACF Field Settings', 'et_builder' );
		$this->main_css_element            = '%%order_class%%';

		$this->advanced_fields = array(
			'fonts'                 => array(
				'text'  => array(
					'label'       => esc_html__( 'Label', 'et_builder' ),
					'css'         => array(
						'main' => "td{$this->main_css_element}.sb_mod_acf_table_item.sb_mod_acf_table_item_label",
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'value' => array(
					'label'       => esc_html__( 'Value', 'et_builder' ),
					'css'         => array(
						'main' => "td{$this->main_css_element}.sb_mod_acf_table_item.sb_mod_acf_table_item_value",
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
		$options = sb_mod_acf_get_fields( false, true );

		$image_link_options = array(
			'none'  => 'No Link'
		,
			'image' => 'Larger version'
		,
			'page'  => 'Same Page'
		);

		$image_options = array();
		$sizes         = get_intermediate_image_sizes();

		foreach ( $sizes as $size ) {
			$image_options[ $size ] = $size;
		}

		$multiple_options = array(
			'default' => 'Bullet List',
			'slash'   => ' On one line separated by /',
			'comma'   => ' On one line separated by ,'
		);

		$fields = array(
			'field_name'       => array(
				'label'       => __( 'Field', 'et_builder' ),
				'type'        => 'select',
				'options'     => $options,
				'description' => __( 'Pick which field to show.', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'title'            => array(
				'label'       => esc_html__( 'Title', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'The label will be used for this field on the front end.', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'image_size'       => array(
				'label'       => __( 'Image Size', 'et_builder' ),
				'type'        => 'select',
				'options'     => $image_options,
				'description' => __( 'If this is an image type then choose a size from here. If there is no size you like in the list consider using the free <a href="https://wordpress.org/plugins/simple-image-sizes/" target="_blank">Simple Image Sizes</a> plugin where you can define your own.', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'link_image'       => array(
				'label'       => __( 'Image Link', 'et_builder' ),
				'type'        => 'select',
				'options'     => $image_link_options,
				'description' => __( 'If this is an image type then please choose how it should be linked. This means that when the image is clicked, what should happen.', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'date_format'      => array(
				'label'       => esc_html__( 'Date Format', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'If this is a date picker type, enter format here. (Optional)', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'multiples_format' => array(
				'label'       => __( 'Multiples Format', 'et_builder' ),
				'type'        => 'select',
				'options'     => $multiple_options,
				'description' => __( 'If your item is a checkbox or a multi select of any kind then normally we would show then im a bullet list but this allows you to choose different output types', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'format_output'    => array(
				'label'       => __( 'Output Format', 'et_builder' ),
				'type'        => 'select',
				'options'     => array(
					'none'          => 'Default',
					'autop'         => 'Add Paragraphs',
					'audio'         => 'Show Audio Player',
					'video'         => 'Show Video Player',
					'button'        => 'Button',
					'number_format' => 'Number Format'
				),
				'affects'     => array( 'button_text', 'decimal_places' ),
				'description' => __( 'How should the output be formatted?', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'button_text'      => array(
				'label'           => __( 'Button Text', 'et_builder' ),
				'type'            => 'text',
				'description'     => __( 'If a button output above then what should the label be?', 'et_builder' ),
				'depends_show_if' => 'button',
				'toggle_slug'     => 'main_settings',
			),
			'decimal_places'   => array(
				'label'           => __( 'Decimal Places', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'number_format',
				'description'     => __( 'If a formatted number above then how many decimal places should it be to?', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'before'           => array(
				'label'       => esc_html__( 'Before Text', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'Text to show before. For example a currency symbol. Note this won\'t show if the field is empty. Shortcodes accepted!', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'after'            => array(
				'label'       => esc_html__( 'After Text', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'Text to show after. For example a label. Note this won\'t show if the field is empty. Shortcodes accepted!', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'module_id'        => array(
				'label'           => esc_html__( 'CSS ID', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'module_class'     => array(
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
		if ( ! function_exists( 'get_field' ) ) {
			return;
		}

		global $et_pt_acf_table_titles;
		global $et_pt_acf_table_classes;

		$title            = $this->props['title'];
		$before           = ( @$this->props['before'] ? $this->props['before'] : '' );
		$after            = ( @$this->props['after'] ? $this->props['after'] : '' );
		$field_name       = $this->props['field_name'];
		$image_size       = $this->props['image_size'];
		$link_image       = $this->props['link_image'];
		$date_format      = ( $this->props['date_format'] ? $this->props['date_format'] : get_option( 'date_format' ) );
		$multiples_format = ( $this->props['multiples_format'] ? $this->props['multiples_format'] : 'default' );
		$format_output    = ( @$this->props['format_output'] ? $this->props['format_output'] : 'none' );

		$module_class = ET_Builder_Element::add_module_order_class( '', $function_name );

		$et_pt_acf_table_titles[]  = '' !== $title ? $title : esc_html__( 'ACF Field', 'et_builder' );
		$et_pt_acf_table_classes[] = $module_class;

		$output = '';

		if ( $field_arr = explode( '|', $field_name ) ) {
			$field_name = $field_arr[1];
		}

		if ( sb_mod_acf_is_vb() ) {
			$value = 'Example value';
			$title = ( $title ? $title : 'Label Text' );

			$output = '<tr>
                                <td valign="top" class="sb_mod_acf_table_item sb_mod_acf_table_item_label clearfix ' . esc_attr( $module_class ) . '">' . $title . '</td>
                                <td valign="top" class="sb_mod_acf_table_item sb_mod_acf_table_item_value ' . esc_attr( $module_class ) . '">' . $value . '</td>
                           </tr>';

		} else {

			$qo         = get_queried_object();
			$additional = sb_mod_acf_get_id();

			//////////////////////////

			$field = false;

			if ( is_a( $qo, 'WP_Term' ) ) {
				$additional_term = trim( $qo->taxonomy . '_' . $qo->term_id );
				$field           = get_field_object( $field_name, $additional_term );
			} //future add support for user pages and maybe options pages.

			if ( ! $field ) {
				$field = get_field_object( $field_name, $additional );
			}

			if ( $field ) {

				//////////////////////////


				//if ( $field = get_field_object( $field_name, $additional ) ) {

				if ( ! $title ) {
					$title = $field['label'];
				}

				$value = sb_mod_acf_parse_value_by_type( $field, $image_size, false, $date_format, $link_image, $multiples_format );

				if ( $value ) {

					if ( $format_output != 'none' ) {
						$value = sb_mod_acf_format_output( $value, $this->props, $field );
					}

					$value = do_shortcode( $before . $value . $after );

					$output = '<tr>
                                <td valign="top" class="sb_mod_acf_table_item sb_mod_acf_table_item_label clearfix ' . esc_attr( $module_class ) . '">' . $title . '</td>
                                <td valign="top" class="sb_mod_acf_table_item sb_mod_acf_table_item_value ' . esc_attr( $module_class ) . '">' . $value . '</td>
                           </tr>';
				}
			}
		}

		return $output;
	}
}

new et_pb_acf_table_item;

?>
