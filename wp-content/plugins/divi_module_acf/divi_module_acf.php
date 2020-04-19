<?php

/*
 * Plugin Name: DIVI ACF Module
 * Plugin URI:  http://www.sean-barton.co.uk
 * Description: A plugin to add the ability to use Advanced Custom Fields in it's own module within the layout builder
 * Author:      Sean Barton - Tortoise IT
 * Version:     3.6
 * Author URI:  http://www.sean-barton.co.uk
 *
 *
 * Changelog:
 *
 * V1.9
 * - Initial versions
 *
 * V2.0
 * - Added support for url, email and taxonomy fields
 *
 * V2.1
 * - Abstracted out the field type processing for easier updating
 * - Added better support for multiple taxonomy fields
 *
 * V2.2
 * - Fixed responsive text size/line height issues
 *
 * V2.3
 * - Moved style.css to correct enqueue point
 *
 * V2.4 - 6/2/17
 * - Tested for empty item before pushing single item module out to screen.
 * - Removed php notice relating to an undeclared title
 *
 * V2.5 - 28/2/17
 * - Fixed Image Size optionting to an undeclared title
 *
 * V2.6 - 28/2/17
 * - Added lightbox gallery functionality from Divi to 'gallery' ACF field type (courtesy of a user code submission)
 *
 * V2.7 - 28/2/17
 * - Added support for the Google Maps Field
 *
 * V2.8 - 5/6/17
 * - Fixed default date in acf single module
 * - Repeater sub fields is_array check to avoid php notices in some situations
 *
 * V2.9 - 17/7/17
 * - Fixed file type to include the file name and a link to the file
 * - Fixed cache-buster for the Divi Builder whereby new fields didn't show in the fields list.
 *
 * V3.0 - 7/9/17
 * - Fixed images used in Repeater module
 * - Steamlined each module to use toggle classes so you don't need to scroll past the background settings to get to the editable useful fields
 * - Fixed gallery field layout
 * - Added licensing/auto update to make upgrading between versions easier
 * - Added support for the number field
 * - Added support for User field
 * - Added support for Page Link field
 * - Added support for Relationship field
 * - Added support for Post Object field
 * - Added support for prepend/append
 * - Added support for the Datepicker field
 * - Added setting to allow you to choose whether and where image fields should be linked
 * - Added helper CSS for single items with bullet point lists present
 *
 * V3.1 - 03/11/17
 * - Fixed auto update functionality
 *
 * V3.2 - 08/11/17
 * - Fixed a variety of design settings within ACF table and ACF single item modules
 * - Removed number format from numeric fields as it was removing decimal places.
 *
 * V3.3 - 10/01/18
 * - Fixed the repeater field module which was completely broken :(
 * - Added design options to the repeater field module
 * - Added support for the new 'link' module which is handy for adding your own title and selecting new window etc
 * - Fixed issue with Date field whereby an empty date formatted as 1/1/1970 incorrectly
 *
 * V3.3.1 - 30/11/18
 * - Added button style output to the single module
 *
 * V3.4 - 15/08/19
 * - Added 'multiples format' option to single and table item modules so that any module that would previously output a bullet list can now output CSV or / delimited data for better formatting
 * - converted modules to use updated Divi code in preparation for VB conversion
 * - Added format output option to table item module
 * - Added number format along with decimal places selector to single and table item modules
 * - Added before and after options to single and table item mobules so you can easily add things like currency symbols and labels to your ACF tables for better formatting! Even add shortcodes!
 * - Fixed repeater module not working in certain circumstances
 *
 * V3.5 - 03/09/19
 * - Fixed Updater code
 * - VB Support for all modules!!
 * - Added Title Field to Table module
 *
 * V3.6 - 16/01/20
 * - Fixed issue with taxonomy code whereby when using a CPT injector loop archive the system didn't look at the posts and instead looked at the taxonomy. Now fixed.
 *
 */

//constants
define( 'SB_ET_ACF_VERSION', '3.6' );
define( 'SB_ET_ACF_STORE_URL', 'https://elegantmarketplace.com' );
define( 'SB_ET_ACF_ITEM_NAME', 'Advanced Custom Fields Module' );
define( 'SB_ET_ACF_AUTHOR_NAME', 'Sean Barton' );
define( 'SB_ET_ACF_ITEM_ID', 50270 );
define( 'SB_ET_ACF_FILE', __FILE__ );

require_once( 'includes/emp-licensing.php' );

add_action( 'plugins_loaded', 'sb_mod_acf_init' );

function sb_mod_acf_init() {
	add_action( 'et_builder_ready', 'sb_mod_acf_theme_setup', 9999 );
	add_action( 'admin_head', 'sb_mod_acf_admin_head', 9999 );
	add_action( 'wp_enqueue_scripts', 'sb_mod_acf_enqueue', 9999 );
	add_action( 'admin_menu', 'sb_mod_acf_submenu' );

	add_filter( 'clean_url', 'sb_mod_acf_clean_content', 10, 10 );
	add_filter( 'esc_html', 'sb_mod_acf_clean_content', 10, 10 );
	add_filter( 'acf/fields/google_map/api', 'sb_mod_acf_google_map_api' );
}

function sb_mod_acf_clean_content( $clean_html, $html ) {

	if ( sb_mod_acf_string_contains_shortcodes( $clean_html ) ) {
		$clean_html = sb_mod_acf_the_content_filter( $html );
	}

	return $clean_html;
}

function sb_mod_acf_string_contains_shortcodes( $content ) {
	$found = false;

	if ( strpos( $content, '[' ) !== false ) {
		$found = true;
	}

	return $found;
}

function sb_mod_acf_the_content_filter( $content ) {

	if ( sb_mod_acf_string_contains_shortcodes( $content ) ) { //this may have been done already but it doesn't hurt to check
		$content = do_shortcode( $content ); //should only process the contents of the attribute shortcode in the html string and nothing else.
	}

	return $content;
}

function sb_mod_acf_submenu() {
	add_submenu_page(
		'plugins.php',
		'Divi ACF Module',
		'Divi ACF Module',
		'manage_options',
		'sb_mod_acf',
		'sb_mod_acf_submenu_cb' );
}

function sb_mod_acf_box_start( $title ) {
	return '<div class="postbox">
                    <h2 class="hndle">' . $title . '</h2>
                    <div class="inside">';
}

function sb_mod_acf_box_end() {
	return '    </div>
                </div>';
}

function sb_mod_acf_format_output( $value, $props, $field ) {
	switch ( $props['format_output'] ) {
		case 'autop':
			$value = wpautop( $value );
			break;
		case 'audio':
			$value = do_shortcode( '[audio src="' . $value . '"]' );
			break;
		case 'video':
			$value = do_shortcode( '[video src="' . $value . '"]' );
			break;
		case 'number_format':
			$value = number_format( $value, (int) $props['decimal_places'] );
			break;
		case 'button':
			if ( trim( $value ) ) {
				$value = '<a class="et_pb_button sb_mod_acf_button" href="' . $value . '">' . apply_filters( 'sb_mod_acf_button', ( $props['button_text'] ? $props['button_text'] : $value ), $field ) . '</a>';
			}
			break;
	}

	return $value;
}

function sb_mod_acf_submenu_cb() {

	echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
	echo '<h2>' . SB_ET_ACF_ITEM_NAME . ' - V' . SB_ET_ACF_VERSION . '</h2>';

	echo '<div id="poststuff">';

	echo '<div id="post-body" class="metabox-holder columns-2">';

	echo '<form method="POST">';

	sb_et_acf_license_page();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	echo '</div>';
}

function sb_mod_acf_google_map_api( $api ) {
	$google = get_option( 'et_google_api_settings' );

	if ( $google['api_key'] ) {
		$api['key'] = $google['api_key'];
	}

	return $api;
}

function sb_mod_acf_enqueue() {
	wp_enqueue_style( 'sb_mod_acf_css', plugins_url( '/style.css', __FILE__ ) );
}

function sb_mod_acf_parse_value_by_type( $field, $image_size = 'medium', $repeater = false, $date_format = false, $link_image = 'image', $multiples_format = 'default' ) {
	if ( isset( $field['value'] ) ) {
		$value = $field['value'];
	} else {
		$value = $field;
	}

	if ( $field['type'] == 'number' && $value ) {
		if ( is_numeric( $value ) ) {
			//$value = number_format($value);
		}
	} else if ( $field['type'] == 'file' ) {
		if ( is_array( $value ) && isset( $value['url'] ) && $value['url'] ) {
			$value = '<a target="_blank" href="' . $value['url'] . '" class="sb-divi-acf-table-file-item">' . apply_filters( 'sb_et_mod_acf_label', ( $value['title'] ? $value['title'] : $value['filename'] ), $value, $field ) . '</a>';
		} else if ( $value ) {
			$value = '<a target="_blank" href="' . $value . '" class="sb-divi-acf-table-file-item">' . apply_filters( 'sb_et_mod_acf_label', $value, $value, $field ) . '</a>';
		}
	} else if ( is_array( $value ) && $field['type'] == 'image' ) {
		$prepend = '';
		$append  = '';

		if ( $link_image == 'page' || $link_image == 'image' ) {
			$url = $value['sizes']['large'];

			if ( $link_image == 'page' ) {
				$url = get_permalink( get_the_ID() );
			}

			$prepend = '<a href="' . $url . '" class="sb-divi-acf-table-image-item">';
			$append  = '</a>';
		}

		$value = $prepend . '<img src="' . ( @$value['sizes'][ $image_size ] ? $value['sizes'][ $image_size ] : $value['sizes']['medium'] ) . '" />' . $append;

	} else if ( is_array( $value ) && $field['type'] == 'checkbox' ) {
		$before        = '<li class="acf-value-item">';
		$after         = '</li>';
		$glue          = "\n";
		$container_tag = 'ul';

		if ( $multiples_format == 'comma' ) {
			$before        = '<span class="acf-value-item">';
			$after         = '</span>';
			$glue          = ",&nbsp;";
			$container_tag = 'div';
		} else if ( $multiples_format == 'slash' ) {
			$before        = '<span class="acf-value-item">';
			$after         = '</span>';
			$glue          = "&nbsp;/&nbsp;";
			$container_tag = 'div';
		}

		foreach ( $value as &$val ) {
			$val = $before . $val . $after;
		}

		$value = '<' . $container_tag . ' class="sb-acf-field-checkboxes">' . implode( $glue, $value ) . '</' . $container_tag . '>';

	} else if ( is_array( $value ) && $field['type'] == 'select' ) {
		$before        = '<li class="acf-value-item">';
		$after         = '</li>';
		$glue          = "\n";
		$container_tag = 'ul';

		if ( $multiples_format == 'comma' ) {
			$before        = '<span class="acf-value-item">';
			$after         = '</span>';
			$glue          = ",&nbsp;";
			$container_tag = 'div';
		} else if ( $multiples_format == 'slash' ) {
			$before        = '<span class="acf-value-item">';
			$after         = '</span>';
			$glue          = "&nbsp;/&nbsp;";
			$container_tag = 'div';
		}

		foreach ( $value as &$val ) {
			$val = $before . $val . $after;
		}

		$value = '<' . $container_tag . ' class="sb-acf-field-select">' . implode( $glue, $value ) . '</' . $container_tag . '>';

	} else if ( is_array( $value ) && $field['type'] == 'gallery' ) {
		$value_cache = $value;
		$value       = '';

		$value .= '<div class="et_pb_gallery_grid" style="display: block;">';
		$value .= '<div class="et_pb_gallery_items et_post_gallery">';

		foreach ( $value_cache as $val ) {
			$value .= '<div class="et_pb_gallery_item et_pb_grid_item et_pb_bg_layout_light" style="display: block;">';
			$value .= '<div class="et_pb_gallery_image landscape">';
			$value .= '<a href="' . $val['sizes']['large'] . '">';
			$value .= '<img src="' . ( @$val['sizes'][ $image_size ] ? $val['sizes'][ $image_size ] : $val['sizes']['large'] ) . '" data-lazy-loaded="true" style="display: inline;">';
			$value .= '<span class="et_overlay et_pb_inline_icon" data-icon="T"></span>';
			$value .= '</a>';
			$value .= '</div>';
			$value .= '</div>';
		}

		$value .= '</div>';
		$value .= '</div>';

		//} else if ($field['type'] == 'date_picker' || $field['type'] == 'date_time_picker') {
	} else if ( $field['type'] == 'date_picker' ) {
		//echo '<pre>';
		//print_r($value);
		//print_r($field);
		//echo '</pre>';
		if ( $value ) {
			$value = strtotime( $value );
			if ( ! $date_format ) {
				$date_format = $field['display_format'];
			}

			$value = date( $date_format, $value );
		}
	} else if ( $field['type'] == 'url' ) {
		$value = '<a href="' . $field['value'] . '" target="_blank">' . $field['value'] . '</a>';

	} else if ( $field['type'] == 'email' ) {
		$value = '<a href="mailto:' . $field['value'] . '">' . $field['value'] . '</a>';

	} else if ( $field['type'] == 'google_map' ) {
		$google = get_option( 'et_google_api_settings' );

		//print_r($field);
		$rand = mt_rand( 1000, 9999 );

		if ( $google['api_key'] ) {

			$zoom = ( $field['zoom'] ? $field['zoom'] : 16 );

			$value = '<script src="https://maps.googleapis.com/maps/api/js?key=' . $google['api_key'] . '"></script>
										<script type="text/javascript">
										(function($) {
										
												function et_acf_new_map( $el ) {
														var $markers = $el.find(".marker");
														
														var args = {
																zoom		: ' . $zoom . ',
																center		: new google.maps.LatLng(0, 0),
																mapTypeId	: google.maps.MapTypeId.ROADMAP,
																scrollwheel : false
														};
														
														var map = new google.maps.Map( $el[0], args);
														
														map.markers = [];
														
														$markers.each(function(){
																et_acf_add_marker( $(this), map );
														});
														
														et_acf_center_map( map );
														
														return map;
												}
										
												function et_acf_add_marker( $marker, map ) {
														var latlng = new google.maps.LatLng( $marker.attr("data-lat"), $marker.attr("data-lng") );
													
														var marker = new google.maps.Marker({
																position	: latlng,
																map			: map
														});
													
														map.markers.push( marker );
													
														if( $marker.html() ) {
																var infowindow = new google.maps.InfoWindow({
																		content		: $marker.html()
																});
														
																google.maps.event.addListener(marker, "click", function() {
																		infowindow.open( map, marker );
																});
														}
												}
										
												function et_acf_center_map( map ) {
														var bounds = new google.maps.LatLngBounds();
													
														jQuery.each( map.markers, function( i, marker ){
																var latlng = new google.maps.LatLng( marker.position.lat(), marker.position.lng() );
																bounds.extend( latlng );
														});
													
														if ( map.markers.length == 1 ) {
																map.setCenter( bounds.getCenter() );
																map.setZoom( ' . $zoom . ' );
														} else {
																map.fitBounds( bounds );
														}
												}
												
												var map = null;
												
												jQuery(document).ready(function($){
														jQuery(".et_pb_acf_map_' . $rand . '").each(function(){
																map = et_acf_new_map( jQuery(this) );
														});
												});
										
										})(jQuery);
										</script>';

			if ( ! empty( $field['value'] ) ) {
				$value .= '<div class="et_pb_acf_map et_pb_acf_map_' . $rand . '" ' . ( $field['height'] ? ' style="height: ' . $field['height'] . 'px;"' : '' ) . '>
												<div class="marker" data-lat="' . $field['value']['lat'] . '" data-lng="' . $field['value']['lng'] . '"></div>
										</div>';
			}
		} else {
			$value .= '<p style="color red;">Please enter a Google API Key in your Divi Settings</p>';
		}
	} else if ( $field['type'] == 'post_object' ) {
		if ( $post = get_post( $value ) ) {
			$value = '<a href="' . get_post_permalink( $value ) . '" target="_blank">' . apply_filters( 'the_title', $post->post_title ) . '</a>';
		}
	} else if ( $field['type'] == 'user' ) {
		$value = '<a href="' . get_author_posts_url( $value['ID'], $value['user_nicename'] ) . '" target="_blank">' . $value['display_name'] . '</a>';
	} else if ( $field['type'] == 'link' ) {

		if ( is_array( $value ) ) {
			$value = '<a href="' . $value['url'] . '" ' . ( isset( $value['target'] ) && $value['target'] ? 'target="' . $value['target'] . '"' : '' ) . '>' . ( $value['title'] ? $value['title'] : $value['url'] ) . '</a>';
		} else {
			$value = '<a href="' . $value . '" >' . $value . '</a>';
		}
		//$value = print_r($field, true) . print_r($value, true);

	} else if ( $field['type'] == 'page_link' ) {
		if ( $post = get_post( $value ) ) {
			$value = '<a href="' . get_post_permalink( $value ) . '" target="_blank">' . apply_filters( 'the_title', $post->post_title ) . '</a>';
		} else {
			$value = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
		}
	} else if ( $field['type'] == 'relationship' ) {
		$value = '';
		if ( ! empty( $field['value'] ) ) {
			foreach ( $field['value'] as $val ) {
				if ( $post = get_post( $val ) ) {
					$value .= '<li><a href="' . get_post_permalink( $val ) . '" target="_blank">' . apply_filters( 'the_title', $post->post_title ) . '</a></li>';
				}
			}

			if ( $value ) {
				$value = '<ul class="sb-acf-field-checkboxes">' . $value . '</ul>';
			}
		}
	} else if ( $field['type'] == 'taxonomy' ) {
		$ACF_t = $field['taxonomy'];

		if ( is_array( $field['value'] ) ) { // multiple values selected
			$value = '';

			foreach ( $field['value'] as $v ) {
				$ACF_taxonomy = get_term_by( 'id', $v, $ACF_t );
				$value        .= $ACF_taxonomy->name . apply_filters( 'sb_et_mod_acf_tax_divider', '<br />' );
			}

			$value = trim( $value, apply_filters( 'sb_et_mod_acf_tax_divider', '<br />' ) );

		} else {                                     // single value selected
			$ACF_tv       = (int) $field['value'];
			$ACF_taxonomy = get_term_by( 'id', $ACF_tv, $ACF_t );
			$value        = $ACF_taxonomy->name;

		}

	} else if ( ! is_array( $value ) ) {
		$value = ( do_shortcode( $value ) );

	}

	if ( isset( $field['prepend'] ) ) {
		$value = $field['prepend'] . $value;
	}
	if ( isset( $field['append'] ) ) {
		$value .= $field['append'];
	}

	if ( ! is_array( $value ) && ! strip_tags( $value ) ) {
		$value = apply_filters( 'sb_et_mod_acf_field_fallback', $value, $field );
		$value = apply_filters( 'sb_et_mod_acf_field_fallback_' . $field['name'], $value, $field );
	}

	$value = apply_filters( 'sb_et_mod_acf_field_parse', $value, $field );

	return $value;
}

function sb_mod_acf_admin_head() {

	if ( isset( $_GET['post'] ) || isset( $_GET['post_type'] ) || isset( $_GET['sb_purge_cache'] ) ) {
		$prop_to_remove = array(
			'et_pb_templates_et_pb_acf_single_item'
		,
			'et_pb_templates_et_pb_acf_table_item'
		,
			'et_pb_templates_et_pb_acf_table_items'
		,
			'et_pb_templates_et_pb_acf_repeater_table'
		);

		$js_prop_to_remove = 'var sb_ls_remove = ["' . implode( '","', $prop_to_remove ) . '"];';

		echo '<script>
	    
	    ' . $js_prop_to_remove . '
	    
	    for (var prop in localStorage) {
            if (sb_ls_remove.indexOf(prop) != -1) {
                localStorage.removeItem(prop);
            }
	    }
	    
	    </script>';
	}
}

function sb_mod_acf_theme_setup() {

	if ( class_exists( 'ET_Builder_Module' ) ) {
		require_once( 'modules/et_pb_acf_table.php' );
		require_once( 'modules/et_pb_acf_table_item.php' );
		require_once( 'modules/et_pb_acf_single.php' );
		require_once( 'modules/et_pb_acf_repeater_table.php' );
	}
}

function sb_mod_acf_get_id() {
	if ( ! $return = get_the_ID() ) {
		if ( isset( $_POST['options'] ) ) {
			if ( isset( $_POST['options']['current_page']['id'] ) ) {
				$return = $_POST['options']['current_page']['id']; //for the vb
			}
		}
	}

	return $return;
}

function sb_mod_acf_is_vb() {
	$return = false;
	if ( isset( $_POST['options'] ) ) {
		if ( isset( $_POST['options']['current_page']['id'] ) ) {
			$return = true;
		}
	}

	return $return;
}

function sb_mod_acf_get_fields( $repeater_only = false, $include_empty = false ) {
	$options = array();

	if ( $include_empty ) {
		$options[] = 'Please Select A Field';
	}

	if ( $acf_posts = get_posts( array( 'post_type' => 'acf', 'posts_per_page' => - 1 ) ) ) {
		foreach ( $acf_posts as $acf_post ) {

			$acf_meta   = get_post_custom( $acf_post->ID );
			$acf_fields = array();

			foreach ( $acf_meta as $key => $val ) {
				if ( preg_match( "/^field_/", $key ) ) {
					$acf_fields[ $key ] = $val;
				}
			}

			if ( $acf_fields ) {
				foreach ( $acf_fields as $field ) {
					$field = unserialize( $field[0] );

					if ( ! $repeater_only || $repeater_only && $field['type'] == 'repeater' ) {
						$options[ $acf_post->post_title . '|' . $field['name'] ] = $acf_post->post_title . ' - ' . $field['label'];
					}
				}
			}
		}
	}

	if ( $acf_pro_groups = get_posts( array( 'post_type' => 'acf-field-group', 'posts_per_page' => - 1 ) ) ) {
		foreach ( $acf_pro_groups as $acf_fg ) {

			if ( $fields = get_posts( array(
				'post_type'      => 'acf-field',
				'post_parent'    => $acf_fg->ID,
				'posts_per_page' => - 1
			) ) ) {
				foreach ( $fields as $field ) {
					$field_obj = unserialize( $field->post_content );

					if ( ! $repeater_only || $repeater_only && $field_obj['type'] == 'repeater' ) {
						$options[ $acf_fg->post_title . '|' . $field->post_excerpt ] = $acf_fg->post_title . ' - ' . $field->post_title;
					}
				}
			}
		}
	}

	return $options;
}

?>
