<?php

class sb_et_cpt_li_postnav_module extends ET_Builder_Module {

	public $vb_support = 'partial';

	protected $module_credits = array(
		'module_uri' => SB_ET_CPT_LI_STORE_URL,
		'author'     => SB_ET_CPT_LI_AUTHOR_NAME,
		'author_uri' => SB_ET_CPT_LI_AUTHOR_URL,
	);

	function init() {
		$this->name            = __( 'CPT Post Nav', 'et_builder' );
		$this->slug            = 'et_pb_cpt_postnav';
		$this->fields_defaults = array();

		$this->settings_modal_toggles = array(
			'general' => array(
				'toggles' => array(
					'main_settings' => esc_html__( 'Main Settings', 'et_builder' ),
				),
			),
		);

		$this->main_css_element = '%%order_class%%';

		$this->advanced_fields = array(
			'fonts'                 => array(
				'text'     => array(
					'label'       => esc_html__( 'Text', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .sb_pb_pagination a, {$this->main_css_element} .sb_pb_pagination span, {$this->main_css_element} p",
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
		$fields = array(
			'admin_label'  => array(
				'label'       => __( 'Admin Label', 'et_builder' ),
				'type'        => 'text',
				'description' => __( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
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
			)
		);

		return $fields;
	}

	function render( $atts, $content = null, $function_name ) {
		//$id    = sb_et_cpt_li_get_id();
		$is_vb = sb_et_cpt_li_is_vb();

		$module_id    = $this->props['module_id'];
		$module_class = $this->props['module_class'];

		$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );

		if ( ! $is_vb ) {
			//////////////////////////////////////////////////////////////////////
			ob_start();
			?>
            <div class="sb_pb_pagination pagination clearfix">
                <div class="alignleft"><?php previous_post_link(); ?></div>
                <div class="alignright"><?php next_post_link(); ?></div>
            </div>
			<?php
			$output = ob_get_clean();
		} else {
			$output = '<div class="sb_pb_pagination pagination clearfix">
                        <div class="alignleft">&#0171; Previous Post Link</div>
                        <div class="alignright">Next Post Link &#0187;</div>
                    </div>';
		}

		//////////////////////////////////////////////////////////////////////

		$output = '<div ' . ( $module_id ? ' id="' . esc_attr( $module_id ) . '"' : '' ) . ' class="clearfix et_pb_module ' . $module_class . '">' . $output . '</div>';

		return $output;
	}
}

new sb_et_cpt_li_postnav_module();

?>