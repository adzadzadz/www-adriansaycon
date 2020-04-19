<?php

class et_pb_acf_repeater_table extends ET_Builder_Module {
	function init() {
		$this->name       = esc_html__( 'ACF Repeater Table', 'et_builder' );
		$this->slug       = 'et_pb_acf_repeater_table';
		$this->vb_support = 'partial';

		$this->fields_defaults  = array();
		$this->main_css_element = '%%order_class%%.et_pb_acf_repeater_table';

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_settings' => esc_html__( 'Main Settings', 'et_builder' ),
				),
			),
		);

		$this->advanced_fields = array(
			'fonts'                 => array(
				'headings'    => array(
					'label'       => esc_html__( 'Headings', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
					),
					'font_size'   => array( 'default' => '30px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'heading_row' => array(
					'label'       => esc_html__( 'Column Headers', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .sb_mod_acf_table_item_label_row td.sb_mod_acf_table_item_label",
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'content_row' => array(
					'label'       => esc_html__( 'Row Text Style', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .sb_mod_acf_table_item_value_row td.sb_mod_acf_table_item",
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
		$options = sb_mod_acf_get_fields( true, true );

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

		$fields = array(
			'field_name'       => array(
				'label'       => __( 'Field', 'et_builder' ),
				'type'        => 'select',
				'options'     => $options,
				'description' => __( 'Pick which field to show.', 'et_builder' ),
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
			'title'            => array(
				'label'       => esc_html__( 'Title', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'The label that will be used for this field on the front end. (Optional)', 'et_builder' ),
				'toggle_slug' => 'main_settings',
			),
			'admin_label'      => array(
				'label'       => esc_html__( 'Admin Label', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
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
			'default_style'    => array(
				'label'           => esc_html__( 'Use Default Styling', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'affects'         => array(
					'odd_row_colour',
					'even_row_colour',
					'odd_text_colour',
					'even_text_colour',
					'v_padding',
					'h_padding',
				),
				'toggle_slug'     => 'main_settings',
				'description'     => esc_html__( 'This will turn on or off the detault layout for the table.', 'et_builder' ),
			),
			'odd_row_colour'   => array(
				'label'           => esc_html__( 'Odd Row Colour', 'et_builder' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'depends_show_if' => 'off',
				'description'     => esc_html__( 'Here you can define a custom color for the ODD rows in the table', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'odd_text_colour'  => array(
				'label'           => esc_html__( 'Odd Text Colour', 'et_builder' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'depends_show_if' => 'off',
				'description'     => esc_html__( 'Here you can define a custom text color for the ODD rows in the table', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'even_row_colour'  => array(
				'label'           => esc_html__( 'Even Row Colour', 'et_builder' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'depends_show_if' => 'off',
				'description'     => esc_html__( 'Here you can define a custom color for the EVEN rows in the table', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'even_text_colour' => array(
				'label'           => esc_html__( 'Even Text Colour', 'et_builder' ),
				'type'            => 'color-alpha',
				'custom_color'    => true,
				'depends_show_if' => 'off',
				'description'     => esc_html__( 'Here you can define a custom text color for the EVEN rows in the table', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'v_padding'        => array(
				'label'           => esc_html__( 'Vertical Padding', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'off',
				'mobile_options'  => true,
				'validate_unit'   => true,
				'toggle_slug'     => 'main_settings',
			),
			'h_padding'        => array(
				'label'           => esc_html__( 'Horizontal Padding', 'et_builder' ),
				'type'            => 'text',
				'depends_show_if' => 'off',
				'mobile_options'  => true,
				'validate_unit'   => true,
				'toggle_slug'     => 'main_settings',
			),
		);

		return $fields;
	}

	function render( $atts, $content = null, $function_name ) {
		if (! function_exists( 'get_field' )) {
			return '';
		}

		$output = '';

		$title            = $this->props['title'];
		$field            = $this->props['field_name'];
		$image_size       = $this->props['image_size'];
		$module_id        = $this->props['module_id'];
		$module_class     = $this->props['module_class'];
		$default_style    = $this->props['default_style'];
		$odd_row_colour   = $this->props['odd_row_colour'];
		$even_row_colour  = $this->props['even_row_colour'];
		$odd_text_colour  = $this->props['odd_text_colour'];
		$even_text_colour = $this->props['even_text_colour'];
		$vpadding         = $this->props['v_padding'];
		$hpadding         = $this->props['h_padding'];
		$link_image       = $this->props['link_image'];
		$date_format      = ( $this->props['date_format'] ? $this->props['date_format'] : get_option( 'date_format' ) );

		$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name ) . ( $default_style == 'on' ? ' et_pb_acf_table_styled' : '' );

		if ( $default_style == 'off' ) {
			if ( '' !== $odd_row_colour ) {
				ET_Builder_Element::set_style( $function_name, array(
					'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(odd)',
					'declaration' => sprintf(
						'background-color: %1$s;',
						esc_html( $odd_row_colour )
					),
				) );
			}

			if ( '' !== $even_row_colour ) {
				ET_Builder_Element::set_style( $function_name, array(
					'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(even)',
					'declaration' => sprintf(
						'background-color: %1$s;',
						esc_html( $even_row_colour )
					),
				) );
			}
			if ( '' !== $odd_text_colour ) {
				ET_Builder_Element::set_style( $function_name, array(
					'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(odd) td',
					'declaration' => sprintf(
						'color: %1$s;',
						esc_html( $odd_text_colour )
					),
				) );
			}

			if ( '' !== $even_text_colour ) {
				ET_Builder_Element::set_style( $function_name, array(
					'selector'    => '%%order_class%%.et_pb_acf_table tbody tr:nth-child(even) td',
					'declaration' => sprintf(
						'color: %1$s;',
						esc_html( $even_text_colour )
					),
				) );
			}

			if ( '' !== $vpadding ) {
				ET_Builder_Element::set_style( $function_name, array(
					'selector'    => '%%order_class%%.et_pb_acf_table tbody tr td',
					'declaration' => sprintf(
						'padding-top: %1$s; padding-bottom: %1$s;',
						esc_html( $vpadding )
					),
				) );
			}

			if ( '' !== $hpadding ) {
				ET_Builder_Element::set_style( $function_name, array(
					'selector'    => '%%order_class%%.et_pb_acf_table tbody tr td',
					'declaration' => sprintf(
						'padding-left: %1$s; padding-right: %1$s;',
						esc_html( $hpadding )
					),
				) );
			}
		}

		if ( $field ) {

			if ( $field_arr = explode( '|', $field ) ) {
				$field = $field_arr[1];
			}

			$subfield_labels = array();

			if ( $field_obj = get_field_object( $field ) ) {
				if ( is_array( $field_obj ) && count( $field_obj ) > 0 ) {
					foreach ( $field_obj['sub_fields'] as $sub_field ) {
						$subfield_labels[ $sub_field['name'] ] = $sub_field['label'];
					}
				}

				if ( have_rows( $field ) ) {
					$output .= '<tr class="sb_mod_acf_table_item_label_row">';
					foreach ( $subfield_labels as $sub_field_name => $sub_field_label ) {
						$output .= '<td valign="top" class="sb_mod_acf_table_item sb_mod_acf_table_item_label clearfix">' . $sub_field_label . '</td>';
					}
					$output .= '</tr>';

					while ( have_rows( $field ) ) {
						the_row();

						$output .= '<tr class="sb_mod_acf_table_item_value_row">';

						foreach ( $subfield_labels as $sub_field_name => $sub_field_label ) {
							$sub_field = get_sub_field_object( $sub_field_name );
							$val       = '';

							if ( is_array( $sub_field ) && isset( $sub_field['value'] ) ) {
								$val = sb_mod_acf_parse_value_by_type( $sub_field, $image_size, true, $date_format, $link_image );
							}

							$output .= '<td valign="top" class="sb_mod_acf_table_item clearfix">' . $val . '</td>';
						}

						$output .= '</tr>';
					}
				}
			} else if ( sb_mod_acf_is_vb() ) {
				$output .= '<p style="color: red;"><em>These are examples. Your own data will show on the front end</em></p>';

				$output .= '<tr class="sb_mod_acf_table_item_label_row">';
				$output .= '<td valign="top" class="sb_mod_acf_table_item sb_mod_acf_table_item_label clearfix">Example Title 1</td>';
				$output .= '<td valign="top" class="sb_mod_acf_table_item sb_mod_acf_table_item_label clearfix">Example Title 2</td>';
				$output .= '</tr>';

				$output .= '<tr class="sb_mod_acf_table_item_value_row">';
				$output .= '<td valign="top" class="sb_mod_acf_table_item clearfix">Example Col 1</td>';
				$output .= '<td valign="top" class="sb_mod_acf_table_item clearfix">Example Col 2</td>';
				$output .= '</tr>';

				$output .= '<tr class="sb_mod_acf_table_item_value_row">';
				$output .= '<td valign="top" class="sb_mod_acf_table_item clearfix">Example Col 1</td>';
				$output .= '<td valign="top" class="sb_mod_acf_table_item clearfix">Example Col 2</td>';
				$output .= '</tr>';
			}
		} else {
			$output = '<p style="color: red;">Please select an ACF field to output</p>';
		}

		if ( $output ) {
			$output = '<div ' . ( $module_id ? 'id="' . esc_attr( $module_id ) . '" ' : '' ) . ' class="et_pb_module et_pb_acf_table et_pb_acf_repeater_table ' . $module_class . '">
							' . ( $title ? '<h3>' . $title . '</h3>' : '' ) . '
							<table class="">
							    <tbody>
								' . $output . '
							    </tbody>
							</table>
						    </div> <!-- .et_pt_acf_repeater_table -->';
		}

		return $output;
	}
}

new et_pb_acf_repeater_table;

?>