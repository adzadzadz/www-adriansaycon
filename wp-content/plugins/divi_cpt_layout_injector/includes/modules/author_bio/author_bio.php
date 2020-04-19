<?php

class sb_et_cpt_li_author_bio_module extends ET_Builder_Module {

	public $vb_support = 'partial';

	protected $module_credits = array(
		'module_uri' => SB_ET_CPT_LI_STORE_URL,
		'author'     => SB_ET_CPT_LI_AUTHOR_NAME,
		'author_uri' => SB_ET_CPT_LI_AUTHOR_URL,
	);

	function init() {
		$this->name = __( 'CPT Author Bio', 'et_builder' );
		$this->slug = 'et_pb_cpt_author_bio';

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
				'text'         => array(
					'label'       => esc_html__( 'Text', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} p",
					),
					'font_size'   => array( 'default' => '14px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'headings'     => array(
					'label'       => esc_html__( 'Headings', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} h1, {$this->main_css_element} h2, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h1 a, {$this->main_css_element} h2 a, {$this->main_css_element} h3, {$this->main_css_element} h4",
					),
					'font_size'   => array( 'default' => '30px' ),
					'line_height' => array( 'default' => '1.5em' ),
				),
				'social_media' => array(
					'label'       => esc_html__( 'Social Media Icons', 'et_builder' ),
					'css'         => array(
						'main' => "{$this->main_css_element} .sb_et_cpt_li_author_social .et-social-icons a",
					),
					'font_size'   => array( 'default' => '24px' ),
					'line_height' => array( 'default' => '1.2em' ),
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
			'admin_label'             => array(
				'label'       => __( 'Admin Label', 'et_builder' ),
				'type'        => 'text',
				'description' => __( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
			),
			'module_id'               => array(
				'label'           => esc_html__( 'CSS ID', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'module_class'            => array(
				'label'           => esc_html__( 'CSS Class', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'author_name'             => array(
				'label'           => esc_html__( 'Show Author Name', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'affects'         => array(
					'author_name_link',
					'author_name_target',
					'author_name_before',
					//'author_name_after',
				),
				'toggle_slug'     => 'main_settings',
			),
			'author_name_link'        => array(
				'label'           => esc_html__( 'Link Author Name', 'et_builder' ),
				'type'            => 'select',
				'options'         => array(
					'author_url' => esc_html__( 'Author URL (automatic WP archive)', 'et_builder' ),
					'user_url'   => esc_html__( 'User URL (set in profile)', 'et_builder' ),
					'none'       => esc_html__( 'No link', 'et_builder' ),
				),
				'option_category' => 'configuration',
				'depends_show_if' => 'on',
				'toggle_slug'     => 'main_settings',
			),
			'author_name_target'      => array(
				'label'           => esc_html__( 'Link Target', 'et_builder' ),
				'type'            => 'select',
				'options'         => array(
					'none'  => 'None',
					'blank' => 'Blank',
				),
				'option_category' => 'configuration',
				'depends_show_if' => 'on',
				'toggle_slug'     => 'main_settings',
			),
			'author_name_before'      => array(
				'label'           => esc_html__( 'Pre Text', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'depends_show_if' => 'on',
				'description'     => 'The text to show before this item.. (eg: About the author...)',
				'toggle_slug'     => 'main_settings',
			),
			'author_bio'              => array(
				'label'           => esc_html__( 'Show Author Bio', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'toggle_slug'     => 'main_settings',
			),
			'author_website'          => array(
				'label'           => esc_html__( 'Show Author Website', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'affects'         => array(
					'author_website_before',
				),
				'toggle_slug'     => 'main_settings',
			),
			'author_website_before'   => array(
				'label'           => esc_html__( 'Pre Text', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'depends_show_if' => 'on',
				'description'     => 'The text to show before this item.. (eg: Author website...)',
				'toggle_slug'     => 'main_settings',
			),
			'author_social'           => array(
				'label'           => esc_html__( 'Show Author Social Media', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'toggle_slug'     => 'main_settings',
			),
			'author_avatar'           => array(
				'label'           => esc_html__( 'Show Author Avatar', 'et_builder' ),
				'type'            => 'yes_no_button',
				'option_category' => 'configuration',
				'options'         => array(
					'on'  => esc_html__( 'Yes', 'et_builder' ),
					'off' => esc_html__( 'No', 'et_builder' ),
				),
				'affects'         => array(
					'author_avatar_size',
					'author_avatar_alignment',
				),
				'toggle_slug'     => 'main_settings',
			),
			'author_avatar_size'      => array(
				'label'           => esc_html__( 'Avatar Size', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'The size in pixels of the avatar. Defaults to 128. Enter a number ONLY. 64 being small, 256 being large.', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
			'author_avatar_alignment' => array(
				'label'           => esc_html__( 'Avatar Alignment', 'et_builder' ),
				'type'            => 'select',
				'options'         => array(
					'alignleft'  => esc_html__( 'Left', 'et_builder' ),
					'alignright' => esc_html__( 'Right', 'et_builder' ),
				),
				'depends_show_if' => 'on',
				'description'     => esc_html__( 'Alignment of the avatar', 'et_builder' ),
				'toggle_slug'     => 'main_settings',
			),
		);

		return $fields;
	}

	function render( $atts, $content = null, $function_name ) {
		//$id    = sb_et_cpt_li_get_id();
		$is_vb = sb_et_cpt_li_is_vb();

		$module_id               = $this->props['module_id'];
		$module_class            = $this->props['module_class'];
		$author_name             = $this->props['author_name'];
		$author_name_before      = $this->props['author_name_before'];
		$author_name_link        = $this->props['author_name_link'];
		$author_name_target      = $this->props['author_name_target'];
		$author_bio              = $this->props['author_bio'];
		$author_website          = $this->props['author_website'];
		$author_website_before   = $this->props['author_website_before'];
		$author_social           = $this->props['author_social'];
		$author_avatar           = $this->props['author_avatar'];
		$author_avatar_size      = $this->props['author_avatar_size'];
		$author_avatar_alignment = $this->props['author_avatar_alignment'];

		$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );

		//////////////////////////////////////////////////////////////////////

		$output = '';

		if ( $is_vb ) {
			$author_id    = get_current_user_id();
			$user_url     = 'https://www.google.co.uk';
			$display_name = 'Your Name Here';

			$user     = wp_get_current_user();
			$nicename = $user->user_nicename;

			$bio_text = 'Author Bio. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed tempus nibh sed elimttis adipiscing. Fusce in hendrerit purus. Suspendisse potenti. Proin quis eros odio, dapibus dictum mauris. Donec nisi libero, adipiscing id pretium eget.';
		} else {
			$author_id    = get_the_author_meta( 'ID' );
			$user_url     = get_the_author_meta( 'url' );
			$display_name = get_the_author_meta( 'display_name' );
			$nicename     = get_the_author_meta( 'user_nicename' );
			$bio_text     = get_the_author_meta( 'description' );
		}

		if ( $author_name == 'on' ) {
			$url = '';
			switch ( $author_name_link ) {
				case 'author_url':
					$url = get_author_posts_url( $author_id, $nicename );
					break;
				case 'user_url':
					$url = $user_url;
					break;
				case 'none':
					$url = '';
					break;
			}

			$output .= '<h3 class="author_archive_title">' . ( $author_name_before ? $author_name_before . ' ' : '' ) . ( $url ? '<a ' . ( $author_name_target == 'blank' ? 'target="_blank"' : '' ) . ' href="' . $url . '">' : '' ) . $display_name . ( $url ? '</a>' : '' ) . '</h3>';
		}

		if ( $author_avatar == 'on' ) {
			if ( ! $author_avatar_size || ! is_numeric( $author_avatar_size ) ) {
				$author_avatar_size = 128;
			}

			if ( $avatar = get_avatar( $author_id, $author_avatar_size, false, false, array( 'class' => $author_avatar_alignment ) ) ) {
				$output .= $avatar;
			}
		}

		if ( $author_bio == 'on' ) {
			$output .= '<p class="sb_et_cpt_li_author_bio">' . $bio_text . '</p>';
		}

		if ( $author_social == 'on' ) {
			if ( $is_vb ) {
				$gp = $tw = $fb = '#';
			} else {
				$fb = get_the_author_meta( 'facebook' );
				$tw = get_the_author_meta( 'twitter' );
				$gp = get_the_author_meta( 'google_plus' );
			}

			if ( $fb || $tw || $gp ) {

				$output .= '<span class="sb_et_cpt_li_author_social">';

				$output .= '<ul class="et-social-icons">';

				if ( 'on' === et_get_option( 'divi_show_facebook_icon', 'on' ) ) {
					if ( $fb ) {
						$output .= '<li class="et-social-icon et-social-facebook">
										<a href="' . esc_url( $url ) . '" class="icon">
											<span>' . esc_html( 'Facebook', 'Divi' ) . '</span>
										</a>
									</li>';
					}
				}
				if ( 'on' === et_get_option( 'divi_show_twitter_icon', 'on' ) ) {
					if ( $tw ) {
						$output .= '<li class="et-social-icon et-social-twitter">
										<a href="' . esc_url( $url ) . '" class="icon">
											<span>' . esc_html( 'Twitter', 'Divi' ) . '</span>
										</a>
									</li>';
					}
				}
				if ( 'on' === et_get_option( 'divi_show_google_icon', 'on' ) ) {
					if ( $gp ) {
						$output .= '<li class="et-social-icon et-social-google-plus">
										<a href="' . esc_url( $url ) . '" class="icon">
											<span>' . esc_html( 'Google', 'Divi' ) . '</span>
										</a>
									</li>';
					}
				}

				$output .= '</ul>';

				$output .= '</span>';
			}
		}

		if ( $author_website == 'on' ) {
			if ( $user_url ) {
				$output .= '<p class="sb_et_cpt_li_author_link">' . ( $author_website_before ? $author_website_before . ' ' : '' ) . '<a href="' . $user_url . '" target="_blank">' . $user_url . '</a></p>';
			}
		}

		//////////////////////////////////////////////////////////////////////

		if ( $output ) {
			$output = '<div ' . ( $module_id ? ' id="' . esc_attr( $module_id ) . '"' : '' ) . ' class="clearfix et_pb_module ' . $module_class . '">' . $output . '</div>';
		}

		return $output;
	}
}

new sb_et_cpt_li_author_bio_module();

?>