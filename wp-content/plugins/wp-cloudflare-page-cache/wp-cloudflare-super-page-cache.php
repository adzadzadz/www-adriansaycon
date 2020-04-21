<?php
/**
 * Plugin Name:  WP Cloudflare Super Page Cache
 * Plugin URI:   https://www.speedywordpress.it/
 * Description:  Speed up your website by enabling page caching on Cloudflare on free plans.
 * Version:      3.8
 * Author:       Salvatore Fresta
 * Author URI:   https://www.salvatorefresta.net/
 * License:      GPLv2 or later
 * Text Domain: wp-cloudflare-page-cache
*/

class SW_CLOUDFLARE_PAGECACHE {

    private $debug_enabled = false;
    private $debug_msg     = "";
    private $skip_cache    = false;
    private $config        = false;
    private $version       = 3.8;

    function __construct() {

        define('SWCFPC_AUTH_MODE_API_KEY', 0);
        define('SWCFPC_AUTH_MODE_API_TOKEN', 1);

        add_action( 'plugins_loaded', array($this, 'update_plugin') );
        register_activation_hook( __FILE__, array($this, 'update_plugin') );
        register_deactivation_hook( __FILE__, array($this, 'deactivate_plugin') );

        if( ! $this->init_config() ) {
            $this->config = $this->get_default_config();
            $this->update_config();
        }

        if( $this->get_single_config("debug", 0) > 0 ) {
            $this->debug_enabled = true;
        }

        $this->actions();

    }

    function load_textdomain() {

        load_plugin_textdomain( 'wp-cloudflare-page-cache', false, basename( dirname( __FILE__ ) ) . '/languages/' );

    }


    function actions() {

        add_action( 'admin_enqueue_scripts', array($this, 'load_custom_wp_admin_styles_and_script') );

        add_action( 'admin_menu', array($this, 'add_admin_menu_pages') );
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links') );

        add_action( 'init', array( $this, 'force_bypass_for_logged_in_users' ), PHP_INT_MAX );

        // This fires on both backend and frontend and it's used to check for URLs to bypass
        add_action( 'init', array($this, 'bypass_cache_on_init'), PHP_INT_MAX );

        // This fires on frontend
        add_action( 'template_redirect', array($this, 'apply_cache'), PHP_INT_MAX );

        // This fires on backend
        add_action( 'admin_init', array($this, 'apply_cache'), PHP_INT_MAX );

        // Admin toolbar options
        add_action( 'admin_bar_menu', array($this, 'add_toolbar_items'), PHP_INT_MAX );

        // Action rows
        add_filter( 'post_row_actions', array($this, 'add_post_row_actions'), PHP_INT_MAX, 2 );
        add_filter( 'page_row_actions', array($this, 'add_post_row_actions'), PHP_INT_MAX, 2 );

        // Multilanguage
        add_action( 'plugins_loaded', array($this, 'load_textdomain') );

        if( isset($_GET['swcfpc']) && isset($_GET['swcfpc-purge-all']) ) {
            add_action( 'init', array($this, 'cronjob_purge_cache') );
        }

        // WP Super Cache actions
        $this->wp_super_cache_hooks();

        // W3TC actions
        $this->w3tc_hooks();

        // WP Rocket actions
        $this->wp_rocket_hooks();


        if( $this->get_single_config("cf_auto_purge_on_comments", 0) > 0 ) {

            add_action( 'transition_comment_status', array($this, 'purge_cache_on_approved_comment'), PHP_INT_MAX, 3 );
            add_action( 'comment_post', array($this, 'purge_cache_on_new_comment'), PHP_INT_MAX, 3 );
            add_action( 'delete_comment', array($this, 'purge_cache_on_deleted_comment'), PHP_INT_MAX, 2 );

        }


        if( ($this->get_single_config("cf_auto_purge", 0) > 0 || $this->get_single_config("cf_auto_purge_all", 0) > 0) && $this->is_cache_enabled() ) {

            $purge_actions = array(
                'wp_update_nav_menu',                                     // When a custom menu is updated
                'update_option_theme_mods_' . get_option( 'stylesheet' ), // When any theme modifications are updated
                'avada_clear_dynamic_css_cache',                          // When Avada theme purge its own cache
                'autoptimize_action_cachepurged',                         // Compat with https://wordpress.org/plugins/autoptimize
                'switch_theme',                                           // When user changes the theme
                'customize_save_after',                                   // Edit theme
                'permalink_structure_changed',                            // When permalink structure is update
            );

            foreach ($purge_actions as $action) {
                add_action( $action, array($this, 'purge_cache_on_theme_edit'), PHP_INT_MAX );
            }

            $purge_actions = array(
                'deleted_post',                     // Delete a post
                'wp_trash_post',                    // Before a post is sent to the Trash
                'clean_post_cache',                 // After a post’s cache is cleaned
                'edit_post',                        // Edit a post - includes leaving comments
                'delete_attachment',                // Delete an attachment - includes re-uploading
                'elementor/editor/after_save',      // Elementor edit
                'elementor/core/files/clear_cache', // Elementor clear cache
            );

            foreach ($purge_actions as $action) {
                add_action( $action, array($this, 'purge_cache_on_post_edit'), PHP_INT_MAX, 2 );
            }

            add_action( 'transition_post_status', array($this, 'purge_cache_on_post_published'), PHP_INT_MAX, 3 );

        }

    }


    function get_default_config() {

        $config = array();

        // Cloudflare config
        $config["cf_zone_id"]                     = "";
        $config["cf_zoneid_list"]                 = array();
        $config["cf_email"]                       = "";
        $config["cf_apitoken"]                    = "";
        $config["cf_apikey"]                      = "";
        $config["cf_token"]                       = "";
        $config["cf_old_bc_ttl"]                  = "";
        $config["cf_page_rule_id"]                = "";
        $config["cf_subdomain"]                   = "";
        $config["cf_auto_purge"]                  = 1;
        $config["cf_auto_purge_all"]              = 0;
        $config["cf_auto_purge_on_comments"]      = 0;
        $config["cf_cache_enabled"]               = 0;
        $config["cf_maxage"]                      = 604800; // 1 week
        $config["browser_maxage"]                 = 60; // 1 minute
        $config["cf_post_per_page"]               = get_option( 'posts_per_page', 0);

        // Pages
        $config["cf_excluded_urls"]                 = array();
        $config["cf_bypass_front_page"]             = 0;
        $config["cf_bypass_pages"]                  = 0;
        $config["cf_bypass_home"]                   = 0;
        $config["cf_bypass_archives"]               = 0;
        $config["cf_bypass_tags"]                   = 0;
        $config["cf_bypass_category"]               = 0;
        $config["cf_bypass_author_pages"]           = 0;
        $config["cf_bypass_single_post"]            = 0;
        $config["cf_bypass_feeds"]                  = 1;
        $config["cf_bypass_search_pages"]           = 1;
        $config["cf_bypass_404"]                    = 1;
        $config["cf_bypass_logged_in"]              = 1;
        $config["cf_bypass_amp"]                    = 1;
        $config["cf_bypass_file_robots"]            = 1;
        $config["cf_bypass_sitemap"]                = 1;
        $config["cf_bypass_ajax"]                   = 1;
        $config["cf_cache_control_htaccess"]        = 1;
        $config["cf_browser_caching_htaccess"]      = 0;
        $config["cf_auth_mode"]                     = SWCFPC_AUTH_MODE_API_KEY;

        // WooCommerce
        $config["cf_bypass_woo_shop_page"]          = 0;
        $config["cf_bypass_woo_pages"]              = 0;
        $config["cf_bypass_woo_product_tax_page"]   = 0;
        $config["cf_bypass_woo_product_tag_page"]   = 0;
        $config["cf_bypass_woo_product_cat_page"]   = 0;
        $config["cf_bypass_woo_product_page"]       = 0;
        $config["cf_bypass_woo_cart_page"]          = 1;
        $config["cf_bypass_woo_checkout_page"]      = 1;
        $config["cf_bypass_woo_checkout_pay_page"]  = 1;

        // W3TC
        $config["cf_w3tc_purge_on_flush_minfy"]         = 0;
        $config["cf_w3tc_purge_on_flush_posts"]         = 0;
        $config["cf_w3tc_purge_on_flush_objectcache"]   = 0;
        $config["cf_w3tc_purge_on_flush_fragmentcache"] = 0;
        $config["cf_w3tc_purge_on_flush_dbcache"]       = 0;
        $config["cf_w3tc_purge_on_flush_all"]           = 0;

        // WP Rocket
        $config["cf_wp_rocket_purge_on_post_flush"]     = 0;
        $config["cf_wp_rocket_purge_on_domain_flush"]   = 0;

        // WP Super Cache
        $config["cf_wp_super_cache_on_cache_flush"] = 0;

        // Other
        $config["debug"] = 0;
        $config["preloader"]  = 0;

        return $config;

    }


    function get_single_config($name, $default=false) {

        if( !is_array($this->config) || !isset($this->config[$name]) )
            return $default;

        if( is_array($this->config[$name]))
            return $this->config[$name];

        return trim($this->config[$name]);

    }


    function set_single_config($name, $value) {

        if( !is_array($this->config) )
            $this->config = array();

        if( is_array($value) )
            $this->config[trim($name)] = $value;
        else
            $this->config[trim($name)] = trim($value);

    }
    
    
    function update_config() {

        update_option( 'swcfpc_config', serialize( $this->config ) );
        
    }
    
    
    function init_config() {
        
        $this->config = get_option( 'swcfpc_config', false );
        
        if( !$this->config )
            return false;
        
        $this->config = unserialize( $this->config );
        
        return true;
        
    }


    function update_plugin() {

        $current_version = get_option( 'swcfpc_version', false );

        if( !$current_version || $current_version != $this->version ) {

            global $wpdb;

            if( $current_version < 2.0 ) {

                $config = $this->get_default_config();

                // Cloudflare config
                $config["cf_zone_id"]        = get_option("swcfpc_cf_zoneid",        "");
                $config["cf_zoneid_list"]    = get_option("swcfpc_cf_zoneid_list",   "");
                $config["cf_email"]          = get_option("swcfpc_cf_email",         "");
                $config["cf_apikey"]         = get_option("swcfpc_cf_apikey",        "");
                $config["cf_old_bc_ttl"]     = get_option("swcfpc_cf_old_bc_ttl",    "");
                $config["cf_page_rule_id"]   = get_option("swcfpc_cf_page_rule_id",  "");
                $config["cf_subdomain"]      = get_option("swcfpc_cf_subdomain",     "");
                $config["cf_auto_purge"]     = get_option("swcfpc_cf_auto_purge",     1);
                $config["cf_cache_enabled"]  = get_option("swcfpc_cf_cache_enabled",  0);
                $config["cf_maxage"]         = get_option("swcfpc_maxage", 604800); // 1 week
                $config["browser_maxage"]    = 60; // 1 minute

                // Pages
                $config["cf_excluded_urls"]       = get_option("swcfpc_cf_excluded_urls", 0);
                $config["cf_bypass_front_page"]   = get_option("swcfpc_cf_bypass_front_page", 0);
                $config["cf_bypass_pages"]        = get_option("swcfpc_cf_bypass_pages", 0);
                $config["cf_bypass_home"]         = get_option("swcfpc_cf_bypass_home", 0);
                $config["cf_bypass_archives"]     = get_option("swcfpc_cf_bypass_archives", 0);
                $config["cf_bypass_tags"]         = get_option("swcfpc_cf_bypass_tags", 0);
                $config["cf_bypass_category"]     = get_option("swcfpc_cf_bypass_category", 0);
                $config["cf_bypass_author_pages"] = get_option("swcfpc_cf_bypass_author_pages", 0);
                $config["cf_bypass_single_post"]  = get_option("swcfpc_cf_bypass_single_post", 0);
                $config["cf_bypass_feeds"]        = get_option("swcfpc_cf_bypass_feeds", 1);
                $config["cf_bypass_search_pages"] = get_option("swcfpc_cf_bypass_search_pages", 1);
                $config["cf_bypass_404"]          = get_option("swcfpc_cf_bypass_404", 1);
                $config["cf_bypass_logged_in"]    = get_option("swcfpc_cf_bypass_logged_in", 1);
                $config["cf_bypass_amp"]          = get_option("swcfpc_cf_bypass_amp", 0);
                $config["cf_bypass_file_robots"]  = get_option("swcfpc_cf_bypass_file_robots", 0);
                $config["cf_bypass_sitemap"]      = get_option("swcfpc_cf_bypass_sitemap", 0);
                $config["cf_bypass_ajax"]         = get_option("swcfpc_cf_bypass_ajax", 1);

                // Other
                $config["debug"] = get_option("swcfpc_debug", 0);

                $this->config = $config;
                $this->update_config();

                delete_option("swcfpc_maxage");
                delete_option("swcfpc_debug");
                delete_option("swcfpc_cf_zoneid");
                delete_option("swcfpc_cf_zoneid_list");
                delete_option("swcfpc_cf_email");
                delete_option("swcfpc_cf_apikey");
                delete_option("swcfpc_cf_old_bc_ttl");
                delete_option("swcfpc_cf_page_rule_id");
                delete_option("swcfpc_cf_auto_purge");
                delete_option("swcfpc_cf_cache_enabled");
                delete_option("swcfpc_cf_excluded_urls");
                delete_option("swcfpc_cf_bypass_front_page");
                delete_option("swcfpc_cf_bypass_pages");
                delete_option("swcfpc_cf_bypass_home");
                delete_option("swcfpc_cf_bypass_archives");
                delete_option("swcfpc_cf_bypass_tags");
                delete_option("swcfpc_cf_bypass_category");
                delete_option("swcfpc_cf_bypass_feeds");
                delete_option("swcfpc_cf_bypass_search_pages");
                delete_option("swcfpc_cf_bypass_author_pages");
                delete_option("swcfpc_cf_bypass_single_post");
                delete_option("swcfpc_cf_bypass_404");
                delete_option("swcfpc_cf_bypass_logged_in");
                delete_option("swcfpc_cf_bypass_amp");
                delete_option("swcfpc_cf_bypass_file_robots");
                delete_option("swcfpc_cf_bypass_sitemap");
                delete_option("swcfpc_cf_bypass_ajax");
                delete_option("swcfpc_cf_subdomain");

            }

            if( $current_version < 3.6 ) {

                $nginx_file_path = $this->get_upload_directory_path()."/nginx.conf";

                if( file_exists($nginx_file_path) )
                    @unlink( $nginx_file_path );

            }

            if( $current_version < 3.8 ) {

                $this->set_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY);
                $this->update_config();

            }

        }

        update_option("swcfpc_version", $this->version);

    }


    function deactivate_plugin() {

        $this->reset_all();

    }


    function load_custom_wp_admin_styles_and_script() {

        $css_version = "1.2.5";
        $js_version = "1.0.2";

        wp_register_style( 'swcfpc_admin_css', plugins_url( '/assets/css/style.css', __FILE__ ), false, $css_version );
        wp_register_script( 'swcfpc_admin_js', plugins_url( '/assets/js/backend.js', __FILE__ ), array('jquery'), $js_version, true );

    }


    function reset_all() {

        $error = "";

        // Reset old browser cache TTL
        $this->cloudflare_set_browser_cache_ttl( $this->get_single_config("cf_old_bc_ttl", 0), $error );

        // Delete the page rule
        $this->cloudflare_delete_page_rule( $error );

        $this->config = $this->get_default_config();
        $this->update_config();

        insert_with_markers( get_home_path().".htaccess", "WP Cloudflare Super Page Cache", array() );

    }


    function purge_cache_on_approved_comment($new_status, $old_status, $comment) {

        if ($old_status != $new_status && $new_status == 'approved') {

            $error = "";
            $urls = array();

            $urls[] = get_permalink( $comment->comment_post_ID );
            $this->cloudflare_purge_cache_urls( $urls, $error );
            
        }

    }


    function purge_cache_on_new_comment( $comment_ID, $comment_approved, $commentdata ) {

        if( isset($commentdata['comment_post_ID']) ) {

            $error = "";
            $urls = array();

            $urls[] = get_permalink( $commentdata['comment_post_ID'] );
            $this->cloudflare_purge_cache_urls( $urls, $error );

        }

    }


    function purge_cache_on_deleted_comment( $comment_ID, $comment ) {

        $error = "";
        $urls = array();

        $urls[] = get_permalink( $comment->comment_post_ID );
        $this->cloudflare_purge_cache_urls( $urls, $error );

    }


    function purge_cache_on_post_published( $new_status, $old_status, $post ) {

        if( $old_status != 'publish' && $new_status == 'publish' ) {

            $error = "";

            if( $this->get_single_config("cf_auto_purge_all", 0) > 0 ) {
                $this->cloudflare_purge_cache( $error );
            }
            else {
                $urls = $this->get_post_related_links( $post->ID );
                $this->cloudflare_purge_cache_urls( $urls, $error );
            }

        }

    }


    function purge_cache_on_post_edit( $postId ) {

        $error = "";

        $validPostStatus = array('publish', 'trash');
        $thisPostStatus = get_post_status($postId);

        if (get_permalink($postId) != true || !in_array($thisPostStatus, $validPostStatus)) {
            return;
        }

        if (is_int(wp_is_post_autosave($postId)) ||  is_int(wp_is_post_revision($postId))) {
            return;
        }

        if( $this->get_single_config("cf_auto_purge_all", 0) > 0 ) {
            $error = "";
            $this->cloudflare_purge_cache( $error );
            return;
        }

        $savedPost = get_post($postId);

        if (is_a($savedPost, 'WP_Post') == false) {
            return;
        }

        $urls = $this->get_post_related_links($postId);

        $this->cloudflare_purge_cache_urls( $urls, $error );

    }


    function get_post_related_links($postId) {

        $listofurls = array();
        $postType = get_post_type($postId);

        //Purge taxonomies terms URLs
        $postTypeTaxonomies = get_object_taxonomies($postType);

        foreach ($postTypeTaxonomies as $taxonomy) {
            $terms = get_the_terms($postId, $taxonomy);

            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $term) {

                $termLink = get_term_link($term);

                if (!is_wp_error($termLink)) {

                    array_push($listofurls, $termLink);

                    if( $this->get_single_config("cf_post_per_page", 0) > 0 ) {

                        // Thanks to Davide Prevosto for the suggest
                        $term_count   = $term->count;
                        $pages_number = ceil($term_count / $this->get_single_config("cf_post_per_page", 0) );
                        $max_pages    = $pages_number > 10 ? 10 : $pages_number; // Purge max 10 pages

                        for ($i=2; $i<=$max_pages; $i++) {
                            $paginated_url = $termLink . 'page/' . user_trailingslashit($i);
                            array_push($listofurls, $paginated_url);
                        }

                    }

                }

            }

        }

        // Author URL
        array_push(
            $listofurls,
            get_author_posts_url(get_post_field('post_author', $postId)),
            get_author_feed_link(get_post_field('post_author', $postId))
        );

        // Archives and their feeds
        if (get_post_type_archive_link($postType) == true) {
            array_push(
                $listofurls,
                get_post_type_archive_link($postType),
                get_post_type_archive_feed_link($postType)
            );
        }

        // Post URL
        array_push($listofurls, get_permalink($postId));

        // Also clean URL for trashed post.
        if (get_post_status($postId) == 'trash') {
            $trashPost = get_permalink($postId);
            $trashPost = str_replace('__trashed', '', $trashPost);
            array_push($listofurls, $trashPost, $trashPost.'feed/');
        }

        // Feeds
        array_push(
            $listofurls,
            get_bloginfo_rss('rdf_url'),
            get_bloginfo_rss('rss_url'),
            get_bloginfo_rss('rss2_url'),
            get_bloginfo_rss('atom_url'),
            get_bloginfo_rss('comments_rss2_url'),
            get_post_comments_feed_link($postId)
        );

        // Home Page and (if used) posts page
        array_push($listofurls, home_url('/'));
        $pageLink = get_permalink(get_option('page_for_posts'));
        if (is_string($pageLink) && !empty($pageLink) && get_option('show_on_front') == 'page') {
            array_push($listofurls, $pageLink);
        }

        // Purge https and http URLs
        if (function_exists('force_ssl_admin') && force_ssl_admin()) {
            $listofurls = array_merge($listofurls, str_replace('https://', 'http://', $listofurls));
        } elseif (!is_ssl() && function_exists('force_ssl_content') && force_ssl_content()) {
            $listofurls = array_merge($listofurls, str_replace('http://', 'https://', $listofurls));
        }

        return $listofurls;
    }


    function purge_cache_on_theme_edit() {

        $error = "";
        $this->cloudflare_purge_cache( $error );

    }


    function purge_cache_on_other_plugin_cache_flushes() {

        $error = "";
        $this->cloudflare_purge_cache( $error );

    }


    function add_admin_menu_pages() {

        add_submenu_page(
                "options-general.php",
                __( 'WP Cloudflare Super Page Cache', 'wp-cloudflare-page-cache' ),
                __( 'WP Cloudflare Super Page Cache', 'wp-cloudflare-page-cache' ),
                'manage_options',
                'wp-cloudflare-super-page-cache-index',
                array($this, 'admin_menu_page_index')
        );

        add_submenu_page(
            null,
            __( 'WP Cloudflare Super Page Cache Nginx Settings', 'wp-cloudflare-page-cache' ),
            __( 'WP Cloudflare Super Page Cache Nginx Settings', 'wp-cloudflare-page-cache' ),
            'manage_options',
            'wp-cloudflare-super-page-cache-nginx-settings',
            array($this, 'admin_menu_page_nginx_settings')
        );

    }


    function add_post_row_actions( $actions, $post ) {

        $url = add_query_arg(array("page" => "wp-cloudflare-super-page-cache-index", "swcfpc" => 1, "swcfpc-purge-cache-single-post" => 1, "post_id" => $post->ID), admin_url("options-general.php"));

        $actions['swcfpc_single_purge'] = '<a href="'.$url.'" target="_blank">'.__('Purge CF Cache', 'wp-cloudflare-page-cache').'</a>';

        return $actions;

    }


    function add_toolbar_items( $admin_bar ) {

        if( $this->get_single_config("cf_cache_enabled", 0) > 0 ) {

            global $post;

            $admin_bar->add_menu(array(
                'id' => 'wp-cloudflare-super-page-cache-toolbar-container',
                'title' => __('Purge CF Cache', 'wp-cloudflare-page-cache'),
                'href' => '#',
            ));

            $admin_bar->add_menu(array(
                'id' => 'wp-cloudflare-super-page-cache-toolbar-purge-all',
                'parent' => 'wp-cloudflare-super-page-cache-toolbar-container',
                'title' => __('Purge whole Cloudflare Cache', 'wp-cloudflare-page-cache'),
                'href' => add_query_arg(array("page" => "wp-cloudflare-super-page-cache-index", "swcfpc" => 1, "swcfpc-purge-cache" => 1), admin_url("options-general.php")),
            ));

            if (is_object($post)) {

                $admin_bar->add_menu(array(
                    'id' => 'wp-cloudflare-super-page-cache-toolbar-purge-single',
                    'parent' => 'wp-cloudflare-super-page-cache-toolbar-container',
                    'title' => __('Purge only current page cache', 'wp-cloudflare-page-cache'),
                    'href' => add_query_arg(array("page" => "wp-cloudflare-super-page-cache-index", "swcfpc" => 1, "swcfpc-purge-cache-single-post" => 1, "post_id" => $post->ID), admin_url("options-general.php")),
                ));

            }

        }

    }


    function add_plugin_action_links( $links ) {

        $mylinks = array(
            '<a href="' . admin_url( 'options-general.php?page=wp-cloudflare-super-page-cache-index' ) . '">'.__( 'Settings', 'wp-cloudflare-page-cache' ).'</a>',
        );

        return array_merge( $links, $mylinks );

    }


    function admin_menu_page_index() {

        if( !current_user_can("manage_options") ) {
            die( __("Permission denied", 'wp-cloudflare-page-cache') );
        }

        $error = false;
        $error_msg = "";

        $success = false;
        $success_msg = "";

        $domain_found = false;
        $domain_zone_id = "";

        $nginx_instructions_page_url = add_query_arg( array("page" => "wp-cloudflare-super-page-cache-nginx-settings"), admin_url("options-general.php") );

        $htaccess_lines = array();
        $htaccess_file_path = get_home_path().".htaccess";

        if( (isset($_POST['swcfpc_debug']) && intval($_POST['swcfpc_debug']) > 0) || $this->get_single_config("debug") > 0 ) {
            $this->debug_enabled = true;
        }

        // Disable cache
        if( isset($_POST['swcfpc_submit_disable_page_cache']) ) {

            // Reset old browser cache TTL
            $this->cloudflare_set_browser_cache_ttl( $this->get_single_config("cf_old_bc_ttl", 0), $error_msg );

            // Delete the page rule
            $this->cloudflare_delete_page_rule( $error );

            // Purge cache
            $this->cloudflare_purge_cache($error_msg);

            $this->set_single_config("cf_cache_enabled", 0);

            remove_action( 'wp_footer',    array($this, 'inject_js_code'), 100 );
            remove_action( 'admin_footer', array($this, 'inject_js_code'), 100 );

            $success = true;
            $success_msg = __("Page cache disabled successfully", 'wp-cloudflare-page-cache');

        }

        // Test cache
        if( isset($_POST['swcfpc_submit_test_cache']) ) {

            if( !$this->cloudflare_page_cache_test($error_msg) ) {
                $error = true;
            }
            else {
                $success = true;
                $success_msg = __("Page caching is working properly", 'wp-cloudflare-page-cache');
            }

        }

        // Manual cache purging
        if( isset($_POST['swcfpc_submit_purge_cache']) || isset($_GET['swcfpc-purge-cache']) || isset($_GET['swcfpc-purge-cache-single-post']) ) {

            if( isset($_GET['swcfpc-purge-cache-single-post']) ) {

                if( !isset($_GET['post_id']) ) {
                    $error = true;
                    $error_msg = __("Unable to find the page ID", 'wp-cloudflare-page-cache');
                }
                else {

                    $post_id = intval( $_GET['post_id'] );

                    if( ! get_post_status($post_id) ) {
                        $error = true;
                        $error_msg = __("Invalid page ID", 'wp-cloudflare-page-cache');
                    }
                    else {

                        $urls = array();

                        $urls[] = get_permalink( $post_id );

                        if ( !$this->cloudflare_purge_cache_urls( $urls, $error ) ) {
                            $error = true;
                        } else {
                            $success = true;
                            $success_msg = __("Cache purged successfully. It may take up to 30 seconds for the cache to be permanently cleaned by Cloudflare", 'wp-cloudflare-page-cache');
                        }

                    }

                }

            }
            else {

                if ( !$this->cloudflare_purge_cache($error_msg) ) {
                    $error = true;
                } else {
                    $success = true;
                    $success_msg = __("Cache purged successfully. It may take up to 30 seconds for the cache to be permanently cleaned by Cloudflare", 'wp-cloudflare-page-cache');
                }

            }

        }

        // Reset all
        if( isset($_POST['swcfpc_submit_reset_all']) ) {
            $this->reset_all();
        }

        // Enable page cache
        if( isset($_POST['swcfpc_submit_enable_page_cache']) ) {

            if( ($current_ttl = $this->cloudflare_get_browser_cache_ttl( $error_msg ) ) === false ) {
                $error = true;
            }
            else {
                $this->set_single_config("cf_old_bc_ttl", $current_ttl);
            }

            // Step 1 - set browser cache ttl to zero (Respect Existing Headers)
            if( !$error && !$this->cloudflare_set_browser_cache_ttl(0, $error_msg) ) {
                $error = true;
                $this->set_single_config("cf_cache_enabled", 0);
            }

            // Step 2 - delete old page rule, if exist
            if( !$error && $this->get_single_config("cf_page_rule_id", 0) ) {
                $this->cloudflare_delete_page_rule( $error_msg );
            }

            // Step 3 - create new page rule
            if( !$error && !$this->cloudflare_set_page_rule($error_msg) ) {
                $error = true;
                $this->set_single_config("cf_cache_enabled", 0);
            }

            // Step 4 - purge cache
            if( !$error ) {
                $this->cloudflare_purge_cache($error_msg);
            }

            if( !$error) {

                $this->set_single_config("cf_cache_enabled", 1);

                add_action( 'wp_footer',    array($this, 'inject_js_code'), 100 );
                add_action( 'admin_footer', array($this, 'inject_js_code'), 100 );

                $success = true;
                $success_msg = __("Page cache enabled successfully", 'wp-cloudflare-page-cache');

            }

        }

        // Save settings
        if( isset($_POST['swcfpc_submit_general']) ) {
            
            $this->set_single_config("cf_auth_mode", intval($_POST['swcfpc_cf_auth_mode']));
            $this->set_single_config("cf_email", sanitize_email($_POST['swcfpc_cf_email']));
            $this->set_single_config("cf_apikey", $_POST['swcfpc_cf_apikey']);
            $this->set_single_config("cf_apitoken", $_POST['swcfpc_cf_apitoken']);
            $this->set_single_config("debug", intval($_POST['swcfpc_debug']));

            // Salvataggio immediato per consentire di applicare subito i settaggi di connessione
            $this->update_config();

            if( isset($_POST['swcfpc_post_per_page']) && intval($_POST['swcfpc_post_per_page']) >= 0 ) {
                $this->set_single_config("cf_post_per_page", intval($_POST['swcfpc_post_per_page']));
            }

            if( isset($_POST['swcfpc_maxage']) && intval($_POST['swcfpc_maxage']) >= 0 ) {
                $this->set_single_config("cf_maxage", intval($_POST['swcfpc_maxage']));
            }

            if( isset($_POST['swcfpc_browser_maxage']) && intval($_POST['swcfpc_browser_maxage']) >= 0 ) {
                $this->set_single_config("browser_maxage", intval($_POST['swcfpc_browser_maxage']));
            }

            if( isset($_POST['swcfpc_cf_zoneid']) ) {
                $this->set_single_config("cf_zoneid", trim($_POST['swcfpc_cf_zoneid']));
            }

            if( isset($_POST['swcfpc_cf_subdomain']) ) {
                $this->set_single_config("cf_subdomain", trim($_POST['swcfpc_cf_subdomain']));
            }

            if( isset($_POST['swcfpc_cf_auto_purge']) ) {
                $this->set_single_config("cf_auto_purge", intval($_POST['swcfpc_cf_auto_purge']));
            }

            if( isset($_POST['swcfpc_cf_auto_purge_all']) ) {
                $this->set_single_config("cf_auto_purge_all", intval($_POST['swcfpc_cf_auto_purge_all']));
            }

            if( isset($_POST['swcfpc_cf_bypass_404']) ) {
                $this->set_single_config("cf_bypass_404", intval($_POST['swcfpc_cf_bypass_404']));
            }
            else {
                $this->set_single_config("cf_bypass_404", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_single_post']) ) {
                $this->set_single_config("cf_bypass_single_post", intval($_POST['swcfpc_cf_bypass_single_post']));
            }
            else {
                $this->set_single_config("cf_bypass_single_post", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_author_pages']) ) {
                $this->set_single_config("cf_bypass_author_pages", intval($_POST['swcfpc_cf_bypass_author_pages']));
            }
            else {
                $this->set_single_config("cf_bypass_author_pages", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_search_pages']) ) {
                $this->set_single_config("cf_bypass_search_pages", intval($_POST['swcfpc_cf_bypass_search_pages']));
            }
            else {
                $this->set_single_config("cf_bypass_search_pages", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_feeds']) ) {
                $this->set_single_config("cf_bypass_feeds", intval($_POST['swcfpc_cf_bypass_feeds']));
            }
            else {
                $this->set_single_config("cf_bypass_feeds", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_category']) ) {
                $this->set_single_config("cf_bypass_category", intval($_POST['swcfpc_cf_bypass_category']));
            }
            else {
                $this->set_single_config("cf_bypass_category", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_tags']) ) {
                $this->set_single_config("cf_bypass_tags", intval($_POST['swcfpc_cf_bypass_tags']));
            }
            else {
                $this->set_single_config("cf_bypass_tags", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_archives']) ) {
                $this->set_single_config("cf_bypass_archives", intval($_POST['swcfpc_cf_bypass_archives']));
            }
            else {
                $this->set_single_config("cf_bypass_archives", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_home']) ) {
                $this->set_single_config("cf_bypass_home", intval($_POST['swcfpc_cf_bypass_home']));
            }
            else {
                $this->set_single_config("cf_bypass_home", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_front_page']) ) {
                $this->set_single_config("cf_bypass_front_page", intval($_POST['swcfpc_cf_bypass_front_page']));
            }
            else {
                $this->set_single_config("cf_bypass_front_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_pages']) ) {
                $this->set_single_config("cf_bypass_pages", intval($_POST['swcfpc_cf_bypass_pages']));
            }
            else {
                $this->set_single_config("cf_bypass_pages", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_amp']) ) {
                $this->set_single_config("cf_bypass_amp", intval($_POST['swcfpc_cf_bypass_amp']));
            }
            else {
                $this->set_single_config("cf_bypass_amp", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_logged_in']) ) {
                $this->set_single_config("cf_bypass_logged_in", intval($_POST['swcfpc_cf_bypass_logged_in']));
            }

            if( isset($_POST['swcfpc_cf_bypass_sitemap']) ) {
                $this->set_single_config("cf_bypass_sitemap", intval($_POST['swcfpc_cf_bypass_sitemap']));
            }

            if( isset($_POST['swcfpc_cf_bypass_file_robots']) ) {
                $this->set_single_config("cf_bypass_file_robots", intval($_POST['swcfpc_cf_bypass_file_robots']));
            }

            if( isset($_POST['swcfpc_cf_bypass_ajax']) ) {
                $this->set_single_config("cf_bypass_ajax", intval($_POST['swcfpc_cf_bypass_ajax']));
            }

            // WooCommerce
            if( isset($_POST['swcfpc_cf_bypass_woo_cart_page']) ) {
                $this->set_single_config("cf_bypass_woo_cart_page", intval($_POST['swcfpc_cf_bypass_woo_cart_page']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_cart_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_checkout_page']) ) {
                $this->set_single_config("cf_bypass_woo_checkout_page", intval($_POST['swcfpc_cf_bypass_woo_checkout_page']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_checkout_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_checkout_pay_page']) ) {
                $this->set_single_config("cf_bypass_woo_checkout_pay_page", intval($_POST['swcfpc_cf_bypass_woo_checkout_pay_page']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_checkout_pay_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_shop_page']) ) {
                $this->set_single_config("cf_bypass_woo_shop_page", intval($_POST['swcfpc_cf_bypass_woo_shop_page']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_shop_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_pages']) ) {
                $this->set_single_config("cf_bypass_woo_pages", intval($_POST['swcfpc_cf_bypass_woo_pages']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_pages", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_product_tax_page']) ) {
                $this->set_single_config("cf_bypass_woo_product_tax_page", intval($_POST['swcfpc_cf_bypass_woo_product_tax_page']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_product_tax_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_product_tag_page']) ) {
                $this->set_single_config("cf_bypass_woo_product_tag_page", intval($_POST['swcfpc_cf_bypass_woo_product_tag_page']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_product_tag_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_product_cat_page']) ) {
                $this->set_single_config("cf_bypass_woo_product_cat_page", intval($_POST['swcfpc_cf_bypass_woo_product_cat_page']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_product_cat_page", 0);
            }

            if( isset($_POST['swcfpc_cf_bypass_woo_product_page']) ) {
                $this->set_single_config("cf_bypass_woo_product_page", intval($_POST['swcfpc_cf_bypass_woo_product_page']));
            }
            else {
                $this->set_single_config("cf_bypass_woo_product_page", 0);
            }

            // W3TC
            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_minfy']) ) {
                $this->set_single_config("cf_w3tc_purge_on_flush_minfy", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_minfy']));
            }
            else {
                $this->set_single_config("cf_w3tc_purge_on_flush_minfy", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_posts']) ) {
                $this->set_single_config("cf_w3tc_purge_on_flush_posts", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_posts']));
            }
            else {
                $this->set_single_config("cf_w3tc_purge_on_flush_posts", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_objectcache']) ) {
                $this->set_single_config("cf_w3tc_purge_on_flush_objectcache", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_objectcache']));
            }
            else {
                $this->set_single_config("cf_w3tc_purge_on_flush_objectcache", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_fragmentcache']) ) {
                $this->set_single_config("cf_w3tc_purge_on_flush_fragmentcache", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_fragmentcache']));
            }
            else {
                $this->set_single_config("cf_w3tc_purge_on_flush_fragmentcache", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_dbcache']) ) {
                $this->set_single_config("cf_w3tc_purge_on_flush_dbcache", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_dbcache']));
            }
            else {
                $this->set_single_config("cf_w3tc_purge_on_flush_dbcache", 0);
            }

            if( isset($_POST['swcfpc_cf_w3tc_purge_on_flush_all']) ) {
                $this->set_single_config("cf_w3tc_purge_on_flush_all", intval($_POST['swcfpc_cf_w3tc_purge_on_flush_all']));
            }
            else {
                $this->set_single_config("cf_w3tc_purge_on_flush_all", 0);
            }

            // WP ROCKET
            if( isset($_POST['swcfpc_cf_wp_rocket_purge_on_post_flush']) ) {
                $this->set_single_config("cf_wp_rocket_purge_on_post_flush", intval($_POST['swcfpc_cf_wp_rocket_purge_on_post_flush']));
            }
            else {
                $this->set_single_config("cf_wp_rocket_purge_on_post_flush", 0);
            }

            if( isset($_POST['swcfpc_cf_wp_rocket_purge_on_domain_flush']) ) {
                $this->set_single_config("cf_wp_rocket_purge_on_domain_flush", intval($_POST['swcfpc_cf_wp_rocket_purge_on_domain_flush']));
            }
            else {
                $this->set_single_config("cf_wp_rocket_purge_on_domain_flush", 0);
            }

            // WP Super Cache
            if( isset($_POST['swcfpc_cf_wp_super_cache_on_cache_flush']) ) {
                $this->set_single_config("cf_wp_super_cache_on_cache_flush", intval($_POST['swcfpc_cf_wp_super_cache_on_cache_flush']));
            }
            else {
                $this->set_single_config("cf_wp_super_cache_on_cache_flush", 0);
            }

            // Htaccess
            if( isset($_POST['swcfpc_cf_cache_control_htaccess']) ) {
                $this->set_single_config("cf_cache_control_htaccess", intval($_POST['swcfpc_cf_cache_control_htaccess']));
            }

            if( isset($_POST['swcfpc_cf_browser_caching_htaccess']) ) {
                $this->set_single_config("cf_browser_caching_htaccess", intval($_POST['swcfpc_cf_browser_caching_htaccess']));
            }

            // Comments
            if( isset($_POST['swcfpc_cf_auto_purge_on_comments']) ) {
                $this->set_single_config("cf_auto_purge_on_comments", intval($_POST['swcfpc_cf_auto_purge_on_comments']));
            }

            // URLs to exclude from cache
            if( isset($_POST['swcfpc_cf_excluded_urls']) ) {

                $excluded_urls = str_replace( array("http:", "https:", "ftp:"), "", $_POST['swcfpc_cf_excluded_urls']);
                $excluded_urls = explode("\n", $excluded_urls);
                $excluded_urls = array_map('trim', $excluded_urls);

                $this->set_single_config("cf_excluded_urls", $excluded_urls);

            }

            if( !$this->cloudflare_get_zone_ids( $error_msg ) ) {
                $error = true;
            }

        }

        // Salvataggio configurazioni
        $this->update_config();

        // Aggiorno htaccess in caso di salvataggio. Lo faccio dopo update_config in quanto mi occorre avere i dati sul valore di cache-control
        if( isset($_POST['swcfpc_submit_general']) || isset($_POST['swcfpc_submit_enable_page_cache']) || isset($_POST['swcfpc_submit_disable_page_cache']) ) {

            if( $this->get_single_config("cf_cache_control_htaccess", 0) > 0 && $this->is_cache_enabled() ) {

                $htaccess_lines[] = "<IfModule mod_headers.c>";
                $htaccess_lines[] = "Header unset Pragma \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
                $htaccess_lines[] = "Header always unset Pragma \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
                $htaccess_lines[] = "Header unset Expires \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
                $htaccess_lines[] = "Header always unset Expires \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
                $htaccess_lines[] = "Header unset Cache-Control \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
                $htaccess_lines[] = "Header always unset Cache-Control \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
                $htaccess_lines[] = "Header always set Cache-Control \"" . $this->get_cache_control_value() . "\" \"expr=resp('x-wp-cf-super-cache-active') == '1'\"";
                $htaccess_lines[] = "</IfModule>";

            }

            if( $this->get_single_config("cf_bypass_sitemap", 0) > 0 ) {

                $htaccess_lines[] = "<IfModule mod_expires.c>";
                $htaccess_lines[] = "ExpiresActive on";
                $htaccess_lines[] = 'ExpiresByType application/xml "access plus 0 seconds"';
                $htaccess_lines[] = "</IfModule>";

            }

            if( $this->get_single_config("cf_bypass_file_robots", 0) > 0 ) {

                $htaccess_lines[] = '<FilesMatch "robots\.txt">';
                $htaccess_lines[] = "<IfModule mod_headers.c>";
                $htaccess_lines[] = 'Header set Cache-Control "max-age=0, public"';
                $htaccess_lines[] = "</IfModule>";
                $htaccess_lines[] = "</FilesMatch>";

            }

            if( $this->get_single_config("cf_browser_caching_htaccess", 0) > 0 ) {

                $htaccess_lines[] = "<IfModule mod_expires.c>";
                $htaccess_lines[] = "ExpiresActive on";
                $htaccess_lines[] = 'ExpiresDefault                              "access plus 1 month"';

                // Data
                $htaccess_lines[] = 'ExpiresByType application/json              "access plus 0 seconds"';
                $htaccess_lines[] = 'ExpiresByType application/xml               "access plus 0 seconds"';

                // Feed
                $htaccess_lines[] = 'ExpiresByType application/rss+xml           "access plus 1 hour"';
                $htaccess_lines[] = 'ExpiresByType application/atom+xml          "access plus 1 hour"';
                $htaccess_lines[] = 'ExpiresByType image/x-icon                  "access plus 1 week"';

                // Media: images, video, audio
                $htaccess_lines[] = 'ExpiresByType image/gif                     "access plus 6 months"';
                $htaccess_lines[] = 'ExpiresByType image/png                     "access plus 6 months"';
                $htaccess_lines[] = 'ExpiresByType image/jpeg                    "access plus 6 months"';
                $htaccess_lines[] = 'ExpiresByType image/webp                    "access plus 6 months"';
                $htaccess_lines[] = 'ExpiresByType video/ogg                     "access plus 1 month"';
                $htaccess_lines[] = 'ExpiresByType audio/ogg                     "access plus 1 month"';
                $htaccess_lines[] = 'ExpiresByType video/mp4                     "access plus 1 month"';
                $htaccess_lines[] = 'ExpiresByType video/webm                    "access plus 1 month"';

                // HTC files  (css3pie)
                $htaccess_lines[] = 'ExpiresByType text/x-component              "access plus 1 month"';

                // Webfonts
                $htaccess_lines[] = 'ExpiresByType font/ttf                      "access plus 4 months"';
                $htaccess_lines[] = 'ExpiresByType font/otf                      "access plus 4 months"';
                $htaccess_lines[] = 'ExpiresByType font/woff                     "access plus 4 months"';
                $htaccess_lines[] = 'ExpiresByType font/woff2                    "access plus 4 months"';
                $htaccess_lines[] = 'ExpiresByType image/svg+xml                 "access plus 1 month"';
                $htaccess_lines[] = 'ExpiresByType application/vnd.ms-fontobject "access plus 1 month"';

                // CSS and JavaScript
                $htaccess_lines[] = 'ExpiresByType text/css                      "access plus 1 year"';
                $htaccess_lines[] = 'ExpiresByType application/javascript        "access plus 1 year"';

                $htaccess_lines[] = "</IfModule>";

            }

            if( !insert_with_markers( $htaccess_file_path, "WP Cloudflare Super Page Cache", $htaccess_lines ) ) {
                $error = true;
                $error_msg = __( sprintf('The .htaccess file (%s) could not be edited. Check if the file has write permissions.', $htaccess_file_path), 'wp-cloudflare-page-cache');
            }

        }

        $zone_id_list = $this->get_single_config("cf_zoneid_list", "");

        if( is_array( $zone_id_list ) ) {

            // If the domain name is found in the zone list, I will show it only instead of full domains list
            $current_domain = str_replace( array("/", "http:", "https:", "www."), "", site_url() );

            foreach($zone_id_list as $zone_id_name => $zone_id) {

                if( $zone_id_name == $current_domain ) {
                    $domain_found = true;
                    $domain_zone_id = $zone_id;
                    break;
                }

            }


        }
        else {
            $zone_id_list = array();
        }


        if( $this->debug_enabled ) {

            $debug  = "";

            if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_TOKEN )
                $debug .= "<p><b>Auth mode:</b> API Token</p>";
            else
                $debug .= "<p><b>Auth mode:</b> API Key</p>";

            $debug .= "<p><b>Email:</b> ".$this->get_single_config("cf_email", "")."</p>";
            $debug .= "<p><b>API Key:</b> ".$this->get_single_config("cf_apikey", "")."</p>";
            $debug .= "<p><b>API Token:</b> ".$this->get_single_config("cf_apitoken", "")."</p>";
            $debug .= "<p><b>Zone ID:</b> ".$this->get_single_config("cf_zoneid", "")."</p>";
            $debug .= "<p><b>Page rule ID:</b> ".$this->get_single_config("cf_page_rule_id", "")."</p>";
            $debug .= "<p><b>Old TTL:</b> ".$this->get_single_config("cf_old_bc_ttl", "")."</p>";

            $this->add_debug_string( __("General Settings", "wp-cloudflare-page-cache"), $debug );

            $debug = "<p><b>Config:</b> ".print_r($this->config, true)."</p>";
            $this->add_debug_string( __("Config", "wp-cloudflare-page-cache"), $debug );

        }

        $cronjob_url = add_query_arg( array(
            'swcfpc' => '1',
            'swcfpc-purge-all' => '1',
        ), site_url() );

        $switch_counter  = 0;

        wp_enqueue_style( 'swcfpc_admin_css' );
        wp_enqueue_script( 'swcfpc_admin_js' );

        ?>

        <div class="wrap">

            <div id="swcfpc_main_content">

                <h1><?php _e('WP Cloudflare Super Page Cache', 'wp-cloudflare-page-cache'); ?></h1>

                <?php if($error): ?>

                    <div class="notice is-dismissible notice-error"><p><?php echo sprintf( __("Error: %s", 'wp-cloudflare-page-cache'), $error_msg ); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e("Hide this notice", 'wp-cloudflare-page-cache'); ?></span></button></div>

                <?php endif; ?>

                <?php if($success): ?>

                    <div class="notice is-dismissible notice-success"><p><?php echo $success_msg; ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e("Hide this notice", 'wp-cloudflare-page-cache'); ?></span></button></div>

                <?php endif; ?>

                <?php if( count($zone_id_list) == 0 ): ?>

                    <div class="step">

                        <div class="step_counter">
                            <div class="step_number step_active"><span>1</span></div>
                            <div class="step_number"><span>2</span></div>
                            <div class="step_number"><span>3</span></div>
                            <div class="clear"></div>
                        </div>

                        <div class="api_key_method <?php if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_KEY ) echo 'swcfpc_hide'; ?>">

                            <h2><?php echo __( 'Enter your Cloudflare\'s API key and e-mail', 'wp-cloudflare-page-cache' ); ?></h2>

                            <p><?php _e('You don\'t know how to do it? Follow these simple four steps:', 'wp-cloudflare-page-cache'); ?></p>

                            <ol>
                                <li><a href="https://dash.cloudflare.com/login" target="_blank"><?php _e('Log in to your Cloudflare account', 'wp-cloudflare-page-cache'); ?></a> <?php _e('and click on My Profile', 'wp-cloudflare-page-cache'); ?></li>
                                <li><?php _e('Click on API tokens, scroll to API Keys and click on View beside Global API Key', 'wp-cloudflare-page-cache'); ?></li>
                                <li><?php _e('Enter your Cloudflare login password and click on View', 'wp-cloudflare-page-cache'); ?></li>
                                <li><?php _e('Enter both API key and e-mail address into the form below and click on Update settings', 'wp-cloudflare-page-cache'); ?></li>
                            </ol>

                        </div>

                        <div class="api_token_method <?php if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_TOKEN ) echo 'swcfpc_hide'; ?>">

                            <h2><?php echo __( 'Enter your Cloudflare\'s API token', 'wp-cloudflare-page-cache' ); ?></h2>

                            <p><?php _e('You don\'t know how to do it? Follow these simple steps:', 'wp-cloudflare-page-cache'); ?></p>

                            <ol>
                                <li><a href="https://dash.cloudflare.com/login" target="_blank"><?php _e('Log in to your Cloudflare account', 'wp-cloudflare-page-cache'); ?></a> <?php _e('and click on My Profile', 'wp-cloudflare-page-cache'); ?></li>
                                <li><?php _e('Click on API tokens > Create Token > Custom Token > Get started', 'wp-cloudflare-page-cache'); ?></li>
                                <li><?php _e('Enter a Token name (example: token for example.com)', 'wp-cloudflare-page-cache'); ?></li>
                                <li><strong><?php _e('Permissions:', 'wp-cloudflare-page-cache'); ?></strong></li>
                                <ul>
                                    <li>Account - Account Settings - Read</li>
                                    <li>Zone - Cache Purge - Purge</li>
                                    <li>Zone - Page Rules - Edit</li>
                                    <li>Zone - Zone Settings - Edit</li>
                                    <li>Zone - Zone - Edit</li>
                                </ul>

                                <li><strong><?php _e('Account resources:', 'wp-cloudflare-page-cache'); ?></strong></li>
                                <ul>
                                    <li>Include - All accounts</li>
                                </ul>

                                <li><strong><?php _e('Zone resources:', 'wp-cloudflare-page-cache'); ?></strong></li>
                                <ul>
                                    <li>Include - All zones</li>
                                </ul>

                                <li><?php _e('Click on Continue to summary and then on Create token', 'wp-cloudflare-page-cache'); ?></li>
                                <li><?php _e('Enter the generated token into the form below and click on Update settings', 'wp-cloudflare-page-cache'); ?></li>
                            </ol>

                        </div>

                    </div>

                <?php endif; ?>

                <?php if( $this->get_single_config("cf_zoneid", "") == "" && count($zone_id_list) > 0 ): ?>

                    <div class="step">

                        <div class="step_counter">
                            <div class="step_number"><span>1</span></div>
                            <div class="step_number step_active"><span>2</span></div>
                            <div class="step_number"><span>3</span></div>
                            <div class="clear"></div>
                        </div>

                        <h2><?php echo __( 'Select the domain', 'wp-cloudflare-page-cache' ); ?></h2>

                        <p style="text-align: center;"><?php _e('Select from the dropdown menu the domain for which you want to enable the cache', 'wp-cloudflare-page-cache'); ?></p>

                    </div>

                <?php endif; ?>

                <?php if( $this->get_single_config("cf_zoneid", "") != "" ): ?>

                    <?php if( ! $this->is_cache_enabled() ): ?>

                        <div class="step">

                            <div class="step_counter">
                                <div class="step_number"><span>1</span></div>
                                <div class="step_number"><span>2</span></div>
                                <div class="step_number step_active"><span>3</span></div>
                                <div class="clear"></div>
                            </div>

                            <h2><?php _e('Enable Page Caching', 'wp-cloudflare-page-cache'); ?></h2>

                            <p style="text-align: center;"><?php _e('Now you can configure and enable the page cache to speed up this website', 'wp-cloudflare-page-cache'); ?></p>

                            <form action="" method="post">
                                <p class="submit"><input type="submit" name="swcfpc_submit_enable_page_cache" class="button button-primary green_button" value="<?php _e('Enable Page Caching Now', 'wp-cloudflare-page-cache'); ?>"></p>
                            </form>

                        </div>

                    <?php else: ?>

                        <div class="blocco_dati_header">
                            <h3><?php echo __( 'Cache Actions', 'wp-cloudflare-page-cache' ); ?></h3>
                        </div>

                    <?php endif; ?>

                    <div id="swcfpc_actions">

                        <?php if( $this->is_cache_enabled() ): ?>

                            <form action="" method="post">
                                <p class="submit"><input type="submit" name="swcfpc_submit_disable_page_cache" class="button button-primary" value="<?php _e('Disable Page Cache', 'wp-cloudflare-page-cache'); ?>"></p>
                            </form>

                        <?php endif; ?>

                        <?php if( $this->is_cache_enabled() ): ?>

                            <form action="" method="post">
                                <p class="submit"><input type="submit" name="swcfpc_submit_purge_cache" class="button button-secondary" value="<?php _e('Purge Cache', 'wp-cloudflare-page-cache'); ?>"></p>
                            </form>

                        <?php endif; ?>

                        <?php if( $this->is_cache_enabled() ): ?>

                            <form action="" method="post">
                                <p class="submit"><input type="submit" name="swcfpc_submit_test_cache" class="button button-secondary" value="<?php _e('Test Cache', 'wp-cloudflare-page-cache'); ?>"></p>
                            </form>

                        <?php endif; ?>

                        <?php if( $this->is_cache_enabled() ): ?>

                            <form id="reset_form" action="" method="post" onsubmit="return confirm('<?php _e("Are you sure you want reset all?", 'wp-cloudflare-page-cache'); ?>');">
                                <p class="submit"><input type="submit" name="swcfpc_submit_reset_all" class="button button-secondary red_button" value="<?php _e('Reset All', 'wp-cloudflare-page-cache'); ?>"></p>
                            </form>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

                <form method="post" action="">

                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Cloudflare General Settings', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Authentication mode', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('Authentication mode to use to connect to your Cloudflare account.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <select name="swcfpc_cf_auth_mode">
                                <option value="<?php echo SWCFPC_AUTH_MODE_API_TOKEN; ?>" <?php if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_TOKEN ) echo "selected"; ?>><?php _e('API Token', 'wp-cloudflare-page-cache'); ?></option>
                                <option value="<?php echo SWCFPC_AUTH_MODE_API_KEY; ?>" <?php if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_KEY ) echo "selected"; ?>><?php _e('API Key', 'wp-cloudflare-page-cache'); ?></option>
                            </select>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati api_key_method <?php if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_KEY ) echo 'swcfpc_hide'; ?>">
                        <div class="blocco_sinistra">
                            <label><?php _e('Cloudflare e-mail', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('The email address you use to log in to Cloudflare.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <input type="text" name="swcfpc_cf_email"  value="<?php echo $this->get_single_config("cf_email", ""); ?>" />
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati api_key_method <?php if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_KEY ) echo 'swcfpc_hide'; ?>">
                        <div class="blocco_sinistra">
                            <label><?php _e('Cloudflare API Key', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('The Global API Key extrapolated from your Cloudflare account.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <input type="password" name="swcfpc_cf_apikey"  value="<?php echo $this->get_single_config("cf_apikey", ""); ?>" />
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati api_token_method <?php if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) != SWCFPC_AUTH_MODE_API_TOKEN ) echo 'swcfpc_hide'; ?>">
                        <div class="blocco_sinistra">
                            <label><?php _e('Cloudflare API Token', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('The API Token extrapolated from your Cloudflare account.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <input type="password" name="swcfpc_cf_apitoken"  value="<?php echo $this->get_single_config("cf_apitoken", ""); ?>" />
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="blocco_dati">
                        <div class="blocco_sinistra">
                            <label><?php _e('Debug mode', 'wp-cloudflare-page-cache'); ?></label>
                            <div class="descrizione"><?php _e('If enabled, you will see all communications between Cloudflare and WP Cloudflare Super Page Cache at the bottom of this page.', 'wp-cloudflare-page-cache'); ?></div>
                        </div>
                        <div class="blocco_destra">
                            <div class="switch-field">
                                <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_debug" value="1" <?php if( $this->get_single_config("debug", 0) > 0 ) echo "checked";  ?>/>
                                <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Enabled", 'wp-cloudflare-page-cache'); ?></label>
                                <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_debug" value="0" <?php if( $this->get_single_config("debug", 0) <= 0 ) echo "checked";  ?> />
                                <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("Disabled", 'wp-cloudflare-page-cache'); ?></label>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <?php if( count($zone_id_list) > 0 ): ?>

                        <div class="blocco_dati">
                            <div class="blocco_sinistra">
                                <label><?php _e('Cloudflare Domain Name', 'wp-cloudflare-page-cache'); ?></label>
                                <div class="descrizione"><?php _e('Select the domain for which you want to enable the cache and click on Update settings.', 'wp-cloudflare-page-cache'); ?></div>
                            </div>
                            <div class="blocco_destra">

                                <select name="swcfpc_cf_zoneid">

                                    <option value=""><?php _e('Select a Domain Name', 'wp-cloudflare-page-cache'); ?></option>

                                    <?php if( $domain_found ): ?>

                                        <option value="<?php echo $domain_zone_id; ?>" <?php if( $domain_zone_id == $this->get_single_config("cf_zoneid", "") ) echo "selected"; ?>><?php echo $current_domain; ?></option>

                                    <?php else: foreach($zone_id_list as $zone_id_name => $zone_id): ?>

                                        <option value="<?php echo $zone_id; ?>" <?php if( $zone_id == $this->get_single_config("cf_zoneid", "") ) echo "selected"; ?>><?php echo $zone_id_name; ?></option>

                                    <?php endforeach; endif; ?>

                                </select>

                            </div>
                            <div class="clear"></div>
                        </div>


                        <div class="blocco_dati">
                            <div class="blocco_sinistra">
                                <label><?php _e('Subdomain', 'wp-cloudflare-page-cache'); ?></label>
                                <div class="descrizione"><?php _e('If you want to enable the cache for a subdomain of the selected domain, enter it here. For example, if you selected the domain example.com from the drop-down menu and you want to enable the cache for subdomain.example.com, enter subdomain.example.com here.', 'wp-cloudflare-page-cache'); ?></div>
                            </div>
                            <div class="blocco_destra">
                                <input type="text" name="swcfpc_cf_subdomain"  value="<?php echo $this->get_single_config("cf_subdomain", ""); ?>" />
                            </div>
                            <div class="clear"></div>
                        </div>

                        <?php if( $this->get_single_config("cf_zoneid", "") != "" ): ?>

                            <div class="blocco_dati_header">
                                <h3><?php echo __( 'Cache lifetime settings', 'wp-cloudflare-page-cache' ); ?></h3>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Cloudflare Cache-Control max-age', 'wp-cloudflare-page-cache'); ?></label>
                                    <div class="descrizione"><?php _e('Don\'t touch if you don\'t know what is it. Must be grater than zero. Recommended 604800', 'wp-cloudflare-page-cache'); ?></div>
                                </div>
                                <div class="blocco_destra">
                                    <input type="text" name="swcfpc_maxage"  value="<?php echo $this->get_single_config("cf_maxage", ""); ?>" />
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Browser Cache-Control max-age', 'wp-cloudflare-page-cache'); ?></label>
                                    <div class="descrizione"><?php _e('Don\'t touch if you don\'t know what is it. Must be grater than zero. Recommended a value between 60 and 600', 'wp-cloudflare-page-cache'); ?></div>
                                </div>
                                <div class="blocco_destra">
                                    <input type="text" name="swcfpc_browser_maxage"  value="<?php echo $this->get_single_config("browser_maxage", ""); ?>" />
                                </div>
                                <div class="clear"></div>
                            </div>


                            <div class="blocco_dati_header">
                                <h3><?php echo __( 'Cache behavior settings', 'wp-cloudflare-page-cache' ); ?></h3>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Posts per page', 'wp-cloudflare-page-cache'); ?></label>
                                    <div class="descrizione"><?php _e('Enter how many posts per page (or category) the theme shows to your users. It will be use to clean up the pagination on cache purge.', 'wp-cloudflare-page-cache'); ?></div>
                                </div>
                                <div class="blocco_destra">
                                    <input type="text" name="swcfpc_post_per_page"  value="<?php echo $this->get_single_config("cf_post_per_page", ""); ?>" />
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Overwrite the cache-control header for Wordpress\'s pages using web server rules', 'wp-cloudflare-page-cache'); ?></label>
                                    <div class="descrizione"><?php _e('This option is useful if you use WP Cloudflare Super Page Cache together with other performance plugins that could affect the Cloudflare cache with their cache-control headers. It works automatically if you are using Apache as web server or as backend web server.', 'wp-cloudflare-page-cache'); ?></div>
                                    <br/>
                                    <div class="descrizione"><strong><?php _e('Read here if you use Apache (htaccess)', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e('for overwriting to work, make sure that the rules added by WP Cloudflare Super Page Cache are placed at the bottom of the htaccess file. If they are present BEFORE other caching rules of other plugins, move them to the bottom manually.', 'wp-cloudflare-page-cache'); ?></div>
                                    <br/>
                                    <div class="descrizione"><strong><?php _e('Read here if you only use Nginx', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e( 'it is not possible for WP Cloudflare Super Page Cache to automatically change the settings to allow this option to work immediately. For it to work, update these settings and then follow the instructions', 'wp-cloudflare-page-cache'); ?> <a href="<?php echo $nginx_instructions_page_url; ?>" target="_blank"><?php _e('on this page', 'wp-cloudflare-page-cache'); ?>.</a></div>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_cache_control_htaccess" value="1" <?php if( $this->get_single_config("cf_cache_control_htaccess", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_cache_control_htaccess" value="0" <?php if( $this->get_single_config("cf_cache_control_htaccess", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Automatically purge the Cloudflare cache on website changes (posts, pages, themes, attachments, etc..)', 'wp-cloudflare-page-cache'); ?></label>
                                    <div class="descrizione"><?php _e('If enabled, WP Cloudflare Super Page Cache tries to purge the cache for related pages only.', 'wp-cloudflare-page-cache'); ?></div>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_auto_purge" value="1" <?php if( $this->get_single_config("cf_auto_purge", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_auto_purge" value="0" <?php if( $this->get_single_config("cf_auto_purge", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Automatically purge the whole Cloudflare cache on website changes (posts, pages, themes, attachments, etc..)', 'wp-cloudflare-page-cache'); ?></label>
                                    <div class="descrizione"><?php _e('If enabled, WP Cloudflare Super Page Cache will purge the whole Cloudflare cache.', 'wp-cloudflare-page-cache'); ?></div>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_auto_purge_all" value="1" <?php if( $this->get_single_config("cf_auto_purge_all", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_auto_purge_all" value="0" <?php if( $this->get_single_config("cf_auto_purge_all", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>


                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Don\'t cache the following page types', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_404" value="1" <?php echo $this->get_single_config("cf_bypass_404", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Page 404 (is_404)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_single_post" value="1" <?php echo $this->get_single_config("cf_bypass_single_post", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Single Posts (is_single)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_pages" value="1" <?php echo $this->get_single_config("cf_bypass_pages", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Pages (is_page)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_front_page" value="1" <?php echo $this->get_single_config("cf_bypass_front_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Front Page (is_front_page)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_home" value="1" <?php echo $this->get_single_config("cf_bypass_home", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Home (is_home)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_archives" value="1" <?php echo $this->get_single_config("cf_bypass_archives", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Archives (is_archive)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_tags" value="1" <?php echo $this->get_single_config("cf_bypass_tags", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Tags (is_tag)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_category" value="1" <?php echo $this->get_single_config("cf_bypass_category", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Categories (is_category)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_feeds" value="1" <?php echo $this->get_single_config("cf_bypass_feeds", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Feeds (is_feed)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_search_pages" value="1" <?php echo $this->get_single_config("cf_bypass_search_pages", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Search Pages (is_search)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_author_pages" value="1" <?php echo $this->get_single_config("cf_bypass_author_pages", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Author Pages (is_author)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_amp" value="1" <?php echo $this->get_single_config("cf_bypass_amp", 0) > 0 ? "checked" : ""; ?> /> <?php _e('AMP Pages', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Prevent the following urls to be cached', 'wp-cloudflare-page-cache'); ?></label>
                                    <div class="descrizione"><?php _e('One URL per line. You can use the * for wildcard URLs.', 'wp-cloudflare-page-cache'); ?></div>
                                </div>
                                <div class="blocco_destra">
                                    <textarea name="swcfpc_cf_excluded_urls"><?php echo is_array( $this->get_single_config("cf_excluded_urls", "") ) ? implode("\n", $this->get_single_config("cf_excluded_urls", "") ) : ""; ?></textarea>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Prevent XML sitemaps to be cached', 'wp-cloudflare-page-cache'); ?></label>
                                    <br/><br/>
                                    <div class="descrizione"><strong><?php _e('If you only use Nginx', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e( 'it is recommended to add the browser caching rules that you find', 'wp-cloudflare-page-cache'); ?> <a href="<?php echo $nginx_instructions_page_url; ?>" target="_blank"><?php _e('on this page', 'wp-cloudflare-page-cache'); ?></a> <?php _e('after saving these settings', 'wp-cloudflare-page-cache'); ?>.</div>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_sitemap" value="1" <?php if( $this->get_single_config("cf_bypass_sitemap", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_sitemap" value="0" <?php if( $this->get_single_config("cf_bypass_sitemap", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Prevent robots.txt to be cached', 'wp-cloudflare-page-cache'); ?></label>
                                    <br/><br/>
                                    <div class="descrizione"><strong><?php _e('If you only use Nginx', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e( 'it is recommended to add the browser caching rules that you find', 'wp-cloudflare-page-cache'); ?> <a href="<?php echo $nginx_instructions_page_url; ?>" target="_blank"><?php _e('on this page', 'wp-cloudflare-page-cache'); ?></a> <?php _e('after saving these settings', 'wp-cloudflare-page-cache'); ?>.</div>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_file_robots" value="1" <?php if( $this->get_single_config("cf_bypass_file_robots", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_file_robots" value="0" <?php if( $this->get_single_config("cf_bypass_file_robots", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Bypass the cache for logged-in users', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_logged_in" value="1" <?php if( $this->get_single_config("cf_bypass_logged_in", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_logged_in" value="0" <?php if( $this->get_single_config("cf_bypass_logged_in", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Bypass the cache for AJAX requests', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_bypass_ajax" value="1" <?php if( $this->get_single_config("cf_bypass_ajax", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_bypass_ajax" value="0" <?php if( $this->get_single_config("cf_bypass_ajax", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>


                            <div class="blocco_dati_header">
                                <h3><?php echo __( 'Browser caching', 'wp-cloudflare-page-cache' ); ?></h3>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Add browser caching rules for assets', 'wp-cloudflare-page-cache'); ?></label>
                                    <div class="descrizione"><?php _e('This option is useful if you want to use WP Cloudflare Super Page Cache to enable browser caching rules for assets such like images, CSS, scripts, etc. It works automatically if you use Apache as web server or as backend web server.', 'wp-cloudflare-page-cache'); ?></div>
                                    <br/>
                                    <div class="descrizione"><strong><?php _e('Read here if you only use Nginx', 'wp-cloudflare-page-cache'); ?></strong>: <?php _e( 'it is not possible for WP Cloudflare Super Page Cache to automatically change the settings to allow this option to work immediately. For it to work, update these settings and then follow the instructions', 'wp-cloudflare-page-cache'); ?> <a href="<?php echo $nginx_instructions_page_url; ?>" target="_blank"><?php _e('on this page', 'wp-cloudflare-page-cache'); ?>.</a></div>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_browser_caching_htaccess" value="1" <?php if( $this->get_single_config("cf_browser_caching_htaccess", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_browser_caching_htaccess" value="0" <?php if( $this->get_single_config("cf_browser_caching_htaccess", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>


                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Automatically purge single post cache when a new comment is inserted into the database or when a comment is approved or deleted', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <div class="switch-field">
                                        <input type="radio" id="switch_<?php echo ++$switch_counter; ?>_left" name="swcfpc_cf_auto_purge_on_comments" value="1" <?php if( $this->get_single_config("cf_auto_purge_on_comments", 0) > 0 ) echo "checked";  ?>/>
                                        <label for="switch_<?php echo $switch_counter; ?>_left"><?php _e("Yes", 'wp-cloudflare-page-cache'); ?></label>
                                        <input type="radio" id="switch_<?php echo $switch_counter; ?>_right" name="swcfpc_cf_auto_purge_on_comments" value="0" <?php if( $this->get_single_config("cf_auto_purge_on_comments", 0) <= 0 ) echo "checked";  ?> />
                                        <label for="switch_<?php echo $switch_counter; ?>_right"><?php _e("No", 'wp-cloudflare-page-cache'); ?></label>
                                    </div>
                                </div>
                                <div class="clear"></div>
                            </div>


                            <!-- WooCommerce Options -->
                            <div class="blocco_dati_header">
                                <h3><?php echo __( 'WooCommerce settings', 'wp-cloudflare-page-cache' ); ?></h3>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Plugin status', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <?php if( is_plugin_active( 'woocommerce/woocommerce.php' ) ): ?>
                                        <p class="plugin_active"><?php _e('Active', 'wp-cloudflare-page-cache'); ?></p>
                                    <?php else: ?>
                                        <p class="plugin_inactive"><?php _e('Inactive', 'wp-cloudflare-page-cache'); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Don\'t cache the following WooCommerce page types', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_cart_page" value="1" <?php echo $this->get_single_config("cf_bypass_woo_cart_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Cart (is_cart)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_checkout_page" value="1" <?php echo $this->get_single_config("cf_bypass_woo_checkout_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Checkout (is_checkout)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_checkout_pay_page" value="1" <?php echo $this->get_single_config("cf_bypass_woo_checkout_pay_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Checkout\'s pay page (is_checkout_pay_page)', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_product_page" value="1" <?php echo $this->get_single_config("cf_bypass_woo_product_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Product (is_product)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_shop_page" value="1" <?php echo $this->get_single_config("cf_bypass_woo_shop_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Shop (is_shop)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_product_tax_page" value="1" <?php echo $this->get_single_config("cf_bypass_woo_product_tax_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Product taxonomy (is_product_taxonomy)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_product_tag_page" value="1" <?php echo $this->get_single_config("cf_bypass_woo_product_tag_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Product tag (is_product_tag)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_product_cat_page" value="1" <?php echo $this->get_single_config("cf_bypass_woo_product_cat_page", 0) > 0 ? "checked" : ""; ?> /> <?php _e('Product category (is_product_category)', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_bypass_woo_pages" value="1" <?php echo $this->get_single_config("cf_bypass_woo_pages", 0) > 0 ? "checked" : ""; ?> /> <?php _e('WooCommerce page (is_woocommerce)', 'wp-cloudflare-page-cache'); ?></div>
                                </div>
                                <div class="clear"></div>
                            </div>


                            <!-- W3TC Options -->
                            <div class="blocco_dati_header">
                                <h3><?php echo __( 'W3 Total Cache settings', 'wp-cloudflare-page-cache' ); ?></h3>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Plugin status', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <?php if( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ): ?>
                                        <p class="plugin_active"><?php _e('Active', 'wp-cloudflare-page-cache'); ?></p>
                                    <?php else: ?>
                                        <p class="plugin_inactive"><?php _e('Inactive', 'wp-cloudflare-page-cache'); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Automatically purge the cache when', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_all" value="1" <?php echo $this->get_single_config("cf_w3tc_purge_on_flush_all", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs all caches', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                    <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_dbcache" value="1" <?php echo $this->get_single_config("cf_w3tc_purge_on_flush_dbcache", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs database cache', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_fragmentcache" value="1" <?php echo $this->get_single_config("cf_w3tc_purge_on_flush_fragmentcache", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs fragment cache', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_objectcache" value="1" <?php echo $this->get_single_config("cf_w3tc_purge_on_flush_objectcache", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs object cache', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_posts" value="1" <?php echo $this->get_single_config("cf_w3tc_purge_on_flush_posts", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs posts cache', 'wp-cloudflare-page-cache'); ?></div>
                                    <div><input type="checkbox" name="swcfpc_cf_w3tc_purge_on_flush_minfy" value="1" <?php echo $this->get_single_config("cf_w3tc_purge_on_flush_minfy", 0) > 0 ? "checked" : ""; ?> /> <?php _e('W3TC flushs minify cache', 'wp-cloudflare-page-cache'); ?></div>
                                </div>
                                <div class="clear"></div>
                            </div>


                            <!-- WP Rocket Options -->
                            <div class="blocco_dati_header">
                                <h3><?php echo __( 'WP Rocket settings', 'wp-cloudflare-page-cache' ); ?></h3>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Plugin status', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <?php if( is_plugin_active( 'wp-rocket/wp-rocket.php' ) ): ?>
                                        <p class="plugin_active"><?php _e('Active', 'wp-cloudflare-page-cache'); ?></p>
                                    <?php else: ?>
                                        <p class="plugin_inactive"><?php _e('Inactive', 'wp-cloudflare-page-cache'); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Automatically purge the cache when', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <div><input type="checkbox" name="swcfpc_cf_wp_rocket_purge_on_post_flush" value="1" <?php echo $this->get_single_config("cf_wp_rocket_purge_on_post_flush", 0) > 0 ? "checked" : ""; ?> /> <?php _e('WP Rocket flushs all caches', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                    <div><input type="checkbox" name="swcfpc_cf_wp_rocket_purge_on_domain_flush" value="1" <?php echo $this->get_single_config("cf_wp_rocket_purge_on_domain_flush", 0) > 0 ? "checked" : ""; ?> /> <?php _e('WP Rocket flushs single post cache', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                </div>
                                <div class="clear"></div>
                            </div>


                            <!-- WP Super Cache Options -->
                            <div class="blocco_dati_header">
                                <h3><?php echo __( 'WP Super Cache settings', 'wp-cloudflare-page-cache' ); ?></h3>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Plugin status', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <?php if( is_plugin_active( 'wp-super-cache/wp-cache.php' ) ): ?>
                                        <p class="plugin_active"><?php _e('Active', 'wp-cloudflare-page-cache'); ?></p>
                                    <?php else: ?>
                                        <p class="plugin_inactive"><?php _e('Inactive', 'wp-cloudflare-page-cache'); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="clear"></div>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Automatically purge the cache when', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <div><input type="checkbox" name="swcfpc_cf_wp_super_cache_on_cache_flush" value="1" <?php echo $this->get_single_config("cf_wp_super_cache_on_cache_flush", 0) > 0 ? "checked" : ""; ?> /> <?php _e('WP Super Cache flushs all caches', 'wp-cloudflare-page-cache'); ?> - <strong><?php _e('(recommended)', 'wp-cloudflare-page-cache'); ?></strong></div>
                                </div>
                                <div class="clear"></div>
                            </div>


                            <div class="blocco_dati_header">
                                <h3><?php echo __( 'Other settings', 'wp-cloudflare-page-cache' ); ?></h3>
                            </div>

                            <div class="blocco_dati">
                                <div class="blocco_sinistra">
                                    <label><?php _e('Purge the whole Cloudflare cache with a Cronjob', 'wp-cloudflare-page-cache'); ?></label>
                                </div>
                                <div class="blocco_destra">
                                    <p><?php _e('If you want purge the whole Cloudflare cache at specific intervals decided by you, you can create a cronjob that hits the following URL', 'wp-cloudflare-page-cache'); ?>:</p>
                                    <p><b><?php echo $cronjob_url; ?></b></p>
                                </div>
                                <div class="clear"></div>
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                    <p class="submit"><input type="submit" name="swcfpc_submit_general" class="button button-primary" value="<?php _e('Update settings', 'wp-cloudflare-page-cache'); ?>"></p>

                </form>


                <?php if( $this->get_single_config("debug", 0) == 1 && $this->debug_msg != "" ): ?>

                    <div id="debug">

                        <h2><?php _e('Debug info', 'wp-cloudflare-page-cache'); ?></h2>

                        <p><?php echo $this->debug_msg; ?></p>

                    </div>

                <?php endif; ?>


            </div>

            <div id="swcfpc_sidebar">

                <h2><?php _e("About the author and support", 'wp-cloudflare-page-cache'); ?></h2>

                <p><?php _e('My name is Salvatore Fresta and I\'m an italian web performance specialist and a senior developer. I\'m the founder of the first italian blog about Wordpress performance', 'wp-cloudflare-page-cache'); ?> <a href="https://www.speedywp.it" target="_blank">speedywp.it</a> <?php _e('and the co-founder of the italian agency ', 'wp-cloudflare-page-cache'); ?> <a href="https://www.squeezemind.it" target="_blank">SqueezeMind</a>.</p>
                <p><?php _e('If you have any issues with this plugin, drop me a line via email to salvatorefresta [at] gmail.com', 'wp-cloudflare-page-cache'); ?>.</p>

                <a href="https://www.speedywp.it" target="_blank"><img src="<?php echo plugins_url( '/assets/img/speedy-wp.jpg', __FILE__ ); ?>" alt="Speedy Wordpress" /></a>
                <a href="https://www.squeezemind.it" target="_blank"><img src="<?php echo plugins_url( '/assets/img/squeezemind.jpg', __FILE__ ); ?>" alt="SqueezeMind" /></a>

            </div>


        </div>

        <?php

    }


    function admin_menu_page_nginx_settings() {

        if( !current_user_can("manage_options") ) {
            die( __("Permission denied", 'wp-cloudflare-page-cache') );
        }

        $nginx_lines = array();

        if( $this->get_single_config("cf_bypass_sitemap", 0) > 0 ) {
            $nginx_lines[] = "location ~* \.(xml)$ { expires -1; }";
        }

        if( $this->get_single_config("cf_bypass_file_robots", 0) > 0 ) {
            $nginx_lines[] = "location /robots.txt { expires -1; }";
        }

        if( $this->get_single_config("cf_browser_caching_htaccess", 0) > 0 ) {

            $nginx_lines[] = "location ~* \.(css|js)$ { expires 365d; }";
            $nginx_lines[] = "location ~* \.(jpg|jpeg|png|gif|ico|svg|webp)$ { expires 180d; }";
            $nginx_lines[] = "location ~* \.(ogg|mp4|mpeg|avi|mkv|webm|mp3)$ { expires 30d; }";
            $nginx_lines[] = "location ~* \.(ttf|otf|woff|woff2)$ { expires 120d; }";
            $nginx_lines[] = "location ~* \.(pdf)$ { expires 30d; }";
            $nginx_lines[] = "location ~* \.(json)$ { expires -1; }";

            if( $this->get_single_config("cf_bypass_sitemap", 0) == 0 )
                $nginx_lines[] = "location ~* \.(xml)$ { expires -1; }";

        }

        wp_enqueue_style( 'swcfpc_admin_css' );

        ?>

        <div class="wrap">

            <div id="swcfpc_main_content">

                <h1><?php _e('WP Cloudflare Super Page Cache - Nginx Settings', 'wp-cloudflare-page-cache'); ?></h1>

                <?php if( $this->get_single_config("cf_cache_control_htaccess", 0) > 0 ): ?>

                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Overwrite the cache-control header', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <p><?php echo __( 'Edit the main Nginx configuration file, usually /etc/nginx.conf, and enter these rules immediately after opening the http block:', 'wp-cloudflare-page-cache' ); ?></p>

                <strong><pre>
map $upstream_http_x_wp_cf_super_cache_active $wp_cf_super_cache_active {
    default  'no-cache, no-store, max-age=0';
    '1' '<?php echo $this->get_cache_control_value(); ?>';
}
                    </pre></strong>

                    <p><?php echo __( 'Now open the configuration file of your domain and add the following rules inside the block that deals with the management of PHP pages:', 'wp-cloudflare-page-cache' ); ?></p>

                    <strong><pre>
more_clear_headers 'Pragma';
more_clear_headers 'Expires';
more_clear_headers 'Cache-Control';
add_header Cache-Control $wp_cf_super_cache_active;
                        </pre></strong>

                    <p><?php echo __( 'Save and restart Nginx.', 'wp-cloudflare-page-cache' ); ?></p>

                <?php endif; ?>

                <?php if( count($nginx_lines) > 0 ): ?>

                    <div class="blocco_dati_header">
                        <h3><?php echo __( 'Browser caching rules', 'wp-cloudflare-page-cache' ); ?></h3>
                    </div>

                    <p><?php echo __( 'Open the configuration file of your domain and add the following rules:', 'wp-cloudflare-page-cache' ); ?></p>

                    <strong><pre>
<?php foreach ($nginx_lines as $single_nginx_line) echo "$single_nginx_line\n"; ?>
                    </pre></strong>

                    <p><?php echo __( 'Save and restart Nginx.', 'wp-cloudflare-page-cache' ); ?></p>

                <?php endif; ?>

            </div>

        </div>

        <?php

    }


    function add_debug_string($title, $content) {

        $this->debug_msg .= "<hr>";
        $this->debug_msg .= "<br><h2>$title</h2><div>$content</div>";

    }


    function cloudflare_page_cache_test( &$error ) {

        $url = plugins_url( '/assets/testcache.html', __FILE__ );

        // First test
        $response = wp_remote_get( esc_url_raw( $url ) );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        $headers = wp_remote_retrieve_headers( $response );

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_page_cache_test - Request 1", "<p><b>URL:</b> $url</p>" );
            $this->add_debug_string("cloudflare_page_cache_test - Response 1", "<p><b>Headers:</b> ".var_export($headers, true)."</p>" );
        }

        // Second test
        if( isset($headers['Set-Cookie']) ) {

            $response = wp_remote_get( esc_url_raw( $url ), array("headers" => array( "Cookie" => $headers['Set-Cookie']) ) );

            if ( is_wp_error( $response ) ) {
                $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
                return false;
            }

            $headers = wp_remote_retrieve_headers( $response );

        }
        else {

            $response = wp_remote_get( esc_url_raw( $url ) );

            if ( is_wp_error( $response ) ) {
                $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
                return false;
            }

            $headers = wp_remote_retrieve_headers( $response );

        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_page_cache_test - Request 2", "<p><b>URL:</b> $url</p>" );
            $this->add_debug_string("cloudflare_page_cache_test - Response 2", "<p><b>Headers:</b> ".var_export($headers, true)."</p>" );
        }

        if( !isset($headers["CF-Cache-Status"]) ) {
            $error = __('The cache doesn\'t seem to work. If you have recently enabled the cache or it is your first test, wait about 30 seconds and try again because the changes take a few seconds for Cloudflare to propagate them on the web. If the error persists, request support for a detailed check.', 'wp-cloudflare-page-cache');
            return false;
        }

        if( strcasecmp($headers["CF-Cache-Status"], "HIT") != 0 ) {
            $error = sprintf( __('Cache status: %s - Try again', 'wp-cloudflare-page-cache'), $headers["CF-Cache-Status"]);
            return false;
        }

        return true;

    }


    function get_cloudflare_api_headers() {

        $cf_headers = array();

        if( $this->get_single_config("cf_auth_mode", SWCFPC_AUTH_MODE_API_KEY) == SWCFPC_AUTH_MODE_API_TOKEN ) {

            $cf_headers = array(
                "headers" => array(
                    "Authorization" => "Bearer ".$this->get_single_config("cf_apitoken", ""),
                    "Content-Type" => "application/json"
                )
            );

        }
        else {

            $cf_headers = array(
                "headers" => array(
                    "X-Auth-Email" => $this->get_single_config("cf_email", ""),
                    "X-Auth-Key"   => $this->get_single_config("cf_apikey", ""),
                    "Content-Type" => "application/json"
                )
            );

        }

        return $cf_headers;

    }


    function cloudflare_get_zone_ids(&$error) {

        $zone_id_list = array();
        $per_page     = 50;
        $current_page = 1;
        $pagination   = false;
        $cf_headers   = $this->get_cloudflare_api_headers();

        do {

            if( $this->debug_enabled ) {
                $this->add_debug_string("cloudflare_get_zone_ids - Request for page $current_page", "<p><b>URL:</b> ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones?page=$current_page&per_page=$per_page" )."</p>" );
            }

            $response = wp_remote_get(
                esc_url_raw( "https://api.cloudflare.com/client/v4/zones?page=$current_page&per_page=$per_page" ),
                $cf_headers
            );

            if ( is_wp_error( $response ) ) {
                $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
                return false;
            }

            if( $this->debug_enabled ) {
                $this->add_debug_string("cloudflare_get_zone_ids - Response for page $current_page", wp_remote_retrieve_body($response) );
            }

            $json = json_decode( wp_remote_retrieve_body($response), true);

            if( $json["success"] == false ) {

                $error = array();

                foreach($json["errors"] as $single_error) {
                    $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
                }

                $error = implode(" - ", $error);

                return false;

            }

            if( isset($json["result_info"]) && is_array($json["result_info"]) ) {

                if( isset($json["result_info"]["total_pages"]) && intval($json["result_info"]["total_pages"]) > $current_page ) {
                    $pagination = true;
                    $current_page++;
                }
                else {
                    $pagination = false;
                }

            }
            else {

                if( $pagination )
                    $pagination = false;

            }

            if( isset($json["result"]) && is_array($json["result"]) ) {

                foreach( $json["result"] as $domain_data ) {

                    if( !isset($domain_data["name"]) || !isset($domain_data["id"]) ) {
                        $error = __("Unable to retrive zone id due to invalid response data", 'wp-cloudflare-page-cache');
                        return false;
                    }

                    $zone_id_list[$domain_data["name"]] = $domain_data["id"];

                }

            }


        } while( $pagination );


        if( !count($zone_id_list) ) {
            $error = __("Unable to find domains configured on Cloudflare", 'wp-cloudflare-page-cache');
            return false;
        }

        $this->set_single_config("cf_zoneid_list", $zone_id_list);
        $this->update_config();

        return true;


    }


    function cloudflare_get_browser_cache_ttl(&$error) {

        $zone_id = $this->get_single_config("cf_zoneid", "");
        $cf_headers = $this->get_cloudflare_api_headers();

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_get_browser_cache_ttl - Request", "<p><b>URL:</b> ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/settings/browser_cache_ttl" )."</p>" );
        }

        $response = wp_remote_get(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/settings/browser_cache_ttl" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_get_browser_cache_ttl - Response", wp_remote_retrieve_body($response) );
        }

        $json = json_decode( wp_remote_retrieve_body($response), true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        if( isset($json["result"]) && is_array($json["result"]) && isset($json["result"]["value"]) ) {
            return $json["result"]["value"];
        }

        $error = __("Unable to find Browser Cache TTL settings ", 'wp-cloudflare-page-cache');
        return false;

    }


    function cloudflare_set_browser_cache_ttl($ttl, &$error) {

        $zone_id = $this->get_single_config("cf_zoneid", "");
        $cf_headers = $this->get_cloudflare_api_headers();

        $cf_headers["method"] = "PATCH";
        $cf_headers["body"] = json_encode( array("value" => $ttl) );

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_set_browser_cache_ttl - Request", "<p><b>URL:</b> ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/settings/browser_cache_ttl" )."</p><p><b>Body:</b> ".json_encode( array("value" => $ttl) )."</p>" );
        }

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/settings/browser_cache_ttl" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_set_browser_cache_ttl - Response", wp_remote_retrieve_body($response) );
        }

        $json = json_decode( wp_remote_retrieve_body($response), true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        return true;

    }


    function cloudflare_delete_page_rule(&$error) {

        $page_rule_id = $this->get_single_config("cf_page_rule_id", "");
        $zone_id      = $this->get_single_config("cf_zoneid", "");
        $cf_headers   = $this->get_cloudflare_api_headers();

        $cf_headers["method"] = "DELETE";

        if( $page_rule_id == "" ) {
            $error = __("There is not page rule to delete", 'wp-cloudflare-page-cache');
            return false;
        }

        if( $zone_id == "" ) {
            $error = __("There is not zone id to use", 'wp-cloudflare-page-cache');
            return false;
        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_delete_page_rule - Request", "<p><b>URL:</b> ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/pagerules/$page_rule_id" )."</p>" );
        }

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/pagerules/$page_rule_id" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_delete_page_rule - Response", wp_remote_retrieve_body($response) );
        }

        $json = json_decode( wp_remote_retrieve_body($response), true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        return true;

    }


    function cloudflare_set_page_rule(&$error) {

        $zone_id = $this->get_single_config("cf_zoneid", "");
        $subdomain = $this->get_single_config("cf_subdomain", "");
        $cf_headers = $this->get_cloudflare_api_headers();

        if( $subdomain != "" && preg_match( "/([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-]+)\.([a-zA-Z0-9])+/", $subdomain ) ) {
            $url = "$subdomain/*";
        }
        else {
            $url = site_url("/*");
        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_set_page_rule - Request", "<p><b>URL:</b> ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/pagerules" )."</p><p><b>Body:</b> ".json_encode( array("targets" => array(array("target" => "url", "constraint" => array("operator" => "matches", "value" => $url))), "actions" => array(array("id" => "cache_level", "value" => "cache_everything")), "priority" => 1, "status" => "active") )."</p>" );
        }

        $cf_headers["method"] = "POST";
        $cf_headers["body"] = json_encode( array("targets" => array(array("target" => "url", "constraint" => array("operator" => "matches", "value" => $url))), "actions" => array(array("id" => "cache_level", "value" => "cache_everything")), "priority" => 1, "status" => "active") );

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/pagerules" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_set_page_rule - Response", wp_remote_retrieve_body($response) );
        }

        $json = json_decode( wp_remote_retrieve_body($response), true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        if( isset($json["result"]) && is_array($json["result"]) && isset($json["result"]["id"]) ) {
            $this->set_single_config("cf_page_rule_id", $json["result"]["id"]);
            $this->update_config();
            return true;
        }

        return false;

    }


    function cloudflare_purge_cache(&$error) {

        do_action("swcfpc_cf_purge_whole_cache_before");

        $zone_id = $this->get_single_config("cf_zoneid", "");
        $cf_headers = $this->get_cloudflare_api_headers();

        $cf_headers["method"] = "POST";
        $cf_headers["body"] = json_encode( array( "purge_everything" => true ) );

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_purge_cache - Request", "<p><b>URL:</b> ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/purge_cache" )."</p><p><b>Body:</b> ".json_encode( array( "purge_everything" => true ) )."</p>" );
        }

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/purge_cache" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_purge_cache - Response", wp_remote_retrieve_body($response) );
        }

        $json = json_decode( wp_remote_retrieve_body($response), true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        do_action("swcfpc_cf_purge_whole_cache_after");

        return true;

    }


    function cloudflare_purge_cache_urls($urls, &$error) {

        do_action("swcfpc_cf_purge_cache_by_urls_before");

        $zone_id = $this->get_single_config("cf_zoneid", "");
        $cf_headers = $this->get_cloudflare_api_headers();

        $cf_headers["method"] = "POST";
        $cf_headers["body"] = json_encode( array( "files" => $urls ) );

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_purge_cache_urls - Request", "<p><b>URL:</b> ".esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/purge_cache" )."</p><p><b>Body:</b> ".json_encode( array( "files" => $urls ) )."</p>" );
        }

        $response = wp_remote_post(
            esc_url_raw( "https://api.cloudflare.com/client/v4/zones/$zone_id/purge_cache" ),
            $cf_headers
        );

        if ( is_wp_error( $response ) ) {
            $error = __('Connection error: ', 'wp-cloudflare-page-cache' ).$response->get_error_message();
            return false;
        }

        if( $this->debug_enabled ) {
            $this->add_debug_string("cloudflare_purge_cache_urls - Response", wp_remote_retrieve_body($response) );
        }

        $json = json_decode( wp_remote_retrieve_body($response), true);

        if( $json["success"] == false ) {

            $error = array();

            foreach($json["errors"] as $single_error) {
                $error[] = $single_error["message"]." (err code: ".$single_error["code"]." )";
            }

            $error = implode(" - ", $error);

            return false;

        }

        do_action("swcfpc_cf_purge_cache_by_urls_after");

        return true;

    }


    function inject_js_code() {

        $selectors = "a";

        if( is_admin() )
            $selectors = "#wp-admin-bar-my-sites-list a, #wp-admin-bar-site-name a, #wp-admin-bar-view-site a, #wp-admin-bar-view a, .row-actions a, .preview, #sample-permalink a, #message a, #editor .is-link, #editor .editor-post-preview, #editor .editor-post-permalink__link";

        ?>

        <script id="swcfpc" data-cfasync="false">

            function swcfpc_adjust_internal_links( selectors_txt ) {

                var comp = new RegExp(location.host);

                [].forEach.call(document.querySelectorAll( selectors_txt ), function(el) {

                    if( comp.test( el.href ) && !el.href.includes("swcfpc=1") ) {

                        if( el.href.indexOf('#') != -1 ) {

                            var link_split = el.href.split("#");
                            el.href = link_split[0];
                            el.href += (el.href.indexOf('?') != -1 ? "&swcfpc=1" : "?swcfpc=1");
                            el.href += "#"+link_split[1];

                        }
                        else {
                            el.href += (el.href.indexOf('?') != -1 ? "&swcfpc=1" : "?swcfpc=1");
                        }

                    }

                });

            }

            document.addEventListener("DOMContentLoaded", function() {

                swcfpc_adjust_internal_links("<?php echo $selectors; ?>");

            });

			window.addEventListener("load", function() {

                swcfpc_adjust_internal_links("<?php echo $selectors; ?>");

			});

            setInterval(function(){ swcfpc_adjust_internal_links("<?php echo $selectors; ?>"); }, 3000);

        </script>

        <?php

    }
    
    
    function is_url_to_bypass() {

        // Bypass AMP
        if( $this->get_single_config("cf_bypass_amp", 0) > 0 && preg_match("/(\/amp\/page\/[0-9]*)|(\/amp\/?)/", $_SERVER['REQUEST_URI']) ) {
            return true;
        }

        // Bypass sitemap
        if( $this->get_single_config("cf_bypass_sitemap", 0) > 0 && strcasecmp($_SERVER['REQUEST_URI'], "/sitemap_index.xml") == 0 || preg_match("/[a-zA-Z0-9]-sitemap.xml$/", $_SERVER['REQUEST_URI']) ) {
            return true;
        }

        // Bypass robots.txt
        if( $this->get_single_config("cf_bypass_file_robots", 0) > 0 && preg_match("/^\/robots.txt/", $_SERVER['REQUEST_URI']) ) {
            return true;
        }

        // Bypass the cache on excluded URLs
        $excluded_urls = $this->get_single_config("cf_excluded_urls", "");

        if( is_array($excluded_urls) && count($excluded_urls) > 0 ) {

            global $post;

            $current_url = "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

            foreach( $excluded_urls as $url_to_exclude ) {

                if( fnmatch($url_to_exclude, $current_url) ) {
                    return true;
                }

            }

        }

        if( isset($_GET['swcfpc']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') || (defined('DOING_AJAX') && DOING_AJAX) ) {
            return true;
        }
        
        return false;
        
    }


    function can_i_bypass_cache() {

        // Bypass the cache using filter
        if( has_filter('swcfpc_cache_bypass') ) {

            $cache_bypass = apply_filters('swcfpc_cache_bypass', false);

            if( $cache_bypass === true )
                return true;

        }

        // Bypass AJAX requests
        if( $this->get_single_config("cf_bypass_ajax", 0) > 0 ) {

            if( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
                return true;
            }

            if( function_exists( 'is_ajax' ) && is_ajax() ) {
                return true;
            }

            if( (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') || (defined('DOING_AJAX') && DOING_AJAX) ) {
                return true;
            }

        }

        // Bypass WooCommerce pages
        if( $this->get_single_config("cf_bypass_woo_cart_page", 0) > 0 && function_exists( 'is_cart' ) && is_cart() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_woo_checkout_page", 0) > 0 && function_exists( 'is_checkout' ) && is_checkout() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_woo_checkout_pay_page", 0) > 0 && function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_woo_shop_page", 0) > 0 && function_exists( 'is_shop' ) && is_shop() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_woo_product_page", 0) > 0 && function_exists( 'is_product' ) && is_product() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_woo_product_cat_page", 0) > 0 && function_exists( 'is_product_category' ) && is_product_category() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_woo_product_tag_page", 0) > 0 && function_exists( 'is_product_tag' ) && is_product_tag() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_woo_product_tax_page", 0) > 0 && function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_woo_pages", 0) > 0 && function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
            return true;
        }


        // Bypass Wordpress pages
        if( $this->get_single_config("cf_bypass_front_page", 0) > 0 && is_front_page() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_pages", 0) > 0 && is_page() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_home", 0) > 0 && is_home() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_archives", 0) > 0 && is_archive() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_tags", 0) > 0 && is_tag() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_category", 0) > 0 && is_category() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_feeds", 0) > 0 && is_feed() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_search_pages", 0) > 0 && is_search() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_author_pages", 0) > 0 && is_author() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_single_post", 0) > 0 && is_single() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_404", 0) > 0 && is_404() ) {
            return true;
        }


        if( $this->get_single_config("cf_bypass_logged_in", 0) > 0 && is_user_logged_in() ) {
            return true;
        }


        // Bypass cache if the parameter swcfpc is setted or we are on backend
        if( isset($_GET['swcfpc']) || is_admin() ) {
            return true;
        }

        return false;

    }


    function apply_cache() {

        if( ! $this->is_cache_enabled() ) {
            header("X-WP-CF-Super-Cache: disabled");
            return;
        }

        if( $this->skip_cache ) {
            return;
        }

        if ( $this->can_i_bypass_cache() ) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("X-WP-CF-Super-Cache: no-cache");
            return;
        }

        header_remove('Pragma');
        header_remove('Expires');
        header_remove('Cache-Control');
        header('Cache-Control: '.$this->get_cache_control_value());
        header("X-WP-CF-Super-Cache: cache");
        header("X-WP-CF-Super-Cache-Active: 1");

    }


    function get_cache_control_value() {

        $value = 's-max-age='.$this->get_single_config("cf_maxage", 604800).', s-maxage='.$this->get_single_config("cf_maxage", 604800).', max-age='.$this->get_single_config("browser_maxage", 60);

        return $value;

    }


    function bypass_cache_on_init() {

        if( ! $this->is_cache_enabled() ) {
            header("X-WP-CF-Super-Cache: disabled");
            return;
        }

        if( $this->skip_cache )
            return;

        if( $this->is_url_to_bypass() ) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("X-WP-CF-Super-Cache: no-cache");
            $this->skip_cache = true;
            return;
        }

    }


    public function force_bypass_for_logged_in_users() {

        if( !function_exists('is_user_logged_in') ) {
            include_once( ABSPATH . "wp-includes/pluggable.php" );
        }

        if ( is_user_logged_in() && $this->is_cache_enabled() ) {
            add_action( 'wp_footer', array( $this, 'inject_js_code' ), 100 );
            add_action( 'admin_footer', array( $this, 'inject_js_code' ), 100 );
        }

    }


    function is_cache_enabled() {

        if( $this->get_single_config("cf_cache_enabled", 0) > 0 || isset($_POST['swcfpc_submit_enable_page_cache']) )
            return true;

        return false;

    }


    function cronjob_purge_cache() {

        $error = "";

        $this->cloudflare_purge_cache( $error );

    }


    function get_upload_directory_path() {

        $upload     = wp_upload_dir();
        $upload_dir = $upload['basedir'];

        return $upload_dir . '/wp-cloudflare-super-page-cache';

    }


    function get_upload_directory_url() {

        $upload     = wp_upload_dir();
        $upload_dir = $upload['baseurl'];

        return $upload_dir . '/wp-cloudflare-super-page-cache';

    }


    function create_upload_directory() {

        return wp_mkdir_p( $this->get_upload_directory_path(), 0755 );

    }


    function w3tc_hooks() {

        if( $this->get_single_config("cf_w3tc_purge_on_flush_minfy", 0) > 0 ) {
            add_action( 'w3tc_flush_minify', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

        if( $this->get_single_config("cf_w3tc_purge_on_flush_posts", 0) > 0 ) {
            add_action( 'w3tc_flush_posts', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
            add_action( 'w3tc_flush_post', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

        if( $this->get_single_config("cf_w3tc_purge_on_flush_objectcache", 0) > 0 ) {
            add_action( 'w3tc_flush_objectcache', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

        if( $this->get_single_config("cf_w3tc_purge_on_flush_fragmentcache", 0) > 0 ) {
            add_action( 'w3tc_flush_fragmentcache', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

        if( $this->get_single_config("cf_w3tc_purge_on_flush_dbcache", 0) > 0 ) {
            add_action( 'w3tc_flush_dbcache', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

        if( $this->get_single_config("cf_w3tc_purge_on_flush_all", 0) > 0 ) {
            add_action( 'w3tc_flush_all', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

    }


    function wp_rocket_hooks() {

        if( $this->get_single_config("cf_wp_rocket_purge_on_post_flush", 0) > 0 ) {
            add_action( 'after_rocket_clean_post', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

        if( $this->get_single_config("cf_wp_rocket_purge_on_domain_flush", 0) > 0 ) {
            add_action( 'after_rocket_clean_domain', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

    }


    function wp_super_cache_hooks() {

        if( $this->get_single_config("cf_wp_super_cache_on_cache_flush", 0) > 0 ) {
            add_action( 'wp_cache_cleared', array($this, 'purge_cache_on_other_plugin_cache_flushes'), PHP_INT_MAX );
        }

    }


}

$sw_cloudflare_pagecache = new SW_CLOUDFLARE_PAGECACHE();