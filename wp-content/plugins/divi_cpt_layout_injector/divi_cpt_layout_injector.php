<?php

/*
 * Plugin Name: CPT Layout Injector
 * Plugin URI:  http://www.sean-barton.co.uk
 * Description: A plugin to handle the layouts of any Custom Post Type pages using the ET layout builder system. 
 * Author:      Sean Barton - Tortoise IT
 * Version:     4.9.1
 * Author URI:  http://www.sean-barton.co.uk
 */

require_once( 'licensing/emp-licensing.php' );

if ( isset( $_GET['debug'] ) ) {
	ini_set( 'display_errors', 1 );
	error_reporting( E_ALL );
}

add_action( 'plugins_loaded', 'sb_et_cpt_li_init' );

//constants
define( 'SB_ET_CPT_LI_VERSION', '4.9.1' );
define( 'SB_ET_CPT_LI_STORE_URL', 'https://elegantmarketplace.com' );
define( 'SB_ET_CPT_LI_AUTHOR_URL', 'https://www.sean-barton.com' );
define( 'SB_ET_CPT_LI_ITEM_NAME', 'CPT Layout Injector' );
define( 'SB_ET_CPT_LI_AUTHOR_NAME', 'Sean Barton' );
define( 'SB_ET_CPT_LI_ITEM_ID', 50271 );
define( 'SB_ET_CPT_LI_FILE', __FILE__ );
define( 'SB_ET_CPT_LI_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'SB_ET_CPT_LI_IMG_URL', SB_ET_CPT_LI_URL . 'images/' );

function sb_et_cpt_li_initialize_extension() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/cpt_layout_lnjector.extension.php';
}

function sb_et_cpt_li_init() {

	add_action( 'divi_extensions_init', 'sb_et_cpt_li_initialize_extension' );
	//add_action( 'et_builder_ready', 'sb_et_cpt_li_theme_setup', 9999 ); //removed in favour of the above

	add_action( 'admin_menu', 'sb_et_cpt_li_submenu' );
	add_action( 'admin_head', 'sb_et_cpt_li_admin_head', 9999 );
	add_action( 'wp_enqueue_scripts', 'sb_et_cpt_li_enqueue', 9999 );
	add_action( 'admin_enqueue_scripts', 'sb_et_cpt_li_enqueue', 9999 );

	add_filter( 'clean_url', 'sb_et_cpt_li_clean_content', 10, 10 );
	add_filter( 'esc_html', 'sb_et_cpt_li_clean_content', 10, 10 );
	add_filter( 'sb_et_cpt_li_single_template_filter', 'sb_et_cpt_li_the_content_filter' );
	add_filter( 'admin_footer_text', '__return_empty_string' );
	add_filter( 'update_footer', '__return_empty_string' );
	add_filter( 'template_include', 'sb_et_cpt_li_template', 99 );
	add_filter( 'user_contactmethods', 'sb_et_cpt_li_contactmethods', 10, 1 );
	add_filter( 'pre_get_document_title', 'sb_et_cpt_li_archive_titles', 100, 100 );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sb_et_cpt_li_action_links' );

	add_image_size( 'sb_cpt_li_150_square', 150, 150, true );
	add_image_size( 'sb_cpt_li_250_square', 250, 250, true );
	add_image_size( 'sb_cpt_li_350_square', 350, 350, true );
	add_image_size( 'sb_cpt_li_150_wide', 150, false, true );
	add_image_size( 'sb_cpt_li_250_wide', 250, false, true );
	add_image_size( 'sb_cpt_li_350_wide', 350, false, true );
}

function sb_et_cpt_li_is_vb() {
	$return = false;

	if ( isset( $_GET['et_fb'] ) ) {
		$return = true;
	} else if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'et_fb_ajax_render_shortcode' ) {
		$return = true;
	}

	return $return;
}

$sb_et_cpt_li_in_loop = false;

function sb_et_cpt_li_get_id() {
	global $sb_et_cpt_li_in_loop;

	if ( isset( $_GET['et_fb'] ) && ! $sb_et_cpt_li_in_loop ) {
		$id = false;
	} else if ( ! $sb_et_cpt_li_in_loop && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'et_fb_ajax_render_shortcode' ) {
		if ( isset( $_POST['options']['current_page']['id'] ) && $_POST['options']['current_page']['id'] ) {
			$id = $_POST['options']['current_page']['id'];

			if (get_post_type($id) == 'et_pb_layout') {
				$id = false;
			}
		}
	} else {
		$id = get_the_ID();
	}

	return $id;
}

function sb_et_cpt_li_clean_content( $good_protocol_url, $original_url ) {

	if ( sb_et_cpt_li_string_contains_shortcodes( $original_url ) ) {
		$good_protocol_url = sb_et_cpt_li_the_content_filter( $original_url );
	}

	return $good_protocol_url;
}

function sb_et_cpt_li_string_contains_shortcodes( $content ) {
	$found = false;

	if ( strpos( $content, '{!{' ) !== false ) {
		$found = true;
	} else if ( strpos( $content, '[' ) !== false ) {
		$found = true;
	}

	return $found;
}

function sb_et_cpt_li_the_content_filter( $content ) {

	if ( sb_et_cpt_li_string_contains_shortcodes( $content ) ) { //this may have been done already but it doesn't hurt to check
		//toolset conversion
		$content = str_replace( '{!{', '[', $content );
		$content = str_replace( '}!}', ']', $content );

		$content = do_shortcode( $content ); //should only process the contents of the attribute shortcode in the html string and nothing else.
	}

	return $content;
}

function sb_et_cpt_li_archive_titles( $title ) {

	if ( is_archive() ) {
		$type = get_post_type();
		if ( $cpt_title = get_option( 'sb_et_cpt_li_' . $type . '_archive_title', '' ) ) {
			$title = $cpt_title;
		}
	}

	return $title;
}

function sb_et_cpt_li_contactmethods( $contactmethods ) {
	$contactmethods['twitter']     = 'Twitter';
	$contactmethods['facebook']    = 'Facebook';
	$contactmethods['google_plus'] = 'Google+';

	return $contactmethods;
}

function sb_et_cpt_li_enqueue() {
	wp_enqueue_style( 'sb_et_cpt_li_css', plugins_url( 'styles/style.css', __FILE__ ) );
}

function sb_et_cpt_li_admin_head() {

	$post_types  = md5( serialize( get_post_types() ) );
	$purge_cache = get_option( 'sb_et_cpt_li_builder_purge_cache', '' );

	if ( ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'et_pb_layout' ) || stripos( $_SERVER['PHP_SELF'], 'wp-admin/index.php' ) !== false || $post_types != $purge_cache || isset( $_GET['sb_purge_cache'] ) ) {

		update_option( 'sb_et_cpt_li_builder_purge_cache', $post_types ); //update purge cache

		$prop_to_remove = array(
			'et_pb_templates_et_pb_cpt_archive'
		,
			'et_pb_templates_et_pb_cpt_loop_archive'
		,
			'et_pb_templates_et_pb_cpt_text'
		,
			'et_pb_templates_et_pb_cpt_author_bio'
		,
			'et_pb_templates_et_pb_cpt_taxonomy'
		,
			'et_pb_templates_et_pb_cpt_featured_image2'
		,
			'et_pb_templates_et_pb_cpt_title'
		,
			'et_pb_templates_et_pb_cpt_postnav'
		);

		$js_prop_to_remove = 'var sb_ls_remove = ["' . implode( '","', $prop_to_remove ) . '"];';

		echo '<script>
            
            ' . $js_prop_to_remove . '
            
            for (var prop in localStorage) {
                if (sb_ls_remove.indexOf(prop) != -1) {
                    console.log(localStorage.removeItem(prop));
                }
            }
            
            </script>';
	}
}

function sb_et_cpt_li_template( $template ) {

	$orig_template = $template;

	if ( function_exists( 'et_pb_is_pagebuilder_used' ) ) {
		$pt          = sb_et_cpt_li_get_post_type();
		$page_layout = '';

		$id = get_the_ID();

		if ( is_front_page() ) {
			$id = get_option( 'page_on_front' );
		}

		if ( apply_filters( 'sb_et_cpt_li_can_view', true, $id, $pt ) ) {


			if ( is_single() || is_page() || is_front_page() ) {
				$is_page_builder_used = et_pb_is_pagebuilder_used( $id );

				if ( $is_page_builder_used ) {
					return $template;
				}

				$meta_key    = 'sb_et_cpt_li_' . $pt . '_layout';
				$page_layout = get_option( $meta_key );

				if ( $tax_overrides = get_option( 'sb_et_cpt_li_' . $pt . '_taxonomies' ) ) {
					foreach ( $tax_overrides as $taxonomy => $overrides ) {
						if ( $objects = wp_get_object_terms( $id, $taxonomy ) ) {
							foreach ( $objects as $object ) {
								if ( isset( $overrides[ $object->slug ] ) && $overrides[ $object->slug ] ) {
									if ( get_post_status( $overrides[ $object->slug ] ) == 'publish' ) { //we do it again here as we can then fall back to the post id
										$page_layout = $overrides[ $object->slug ];
									}
								}
							}
						}
					}
				}

			} else if ( is_post_type_archive( get_post_types( '', 'names' ) ) ) {
				$meta_key    = 'sb_et_cpt_li_' . $pt . '_archive_layout';
				$page_layout = get_option( $meta_key );
			} else if ( is_home() ) {
				$meta_key    = 'sb_et_cpt_li_' . $pt . '_archive_layout';
				$page_layout = get_option( $meta_key );
			}

			if ( $page_layout ) {
				if ( get_post_status( $page_layout ) == 'publish' ) {
					$template = dirname( __FILE__ ) . '/empty.php'; //calls our own file which is a wrapper on a simple shortcode call
				}
			}

			$template = apply_filters( 'sb_et_cpt_li_template', $template, $orig_template, $id, $pt );
		}
	}

	return $template;
}

function sb_et_cpt_li_get_post_type() {

	if ( is_single() || is_singular() ) {
		$post_type = get_post_type();
	} else if ( get_query_var( 'post_type' ) ) {
		$post_type = get_query_var( 'post_type' );
	} else {
		$post_type = 'post';
	}

	return $post_type;
}

function sb_et_cpt_li_single_template() {
	get_header();

	$pt = sb_et_cpt_li_get_post_type();

	if ( is_single() || is_page() ) {
		$meta_key    = 'sb_et_cpt_li_' . $pt . '_layout';
		$page_layout = get_option( $meta_key );

		if ( $tax_overrides = get_option( 'sb_et_cpt_li_' . $pt . '_taxonomies' ) ) {
			foreach ( $tax_overrides as $taxonomy => $overrides ) {
				if ( $objects = wp_get_object_terms( get_the_ID(), $taxonomy ) ) {
					foreach ( $objects as $object ) {
						if ( isset( $overrides[ $object->slug ] ) && $overrides[ $object->slug ] ) {
							$page_layout = $overrides[ $object->slug ];
						}
					}
				}
			}
		}

	} else if ( is_archive() ) {
		$meta_key    = 'sb_et_cpt_li_' . $pt . '_archive_layout';
		$page_layout = get_option( $meta_key );
	} else if ( is_home() ) {
		$meta_key    = 'sb_et_cpt_li_' . $pt . '_archive_layout';
		$page_layout = get_option( $meta_key );
	}

	if ( $page_layout ) {
		if ( $section = do_shortcode( '[et_pb_section global_module="' . $page_layout . '"][/et_pb_section]' ) ) {
			$section = apply_filters( 'sb_et_cpt_li_single_template_filter', $section, get_the_ID(), $page_layout ); //mainly for toolset

			echo '<div id="main-content" class="entry-content">';
			echo do_shortcode( $section );
			echo '</div>';
		}
	}

	get_footer();
}

function sb_et_cpt_li_submenu() {
	add_submenu_page(
		'options-general.php',
		'CPT Layout Injector',
		'CPT Layout Injector',
		'manage_options',
		'sb_et_cpt_li',
		'sb_et_cpt_li_submenu_cb' );
}

function sb_et_cpt_li_box_start( $title ) {
	return '<div class="postbox">
                    <h2 class="hndle">' . $title . '</h2>
                    <div class="inside">';
}

function sb_et_cpt_li_box_end() {
	return '    <div style="display: table; clear: both;">&nbsp;</div></div>
                </div>';
}

function sb_et_cpt_li_submenu_cb() {

	echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
	echo '<h2>CPT Layout Injector - V' . SB_ET_CPT_LI_VERSION . '</h2>';

	echo '<div id="poststuff">';

	echo '<div id="post-body" class="metabox-holder columns-2">';

	$ignored_types = array( 'attachment', 'product', 'download' );

	$types = get_post_types();

	if ( isset( $_POST['sb_et_cpt_li_edit_submit'] ) ) {
		foreach ( $types as $type ) {
			$type_obj = get_post_type_object( $type );

			if ( ! $type_obj->public ) {
				continue;
			}
			if ( in_array( $type, $ignored_types ) ) {
				continue;
			}

			update_option( 'sb_et_cpt_li_' . $type . '_layout', @$_POST[ 'sb_et_cpt_li_' . $type . '_layout' ] );
			update_option( 'sb_et_cpt_li_' . $type . '_taxonomies', @$_POST[ 'sb_et_cpt_li_' . $type . '_taxonomies' ] ); //taxonomy layouts

			if ( isset( $_POST[ 'sb_et_cpt_li_' . $type . '_archive_layout' ] ) ) {
				update_option( 'sb_et_cpt_li_' . $type . '_archive_layout', @$_POST[ 'sb_et_cpt_li_' . $type . '_archive_layout' ] );
			}

			if ( isset( $_POST[ 'sb_et_cpt_li_' . $type . '_archive_title' ] ) ) {
				update_option( 'sb_et_cpt_li_' . $type . '_archive_title', @$_POST[ 'sb_et_cpt_li_' . $type . '_archive_title' ] );
			}
		}

		echo '<div id="message" class="updated fade"><p>Layouts edited successfully</p></div>';
	}
	echo '<form method="POST">';

	sb_et_cpt_li_license_page();

	$layout_query = array(
		'post_type'      => 'et_pb_layout'
	,
		'posts_per_page' => - 1
	,
		'meta_query'     => array(
			array(
				'key'     => '_et_pb_predefined_layout',
				'compare' => 'NOT EXISTS',
			),
		)
	);

	echo sb_et_cpt_li_box_start( 'CPT Layout Injector' );

	echo '<div class="alignright"><iframe width="300" height="169" src="https://www.youtube.com/embed/hMMWhi2sIlI?rel=0" frameborder="0" allowfullscreen></iframe></div>';

	echo '<p>This plugin allows you to edit the layouts of your Divi site without having to edit any core files.</p>';
	echo '<p>A layout can be built within the Divi/Extra library using the page builder. You can then use these settings to set the appropriate layout to the Custom Post Type page(s). You\'ll need to include a variety of new modules in the page builder to make the page work. This plugin will do the rest!</p><p>If you need any support for this plugin please visit the documentation website at <a href="http://docs.tortoise-it.co.uk/cpt-layout-injector/" target="_blank">docs.tortoise-it.co.uk/cpt-layout-injector/</a>.</p><p>The following video will explain the loop archive module which is integral to this plugin.</p>';

	echo sb_et_cpt_li_box_end();

	echo '<div style="display: inline-block; margin-right: 10px; margin-bottom: 20px; padding: 5px 0px;">Jump to:</div>';

	foreach ( $types as $type2 ) {
		$type_obj2 = get_post_type_object( $type2 );

		if ( ! $type_obj2->public ) {
			continue;
		}
		if ( in_array( $type2, $ignored_types ) ) {
			continue;
		}

		$type_name2 = $type_obj2->labels->name;

		echo '<div style="display: inline-block; margin-right: 10px; margin-bottom: 20px; padding: 5px 10px; background: #333"><a style="color: white; text-decoration: none; " href="#' . $type2 . '">' . $type_name2 . '</a></div>';
	}

	if ( $layouts = get_posts( $layout_query ) ) {

		foreach ( $types as $type ) {

			$type_obj = get_post_type_object( $type );

			if ( ! $type_obj->public ) {
				continue;
			}
			if ( in_array( $type, $ignored_types ) ) {
				continue;
			}

			echo '<p id="submit"><input type="submit" name="sb_et_cpt_li_edit_submit" class="button-primary" value="Save Settings" /></p>';

			$type_name = $type_obj->labels->name;

			echo '<a name="' . $type . '"></a>';
			echo sb_et_cpt_li_box_start( $type_name );

			echo '<label style="display:inline-block; min-width: 200px;">Single Template: </label><select name="sb_et_cpt_li_' . $type . '_layout">';

			$cpt_layout = get_option( 'sb_et_cpt_li_' . $type . '_layout' );

			echo '<option value="">-- None --</option>';

			$selected = '';
			foreach ( $layouts as $layout ) {
				if ( $cpt_layout == $layout->ID ) {
					$selected = $layout->post_title;
				}
				echo '<option ' . selected( $layout->ID, $cpt_layout, false ) . ' value="' . $layout->ID . '">' . $layout->post_title . '</option>';
			}

			echo '</select>';

			if ( $selected ) {
				echo '&nbsp; <a class="button-secondary" target="_blank" href="' . admin_url( 'post.php?post=' . $cpt_layout . '&action=edit' ) . '">Click to edit "' . $selected . '"</a>';
			}

			echo '<br />';
			if ( $type_obj->has_archive || $type == 'post' ) {
				$url = get_post_type_archive_link( $type );

				////////////////////////////////////////

				$taxonomy_objects = get_object_taxonomies( $type, 'objects' );
				//echo '<pre>';
				//print_r($taxonomy_objects);
				//echo '</pre>';

				$this_layout = get_option( 'sb_et_cpt_li_' . $type . '_taxonomies' );

				//echo '<pre>';
				//print_r($this_layout);
				//echo '</pre>';

				if ( $taxonomy_objects ) {
					echo '<p>This above layout will appear everywhere that the page builder is not used but you can override by taxonomy term. For example you may wish for some posts to use a different layout based on the category they are in. Using the boxes below allows you to do that.</p>';

					foreach ( $taxonomy_objects as $taxonomy => $tax_obj ) {

						if ( $taxonomy == 'post_format' ) {
							continue;
						}

						$terms = get_terms( $taxonomy, array(
							'hide_empty' => false,
						) );

						if ( $terms ) {

							echo '<p><strong><a style="cursor: pointer;" class="button-secondary" onclick="jQuery(\'.single_' . $type . '_' . $taxonomy . '_layout\').slideToggle();">Toggle ' . $tax_obj->label . '</a></strong></p>';

							echo '<div class="single_' . $type . '_' . $taxonomy . '_layout" style="display: none;">';

							echo '<h2>' . $tax_obj->label . '</h2>';

							if ( count( $terms ) <= 100 ) {

								echo '<table style="width: 100%;" class="widefat">';

								foreach ( $terms as $term ) {

									echo '<tr><td style="width: 30%;">' . $term->name . '</td><td>';
									echo '<select style="width: 250px;" name="sb_et_cpt_li_' . $type . '_taxonomies[' . $taxonomy . '][' . $term->slug . ']">';

									echo '<option value="">-- Default --</option>';

									$selected = '';
									foreach ( $layouts as $layout ) {
										if ( @$this_layout[ $taxonomy ][ $term->slug ] == $layout->ID ) {
											$selected = $layout->post_title;
										}
										echo '<option ' . selected( $layout->ID, @$this_layout[ $taxonomy ][ $term->slug ], false ) . ' value="' . $layout->ID . '">' . $layout->post_title . '</option>';
									}

									echo '</select>';

									if ( $selected ) {
										echo '&nbsp; <a class="button-secondary" target="_blank" href="' . admin_url( 'post.php?post=' . $this_layout[ $taxonomy ][ $term->slug ] . '&action=edit' ) . '">Click to edit "' . $selected . '"</a>';
									}

									echo '</td></tr>';

								}

								echo '</table>';

							} else {
								echo '<p>Here you would ordinarily be able to override each category at will but because you have more than 100 it has been known to cause issues with the page load. Therefore this functionality has been disabled. If you would like to use this feature please temporarily delete some categories to below the 100 limit.</p>';
							}

							echo '</div>';

						}
					}
				}

				////////////////////////////////////////

				echo '<hr />';

				if ( $type == 'post' ) {
					echo '<p>Divi doesn\'t have an archive page in the conventional sense. Normally you would simply visit the posts page and see a list of blogs however, using Divi, we are encouraged to use the "Blog" module to make a grid or list page containing the latest items. I agree with this and, as such, encourage you to create a "Page" called "Blog" or similar and use the "CPT Loop Archive" Module. The only difference is that you need to select "Yes" for "Custom Query" and "Post" as the "Post Type". Pages 2 onwards will use the same template. If you want to style your category/tag pages you\'ll need the <a href="https://elegantmarketplace.com/downloads/taxonomy-layout-injector/" target="_blank">Taxonomy Layout Injector</a> plugin</p>';

					if ( $page_for_posts = get_option( 'page_for_posts' ) ) {
						echo '<p><strong>BUT... you can add one if you want! See below</strong></p>';
						echo '<p>Use the notes from the big paragraph above to show you how to create the layout.</p>';
					} else {
						echo '<p><strong>BUT... WordPress allows you to have an archive of posts which means that certain other plugins such as the calendar and archive widgets will link to the correct places. Set it up as follows:</strong></p>';
						echo '<p>Create a new WP Page for your blog archive. You don\'t need to style it, it just needs to be created for the URL. Save it and then visit the WP <a target="_blank" href="' . admin_url( 'options-reading.php' ) . '">Reading Settings</a> page and set the "Posts Page" to the page you just created. Then revisit this page and you can then set the Divi layout for the blog archive as normal using the Loop Archive module or any combination you like.</p>';
					}
				} else {
					echo '<p>This post type has the ability to show an archive page (a list of the items on the same page.. like the blog page for instance). Use the settings below to configure those pages.</p>';
				}

				echo '<label style="display:inline-block; min-width: 200px;">Archive Template: </label><select name="sb_et_cpt_li_' . $type . '_archive_layout">';

				$cpt_archive_layout = get_option( 'sb_et_cpt_li_' . $type . '_archive_layout' );

				echo '<option value="">-- None --</option>';

				$selected = '';
				foreach ( $layouts as $layout ) {
					if ( $cpt_archive_layout == $layout->ID ) {
						$selected = $layout->post_title;
					}
					echo '<option ' . selected( $layout->ID, $cpt_archive_layout, false ) . ' value="' . $layout->ID . '">' . $layout->post_title . '</option>';
				}

				echo '</select>';

				if ( $selected ) {
					echo '&nbsp; <a class="button-secondary" target="_blank" href="' . admin_url( 'post.php?post=' . $cpt_archive_layout . '&action=edit' ) . '">Click to edit "' . $selected . '"</a>';
				}

				if ( $type == 'post' ) {
					if ( $page_for_posts = get_option( 'page_for_posts' ) ) {
						$permalink = get_permalink( $page_for_posts );
						echo '<p><a target="_blank" class="button-secondary" href="' . $permalink . '"><strong>View Post Archive</strong></a> <small>(' . $permalink . ')</small></p>';
					}
				} else {
					$cpt_archive_title = get_option( 'sb_et_cpt_li_' . $type . '_archive_title', '' );
					echo '<br /><label style="display:inline-block; min-width: 200px;">Archive HTML Title Tag: </label><input type="text" name="sb_et_cpt_li_' . $type . '_archive_title" value="' . $cpt_archive_title . '" /> <small>(optional)</small>';
					echo '<p><a target="_blank" class="button-secondary" href="' . $url . '"><strong>View Archive</strong></a> <small>(' . $url . ')</small></p>';
				}
			} else {
				if ( $type == 'page' ) {
					echo '<p>There is no real reason to have an archive of Pages. If you feel you need one please contact me at <a href="https://www.sean-barton.co.uk">sean-barton.co.uk</a> and I would be more than happy to help you structure your site and use the appropriate data type(s)</p>';
				} else {
					echo '<p>This post type has has_archive set to false meaning you can see the single post type item pages but there is no concept of an archive or view showing a group of items at once. This is not recommended. Once you have turned on has_archive then you will be able to inject a layout using this page.</p>';
				}
			}

			echo sb_et_cpt_li_box_end();

		}

		echo '<p id="submit"><input type="submit" name="sb_et_cpt_li_edit_submit" class="button-primary" value="Save Settings" /></p>';
	} else {
		echo '<div style="padding:100px; border: 2px solid #999; text-align: center;">
                    <h1>Oops no layouts!</h1>
                    <p style="font-size: 16px;">Please visit the Divi Library to add your first layout and then this page will become available</p>
                    <p><a href="' . ( admin_url( '/edit.php?post_type=et_pb_layout' ) ) . '" style="display: inline-block; padding: 10px 30px; border: 1px solid #999; font-size: 16px; background-color: #333; color: white; font-weight: bold; border-radius: 20px; text-decoration: none;">Click here to visit the Divi Library</a></p>
                </div>';
	}

	echo '</form>';

	echo '</div>';

	echo '</div>';
	echo '</div>';
}

function sb_et_cpt_li_get_taxonomies() {
	$return = array();

	$ignored = array(
		'nav_menu'
	,
		'post_format'
	,
		'link_category'
	);

	$ignored_stubs = array(
		'pa_'
	,
		'product_'
	);

	if ( $taxonomies = get_taxonomies( false, 'object' ) ) {
		foreach ( $taxonomies as $taxonomy ) {

			if ( $taxonomy->publicly_queryable && ! in_array( $taxonomy->name, $ignored ) ) {
				$ignore_this = false;

				foreach ( $ignored_stubs as $ignored_stub ) {
					$length = strlen( $ignored_stub );
					if ( substr( $taxonomy->name, 0, $length ) == $ignored_stub ) {
						$ignore_this = true;
						break;
					}
				}

				if ( ! $ignore_this ) {
					if ( isset( $taxonomy->name ) ) {
						$return[ $taxonomy->name ] = $taxonomy->label;
					}
				}
			}
		}
	}

	return $return;
}

function sb_et_cpt_li_theme_setup() {

	if ( class_exists( 'ET_Builder_Module' ) ) {

		$modules_path = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/';

		//$is_layout = false;

		//if (isset($_GET['post'])) {
		//$pt = get_post_type($_GET['post']);

		//if (substr($pt, 0, 5) == 'et_pb') {
		//$is_layout = true;
		//}
		//}

		require_once( $modules_path . 'sb_et_cpt_li_title_module.php' );
		require_once( $modules_path . 'sb_et_cpt_li_pt_archive_module.php' );
		require_once( $modules_path . 'sb_et_cpt_li_loop_archive_module.php' );

		//if ($is_layout || !is_admin()) {
		require_once( $modules_path . 'sb_et_cpt_li_read_more_module.php' );
		require_once( $modules_path . 'sb_et_cpt_li_author_bio_module.php' );
		require_once( $modules_path . 'sb_et_cpt_li_postnav_module.php' );
		require_once( $modules_path . 'sb_et_cpt_li_taxonomy_module.php' );
		require_once( $modules_path . 'sb_et_cpt_li_content_module.php' );
		require_once( $modules_path . 'sb_et_cpt_li_gallery_module.php' );
		//}

	}
}

function sb_et_cpt_li_action_links( $links ) {
	$links[] = '<a href="' . esc_url( get_admin_url( null, 'options-general.php?page=sb_et_cpt_li' ) ) . '">Settings</a>';
	$links[] = '<a href="https://www.facebook.com/groups/599390973725519" target="_blank">Support</a>';
	$links[] = '<a href="https://elegantmarketplace.com/vendor/sean" target="_blank">More from Tortoise IT</a>';

	return $links;
}

?>