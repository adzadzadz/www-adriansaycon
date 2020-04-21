=== WP Cloudflare Super Page Cache ===
Tags: cloudflare cache, improve speed, improve performance, page caching
Requires at least: 3.0.1
Tested up to: 5.4
Stable tag: 3.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Speed up your website by enabling page cache on Cloudflare for dynamic websites too without having to purchase the Cloudflare's Enterprise plan.

== Description ==

The free Cloudflare plan allows you to enable a page cache by entering the *Cache Everything* rule, greatly improving response times. 

However for dynamic websites such as Wordpress, it is not possible to use this rule without running into problems as it is not possible to exclude critical web pages from the cache, the sessions for logged in users, ajax requests and much more.

**Thanks to this plugin all of this becomes possible.**

You will be able to significantly **improve the response times of your Wordpress website** by taking advantage of the very fast Cloudflare cache also for PHP pages. The alternative to this plugin is to purchase and configure the Enterprise plan.

**This plugin is compatible with all versions of Wordpress, all Wordpress themes and WooCommerce.** It can also be used in conjunction with other performance plugins as long as their rules do not interfere with the Cloudflare cache.

== Installation ==

FROM YOUR WORDPRESS DASHBOARD

1. Visit "Plugins" > Add New
2. Search for WP Cloudflare Super Page Cache
3. Activate WP Cloudflare Super Page Cache from your Plugins page.

FROM WORDPRESS.ORG

1. Download WP Cloudflare Super Page Cache
2. Upload the "wp-cloudflare-super-page-cache" directory to your "/wp-content/plugins/" directory, using ftp, sftp, scp etc.
3. Activate WP Cloudflare Super Page Cache from your Plugins page.

== Frequently Asked Questions ==

= How do I know if everything is working properly? =

To verify that everything is working properly, I invite you to check the HTTP response headers of the displayed page in Incognito mode (browse in private). WP Cloudflare Super Page Cache returns two headers:

**x-wp-cf-super-cache**

If its value is **cache**, WP Cloudflare Super Page Cache is active on the displayed page and the page cache is enabled. If **no-cache**, WP Cloudflare Super Page Cache is active but the page cache is disabled for the displayed page.

**x-wp-cf-super-cache-active**

This header is present only if the previous header has the value **cache**.

If its value is **1**, the displayed page should have been placed in the Cloudflare cache.

To find out if the page is returned from the cache, Cloudflare sets its header called **cf-cache-status**. 

If its value is **HIT**, the page has been returned from cache. 

If **MISS**, the page was not found in cache. Refresh the page.

If **BYPASS**, the page was excluded from WP Cloudflare Super Page Cache. 

If **EXPIRED**, the page was cached but the cache has expired.

= Error: Page Rule validation failed: See messages for details. (err code: 1004 ) =

Login to Cloudflare, click on your domain and go to Page Rules. If there are *Cache Everything* rules setted up by other plugins, remove them. Then disable and re-enable the cache from WP Cloudflare Super Page Cache

= Do you allow to bypass the cache for logged in users even on free plan? =

Yes. It is the main purpose of this plugin.

= When I am logged in, the "swcfpc" parameter is added to all links. What is this? =

It is a cache buster. Allows you, while logged in, to bypass the Cloudflare cache for pages that could be cached.

= Do you automatically clean up the cache on website changes? =

Yes, you can enable this option from the settings page.

= Can I restore all Cloudflare settings as before the plugin activation? =

Yes, there is a reset button. Anyway if you deactivate the plugin, all the changes made on Cloudflare will be restored.

= What happens if I delete the plugin? =

I advise you to disable the plugin before deleting it, to allow you to restore all the information on Cloudflare. Then you can proceed with the elimination. This plugin will delete all the data stored into the database so that your Wordpress installation remains clean.

= What happens to the browser caching settings on Cloudflare? =

You will not be able to use them anymore. You will need to enter the browser caching settings on your htaccess file or, if you use Nginx, in your server block's configuration file.

= Does it work with WooCommerce? =

Yes.

= Can I use this plugin together with WP Rocket or W3 Total Cache? =

Yes, you can, but we recommend to use only one page cache, so install the free WP Rocket Disable Page Caching add on.

= Something is not working, what can I do? =

Enable the debug mode and send me all the information you see at the bottom of the settings page so I can help you. Use the email you see on the sidebar.

= Can I bypass the cache using a filter? =

Yes you can. Example:

`function bypass_cache_custom( $cache_bypass ) {
    
    // Bypass cache on front page
    if( is_front_page() ) $cache_bypass = true;

    return $cache_bypass;

}

add_filter( 'swcfpc_cache_bypass', 'bypass_cache_custom', 1 );`


= Can I purge the cache programmatically? =

Yes you can. You can purge whole cache using the following code:

`global $sw_cloudflare_pagecache;

$error_msg = "";

if( $sw_cloudflare_pagecache->cloudflare_purge_cache( $error_msg ) ) {
    // Cache purged
}
else {
    // Cache not purged. Error on $error_msg
}

Or purge cache by URLs using the following code:


global $sw_cloudflare_pagecache;

$error_msg = "";
$urls = array("first url here", "second url here");

if( $sw_cloudflare_pagecache->cloudflare_purge_cache_urls( $urls, $error_msg ) ) {
    // Cache purged
}
else {
    // Cache not purged. Error on $error_msg
}`


== Changelog ==

= Version 3.8 =
* Added the ability to use the API tokens instead of the API keys to authenticate with Cloudflare
* Added in the admin toolbar the option to purge the cache for the current page/post only
* Added more debug details
* Added page/post action links to purge the cache for the selected page/post only

= Version 3.7.2 =
* Fixed a sentence for italian language

= Version 3.7.1 =
* Added option for automatically purge single post cache when a new comment is inserted into the database or when a comment is approved or deleted

= Version 3.7 =
* Added options for WP Rocket users
* Added options for W3 Total Cache users
* Added options for WP Super Cache users
* Improve some internal hooks

= Version 3.6.1 =
* Added options for WooCommerce

= Version 3.6 =
* Added Nginx support for "Overwrite the cache-control header" option

= Version 3.5 =
* Added Nginx support
* Italian translation

= Version 3.4 =
* Fixed notice Undefined index: HTTP_X_REQUESTED_WITH

= Older versions =
Version 1.5   - Added support for WooCommerce, filters and actions
Version 1.6   - Added support for scheduled posts, cronjobs, robots.txt and Yoast sitemaps
Version 1.7   - Little bugs fix
Version 1.7.1 - Fixed little incompatibilities due to swcfpc parameter
Version 1.7.2 - Added other cache exclusion options
Version 1.7.3 - Add support for AMP pages
Version 1.7.6 - Fixed little bugs
Version 1.7.8 - Added support for robots.txt and sitemaps generated by Yoast. Added a link to admin toolbar to purge cache fastly. Added custom header "Wp-cf-super-cache" for debug purposes
Version 1.8 - Solved some incompatibility with WP SES - Thanks to Davide Prevosto
Version 1.8.1 - Added support for other WooCommerce page types and AJAX requests
Version 1.8.4 - Fixed little bugs
Version 1.8.5 - Added support for subdomains
Version 1.8.7 - Prevent 304 response code
Version 2.0 - Database optimization and added support for browser cache-control max-age
Version 2.1 - Fixed warning on line 1200
Version 2.3 - Added support for wildcard URLs
Version 2.4 - Added support for pagination (thanks to Davide Prevosto)
Version 2.5 - Fixed little bugs and added support for Gutenberg editor
Version 2.6 - Auto-purge cache when edit posts/pages using Elementor and fix the warning on purge_cache_on_post_published
Version 2.7 - Fixed a little bug when calling purge_cache_on_post_published
Version 2.8 - Fixed the last warning
Version 3.0 - Improved the UX interface, added browser caching option and added support for htaccess so that it is possible to improve the coexistence of this plugin with other performance plugins.
Version 3.1 - Fixed PHP warning implode() for option Prevent the following urls to be cached
Version 3.2 - Improved cache-control flow via htaccess
Version 3.3 - Fixed missing checks in backend


== Upgrade Notice ==

= Version 3.6.1 =

* New update is available.


== Screenshots ==

1. This screen shot description corresponds to screenshot-1.jpg
Step 1 - Enter your Cloudflare's API Key and e-mail 
2. This screen shot description corresponds to screenshot-2.jpg
Step 2 - Select the domain
3. This screen shot description corresponds to screenshot-3.jpg
Step 3 - Enable the page Cache
