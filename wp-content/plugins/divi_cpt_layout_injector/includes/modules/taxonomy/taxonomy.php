<?php

class sb_et_cpt_li_taxonomy_module extends ET_Builder_Module {

	public $vb_support = 'partial';

	protected $module_credits = array(
		'module_uri' => SB_ET_CPT_LI_STORE_URL,
		'author'     => SB_ET_CPT_LI_AUTHOR_NAME,
		'author_uri' => SB_ET_CPT_LI_AUTHOR_URL,
	);

	function init() {
		$this->name = __( 'CPT Taxonomy', 'et_builder' );
		$this->slug = 'et_pb_cpt_taxonomy';

		$this->fields_defaults  = array();
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
						'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
					),
					'font_size'   => array( 'default' => '30px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'prefix'   => array(
					'label'       => esc_html__( 'Prefix', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .sb_cpt_term_prefix",
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'suffix'   => array(
					'label'       => esc_html__( 'Suffix', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .sb_cpt_term_suffix",
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
		$tax = sb_et_cpt_li_get_taxonomies();

		$fields = array(
			'admin_label'  => array(
				'label'       => __( 'Admin Label', 'et_builder' ),
				'type'        => 'text',
				'description' => __( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
			),
			'taxonomy'     => array(
				'label'       => esc_html__( 'Taxonomy', 'et_builder' ),
				'type'        => 'select',
				'options'     => $tax,
				'description' => 'Which taxonomy should the system check against. This will display a list of links to categories etc that this post is tagged against',
				'toggle_slug' => 'main_settings',
			),
			'prefix'       => array(
				'label'       => esc_html__( 'Prefix', 'et_builder' ),
				'type'        => 'text',
				'description' => 'Should the list of terms start with something? If you are showing categories then you may want to add "Categories: " into this box for instance.',
				'toggle_slug' => 'main_settings',
			),
			'suffix'       => array(
				'label'       => esc_html__( 'Suffix', 'et_builder' ),
				'type'        => 'text',
				'description' => 'Should the list of terms end with something? If you are showing your taxonomies in quotes you may want to add " into this box.',
				'toggle_slug' => 'main_settings',
			),
			'separator'    => array(
				'label'       => esc_html__( 'Separator', 'et_builder' ),
				'type'        => 'text',
				'description' => 'When there is more than one term to display what should separate them. Eg | or ,',
				'toggle_slug' => 'main_settings',
			),
			'module_id'    => array(
				'label'           => esc_html__( 'CSS ID', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'module_class' => array(
				'label'           => esc_html__( 'CSS Class', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
		);

		//print_r($fields);

		return $fields;
	}

	function render( $atts, $content = null, $function_name ) {
		$id    = sb_et_cpt_li_get_id();
		$is_vb = sb_et_cpt_li_is_vb();

		$module_id    = $this->props['module_id'];
		$module_class = $this->props['module_class'];
		$taxonomy     = $this->props['taxonomy'];
		$prefix       = $this->props['prefix'];
		$suffix       = $this->props['suffix'];
		$separator    = $this->props['separator'];
		$output       = $content = '';

		$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );

		//////////////////////////////////////////////////////////////////////

		if (!$is_vb) {

			$product_terms = wp_get_object_terms( $id, $taxonomy );
			$term_array    = array();

			if ( ! empty( $product_terms ) ) {
				if ( ! is_wp_error( $product_terms ) ) {
					foreach ( $product_terms as $term ) {
						//sometimes this returns an error because of plugins like cptonomies. using get_permalink may also fail but it's a good fallback
						$link = get_term_link( $term->slug, $taxonomy );

						if ( is_wp_error( $link ) ) {
							$link = get_permalink( $term->term_id );
						}

						if ( $link ) {
							$term_array[] = '<a href="' . $link . '">' . esc_html( $term->name ) . '</a>';
						} else {
							$term_array[] = esc_html( $term->name );
						}
					}

					$content = '<span class="sb_cpt_term_list">';

					if ( $prefix ) {
						$content .= '<span class="sb_cpt_term_prefix">' . $prefix . '</span>';
					}

					$content .= implode( $separator, $term_array );

					if ( $suffix ) {
						$content .= '<span class="sb_cpt_term_suffix">' . $suffix . '</span>';
					}

					$content .= '</span>';
				}
			}
		} else {
			//for VB look and feel
			$content = '<span class="sb_cpt_term_list">';

			if ( $prefix ) {
				$content .= '<span class="sb_cpt_term_prefix">' . $prefix . '</span>';
			}

			$content .= implode( $separator, array('Term 1', 'Term 2', '<a href="#">Term 3 link</a>') );

			if ( $suffix ) {
				$content .= '<span class="sb_cpt_term_suffix">' . $suffix . '</span>';
			}

			$content .= '</span>';
		}

		//////////////////////////////////////////////////////////////////////

		if ( $content ) {
			$output = '<div ' . ( $module_id ? ' id="' . esc_attr( $module_id ) . '"' : '' ) . ' class="clearfix et_pb_module ' . $module_class . '">' . $content . '</div>';
		}

		return $output;
	}
}

new sb_et_cpt_li_taxonomy_module();

?>