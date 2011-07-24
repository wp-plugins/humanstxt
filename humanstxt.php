<?php
/*
Plugin Name: Humans TXT
Plugin URI: http://tillkruess.com/projects/humanstxt/
Description: Credit the people behind your website in your <strong>humans.txt</strong> file. Easy to edit, directly within WordPress.
Version: 1.0.5
Author: Till Krüss
Author URI: http://tillkruess.com/
License: GPLv3
*/

/**
 * This file contains all non-admin code of the Humans TXT plugin.
 *
 * Copyright 2011 Till Krüss  (www.tillkruess.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Humans TXT
 * @copyright 2011 Till Krüss
 */

/**
 * Humans TXT plugin version.
 * @since 1.0.1
 */
define('HUMANSTXT_VERSION', '1.0.5');

/**
 * Absolute path to the main Humans TXT plugin file.
 */
define('HUMANSTXT_PLUGIN_FILE', __FILE__);

/**
 * Absolute path to the Humans TXT plugin directory.
 */
define('HUMANSTXT_PLUGIN_PATH', dirname(HUMANSTXT_PLUGIN_FILE));

/**
 * Humans TXT plugin basename and text domain.
 */
define('HUMANSTXT_DOMAIN', basename(HUMANSTXT_PLUGIN_PATH));

/**
 * Default Humans TXT plugin settings.
 * @global array $humanstxt_defaults Default plugin settings.
 */
$humanstxt_defaults = array(
	'enabled' => false,
	'authortag' => false,
	'roles' => array()
);

/**
 * Register plugin actions, filters and shortcode.
 */
add_action('init', 'humanstxt_init');
add_action('template_redirect', 'humanstxt_template_redirect', 8);
add_action('do_humans', 'humanstxt_do_humans');
add_filter('humans_txt', 'humanstxt_replace_variables');
add_shortcode('humanstxt', 'humanstxt_shortcode');

/**
 * Load plugin code for WordPress backend, if needed.
 */
if (is_admin()) {
	require_once HUMANSTXT_PLUGIN_PATH.'/options.php';
}

/**
 * Echos the content of the virtual humans.txt file. 
 *
 * @since 1.0.4
 */
function humanstxt() {
	echo get_humanstxt();
}

/**
 * Returns the content of the virtual humans.txt file,
 * after applying the 'humans_txt' filter to it.
 *
 * @since 1.0.4
 *
 * @return string Content of the virtual humans.txt file
 */
function get_humanstxt() {
	return apply_filters('humans_txt', humanstxt_content());
}

/**
 * Echos a XHTML-conform author link tag.
 *
 * @uses get_humanstxt_authortag()
 */
function humanstxt_authortag() {
	echo get_humanstxt_authortag();
}

/**
 * Returns a XHTML-conform author link tag, pointed to
 * the humans.txt URL, after applying the 'humans_authortag'
 * filter to it.
 * 
 * @since 1.0.4
 *
 * @return string XHTML-conform author link tag
 */
function get_humanstxt_authortag() {
	return apply_filters('humans_authortag', '<link rel="author" type="text/plain" href="'.home_url('humans.txt').'" />'."\n");
}

/**
 * Determines if it is a request for the humans.txt file.
 * 
 * @return bool
 */
function is_humans() {
	return (bool)get_query_var('humans');
}

/**
 * Determines if there is a physical humans.txt file in WP's ABSPATH.
 * 
 * @return bool
 */
function humanstxt_exists() {
	return file_exists(ABSPATH.'humans.txt');
}

/**
 * Determines if WordPress is installed the site root.
 * 
 * @return bool
 */
function humanstxt_is_rootinstall() {

	$homeurl = parse_url(home_url());

	if (!isset($homeurl['path']) || empty($homeurl['path']) || $homeul['path'] == '/') {
		return true;
	}

	return false;

}

/**
 * Callback function for 'init' action.
 * Registers humans.txt rewrite rules, flushes rules if necessary.
 * 
 * @uses humanstxt_load_options()
 * @uses humanstxt_is_rootinstall()
 * @global $wp_rewrite
 */
function humanstxt_init() {

	global $wp_rewrite;

	$rewrite_rules = get_option('rewrite_rules');

	if (humanstxt_option('enabled')) {

		// rewrite humans.txt file only if installed at the root
		if (humanstxt_is_rootinstall()) {

			add_filter('query_vars', create_function('$qv', '$qv[] = "humans"; return $qv;'));
			add_rewrite_rule('humans\.txt$', $wp_rewrite->index.'?humans=1', 'top');

			// register author link tag action if enabled
			if (humanstxt_option('authortag')) {
				add_action('wp_head', 'humanstxt_authortag', 1);
			}

		}

		// flush rewrite rules if ours is missing
		if (!isset($rewrite_rules['humans\.txt$'])) {
			flush_rewrite_rules(false);
		}

	} else {

		// flush rewrite rules if ours shouldn't be there
		if (isset($rewrite_rules['humans\.txt$'])) {
			flush_rewrite_rules(false);
		}

	}

}

/**
 * Callback function for 'template_redirect' action.
 * Calls 'do_humans' action if is_humans() is positive.
 * 
 * @uses is_humans()
 */
function humanstxt_template_redirect() {
	if (is_humans()) {
		do_action('do_humans');
		exit;
	}
}

/**
 * Callback function for 'do_humans' action.
 * Calls 'do_humanstxt' action and echos get_humanstxt().
 * 
 * @uses get_humanstxt()
 */
function humanstxt_do_humans() {

	header('Content-Type: text/plain; charset=utf-8');
	do_action('do_humanstxt');

	echo get_humanstxt();

}

/**
 * Callback function for [humanstxt] shortcode. Processes
 * shortcode call and returns result as string. The un-wrapped
 * output can be filtered with the 'humanstxt_shortcode_content'
 * filter and the final output can be filtered with the
 * 'humanstxt_shortcode_output' filter.
 *
 * @since 1.0.4
 *
 * @param array $attributes
 * @return string
 */
function humanstxt_shortcode($attributes) {

	extract(shortcode_atts(array(
		'id' => '', // id-attribute of wrapping HTML element, if $wrap isn't false
		'pre' => false, // perfect for preformatted text: <pre>[humanstxt pre="1"]</pre>
		'plain' => false, // clean output, all options are ignored except $wrap and $id
		'wrap' => true, // wrap content of humans.txt in <p> element
		'filter' => true, // convert/format common entities and encode plain text email addresses		
		'clickable' => true, // make URLs, email addresses and Twitter accounts clickable
		'urls' => true, // force/prevent clickable URLs, regardless $clickable
		'emails' => true, // force/prevent clickable email addresses, regardless $clickable
		'twitter' => true // force/prevent clickable Twitter accounts, regardless $clickable
	), $attributes));

	$classes = array('humanstxt');
	$content = get_humanstxt();
	$content = esc_html($content);

	if (!$plain) {

		if (!$pre) $content = nl2br(trim($content)); // convert line breaks

		if ($filter) {
			if (!$pre) $content = wptexturize($content); // format common entities
			$content = convert_chars($content); // convert certain characters
			$content = capital_P_dangit($content); // correct "Wordpress"
		}

		// format standard headlines
		if (!$pre) {
			$headline_replacement = '<strong class="humanstxt-headline">$1</strong>';
			$headline_replacement = apply_filters('humanstxt_shortcode_headline_replacement', $headline_replacement);
			$content = preg_replace('~/\*(.+?)\*/~', $headline_replacement, $content);
		}

		// make URLs clickable
		if (($clickable && $urls) || (!$clickable && $urls && isset($attributes['urls']))) {
			$_content = preg_replace_callback('#(?<!=[\'"])(?<=[*\')+.,;:!&$\s>])(\()?([\w]+?://(?:[\w\\x80-\\xff\#%~/?@\[\]-]{1,2000}|[\'*(+.,;:!=&$](?![\b\)]|(\))?([\s]|$))|(?(1)\)(?![\s<.,;:]|$)|\)))+)#is', '_make_url_clickable_cb', $content);
			if (!is_null($_content)) $content = $_content;
			$content = preg_replace_callback('#([\s>])((www)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', '_make_web_ftp_clickable_cb', $content);
		}

		// make email addresses clickable
		if (($clickable && $emails) || (!$clickable && $emails && isset($attributes['emails']))) {
			$content = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $content);
		}

		// make Twitter account names clickable
		if (($clickable && $twitter) || (!$clickable && $twitter && isset($attributes['twitter']))) {
			$twitter_replacement = '$1<a href="http://twitter.com/$2" rel="external">@$2</a>';
			$twitter_replacement = apply_filters('humanstxt_shortcode_twitter_replacement', $twitter_replacement);
			$content = preg_replace('/(^|[^a-z0-9_])[@＠]([a-z0-9_]{1,20})([@＠\xC0-\xD6\xD8-\xF6\xF8-\xFF]?)/iu', $twitter_replacement, $content);
		}

		if ($filter) {
			// encode email addresses to block spam bots
			$content = preg_replace_callback('{(?:mailto:)?((?:[-!#$%&\'*+/=?^_`.{|}~\w\x80-\xFF]+|".*?")\@(?:[-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+|\[[\d.a-fA-F:]+\]))}xi', create_function('$matches', 'return antispambot($matches[0]);'), $content);
		}

		if ($pre) $classes[] = 'humanstxt-pre';

	} else {
		$classes[] = 'humanstxt-plain';
	}

	$content = apply_filters('humanstxt_shortcode_content', $content, $attributes);

	// do we have an id attribute?
	$id = preg_replace('~[^a-z0-9_-]~i', '', $id);
	if (!empty($id)) {
		$id = ' id="'.$id.'"';
	}

	// do we have a class attribute?
	$classes = preg_replace('~[^a-z0-9_-]~i', '', $classes);
	foreach ($classes as $key => $classname) {
		if (empty($classname)) unset($classes[$key]);
	}
	$class = empty($classes) ? '' : ' class="'.implode(' ', $classes).'"';

	// wrap the output?
	if ($wrap) {
		$content = '<p'.$id.$class.'>'.$content.'</p>';
	}

	return apply_filters('humanstxt_shortcode_output', $content, $attributes);

}

/**
 * Loads plugin options from database and sets missing
 * options to their default values.
 *
 * @since 1.0.5
 *
 * @global $humanstxt_options
 * @global $humanstxt_defaults
 */
function humanstxt_load_options() {

	global $humanstxt_options, $humanstxt_defaults;

	// already loaded?
	if (is_null($humanstxt_options)) {

		$humanstxt_options = get_option('humanstxt_options');

		// populate with defaults options if missing...
		foreach ($humanstxt_defaults as $option => $value) {
			if (!isset($humanstxt_options[$option])) {
				$humanstxt_options[$option] = $value;
			}
		}

	}

}

/**
 * Returns options value of given $option.
 * Returns NULL if $option doesn't exist.
 * 
 * @global $humanstxt_options
 * 
 * @param string $option Name of the option.
 * @return mixed Plugin option value
 */
function humanstxt_option($option) {

	global $humanstxt_options;

	humanstxt_load_options();

	return isset($humanstxt_options[$option]) ? $humanstxt_options[$option] : null;

}

/**
 * Returns stored humans.txt file content.
 * Stores and returns default humans.txt content, if not set yet.
 * 
 * @uses humanstxt_default_content()
 * @return string $content
 */
function humanstxt_content() {

	$content = get_option('humanstxt_content');

	// add option if missing
	if ($content === false) {
		$content = humanstxt_default_content();
		add_option('humanstxt_content', $content, '', 'no');
	}

	return apply_filters('humanstxt_content', $content);

}

/**
 * Loads plugin text-domain if not loaded.
 * 
 * @uses is_textdomain_loaded
 */
function humanstxt_load_textdomain() {
	if (!is_textdomain_loaded(HUMANSTXT_DOMAIN)) {
		load_plugin_textdomain(HUMANSTXT_DOMAIN, false, HUMANSTXT_DOMAIN.'/languages');
	}
}

/**
 * Replaces all valid content-variables in given string and return it.
 * 
 * @uses humanstxt_valid_variables()
 * 
 * @param string $string String in which content-variables should be replaced.
 * @return string $string Given string with replaced content-variables.
 */
function humanstxt_replace_variables($string) {

	$variables = humanstxt_valid_variables();

	foreach ($variables as $variable) {

		$variable_name = '$'.$variable[0].'$';

		// does the variable occur in the string?
		if (stripos($string, $variable_name) !== false) {

			// do we have a valid callback result?
			if (($result = call_user_func($variable[1])) !== false) {
				// replace all occurrences of the variable with callback result
				$string = str_ireplace($variable_name, (string)$result, $string);
			}

		}			

	}

	return $string;

}

/**
 * Returns an array of default content-variables after
 * applying the 'humanstxt_variables' filter to it.
 * 
 * Each array value represents a content-variable:
 * array(string $varname, callback $function, string $description);
 * 
 * @uses humanstxt_load_textdomain()
 * @return array $variables Default content-variables.
 */
function humanstxt_variables() {

	humanstxt_load_textdomain();

	$variables = array();
	
	$variables[] = array(_x('wp-title', 'Name of variable for the site/blog name', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpblogname', __('Name of site/blog', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-tagline', 'Name of variable for the site/blog tagline (description)', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptagline', __('Tagline (description) of site/blog', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-posts', 'Name of variable for the number of published posts', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpposts', __('Number of published posts', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-pages', 'Name of variable for the number of published pages', HUMANSTXT_DOMAIN), 'humanstxt_callback_wppages', __('Number of published pages', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-lastupdate', 'Name of variable for the last modified timestamp', HUMANSTXT_DOMAIN), 'humanstxt_callback_lastupdate', __('Time of last modified post or page', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-language', 'Name of variable for the active WordPress languages(s)', HUMANSTXT_DOMAIN), 'humanstxt_callback_wplanguage', __('Active WordPress language(s)', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-plugins', 'Name of variable for the activated WordPress plugins', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpplugins', __('Activated WordPress plugins', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-charset', 'Name of variable for the encoding (charset)', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpcharset', __('Encoding used for pages and feeds', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-version', 'Name of variable for the installed WordPress version', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpversion', __('Installed WordPress version', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('php-version', 'Name of variable for the running PHP version', HUMANSTXT_DOMAIN), 'humanstxt_callback_phpversion', __('Currently running PHP parser version', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme', 'Name of variable for the summary of the active WordPress theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme', __('Summary of the active WP theme', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme-name', 'Name of variable for the name of the active WordPress theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_name', __('Name of active WordPress theme', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme-version', 'Name of variable for the version of the active WordPress theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_version', __('Version of active theme', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme-author', 'Name of variable for author name of the active WordPress theme ', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_author', __('Author name of active theme', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme-author-link', 'Name of variable for author URL of the active WordPress theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_author_link', __('Author URL of active theme', HUMANSTXT_DOMAIN));

	return apply_filters('humanstxt_variables', $variables);

}

/**
 * Returns an array all valid content-variables.
 * 
 * @uses humanstxt_variables()
 * @return array $variables Valid content-variables.
 */
function humanstxt_valid_variables() {

	$variables = humanstxt_variables();

	// return empty array if $variables is empty or not an array
	if (!is_array($variables) || empty($variables)) {
		return array();
	}

	foreach ($variables as $key => $variable) {
		// delete if variable name or callback is not set
		if (!isset($variable[0], $variable[1])) {
			unset($variables[$key]); continue;
		}
		// delete if variable callback is not a function
		if (!function_exists($variable[1])) {
			unset($variables[$key]); continue;
		}
	}

	return $variables;

}

/**
 * Returns default content of humans.txt file. Quite ugly function,
 * but fast, since we have a translated string *without* having to
 * load the plugin text-domain on every request.
 * 
 * @uses humanstxt_load_textdomain()
 * @return string Default humans.txt file content.
 */
function humanstxt_default_content() {

	humanstxt_load_textdomain();

	return _x(
'/* the humans responsible & colophon */
/* humanstxt.org */

/* TEAM */
	<your title>: <your name>
	Site: <website url>
	Twitter: <@username>
	Location: <city, country>

		[...]

/* THANKS */
	<name>: <link, email, @twittername, ...>

		[...]

/* SITE */
	Last update: $wp-lastupdate$
	Standards: <HTML5, CSS3, ...>
	CMS: WordPress $wp-version$ (running PHP $php-version$)
	Language: <English, Klingon, ...>
	Components: <jQuery, Typekit, Modernizr, ...>
	IDE: <Coda, Zend Studio, Photoshop, Terminal, ...>
', 'Default content of the humans.txt file', HUMANSTXT_DOMAIN);

}

/**
 * Returns the server's PHP version.
 * 
 * @return string Value of phpversion()
 */
function humanstxt_callback_phpversion() {
	return phpversion();
}

/**
 * Returns the WordPress version.
 * 
 * @return string WordPress version.
 */
function humanstxt_callback_wpversion() {
	return get_bloginfo('version');
}

/**
 * Returns the site/blog title.
 * 
 * @since 1.0.5
 *
 * @return string Site/blog name.
 */
function humanstxt_callback_wpblogname() {
	return get_bloginfo('name');
}

/**
 * Returns the site/blog description (tagline).
 * 
 * @since 1.0.5
 *
 * @return string Site/blog description.
 */
function humanstxt_callback_wptagline() {
	return get_bloginfo('description');
}

/**
 * Returns the encoding used for pages and feeds.
 * 
 * @since 1.0.5
 *
 * @return string Site/blog encoding.
 */
function humanstxt_callback_wpcharset() {
	return get_bloginfo('charset');
}

/**
 * Returns count of posts that are published. Can be
 * modified using the 'humanstxt_postcount' filter.
 * 
 * @since 1.0.4
 * @uses wp_count_posts()
 * 
 * @return string Number of published posts
 */
function humanstxt_callback_wpposts() {
	$postcounts = wp_count_posts();
	return apply_filters('humanstxt_postcount', $postcounts->publish);
}

/**
 * Returns count of pages that are published. Can be
 * modified using the 'humanstxt_pagecount' filter.
 * 
 * @since 1.0.4
 * @uses wp_count_posts()
 * 
 * @return string Number of published pages
 */
function humanstxt_callback_wppages() {
	$pagecounts = wp_count_posts('page');
	return apply_filters('humanstxt_pagecount', $pagecounts->publish);
}

/**
 * Returns user-friendly language of WordPress.
 * Supports WPML, qTranslate and xili-language.
 * 
 * @uses format_code_lang()
 * @global $sitepress
 * @global $q_config
 * @global $xili_language
 * 
 * @return string Name(s) of language(s).
 */
function humanstxt_callback_wplanguage() {

	global $sitepress, $q_config, $xili_language;

	require_once ABSPATH.'wp-admin/includes/ms.php';

	$separator = apply_filters('humanstxt_separator', ', ');
	$separator = apply_filters('humanstxt_languages_separator', $separator);

	if (defined('ICL_SITEPRESS_VERSION')) { // is WPML/SitePress active?

		$languages = $sitepress->get_active_languages();
		foreach ($languages as $code => $information) {
			$languages[$code] = $information['display_name'];
		}

		$active_languages = implode($separator, $languages);

	} elseif (function_exists('qtrans_getSortedLanguages')) { // is qTranslate active?

		$languages = qtrans_getSortedLanguages();
		foreach ($languages as $key => $language) {
			// try to get internatinal language name
			$languages[$key] = isset($q_config['locale'][$language]) ? format_code_lang($language) : qtrans_getLanguageName($language);
		}

		$active_languages = implode($separator, $languages);

	} elseif (defined('XILILANGUAGE_VER')) { // is xili-language active?

		$languages = $xili_language->get_listlanguages();
		foreach ($languages as $key => $language) {
			$languages[$key] = $language->description;
		}

		$active_languages = implode($separator, $languages);

	} else {

		// just return the standard WordPress language...
		$active_languages = format_code_lang(get_bloginfo('language'));

	}

	return apply_filters('humanstxt_languages', $active_languages);

}

/**
 * Returns YYYY/MM/DD timestamp of the latest modified post/page which is published.
 * The date format can be modified with the 'humanstxt_lastupdate_format' filter.
 * The final funtion result can be modified with the 'humanstxt_lastupdate' filter.
 * 
 * @global $wpdb 
 * @return string $last_edit Timestamp of last modified post/page.
 */
function humanstxt_callback_lastupdate() {
	global $wpdb;
	$last_edit = $wpdb->get_var($wpdb->prepare('SELECT post_modified FROM '.$wpdb->posts.' WHERE post_status = "publish" AND (post_type = "page" OR post_type = "post") ORDER BY post_modified DESC LIMIT 1'));
	if (!empty($last_edit)) {
		$last_edit = date(apply_filters('humanstxt_lastupdate_format', 'Y/m/d'), strtotime($last_edit));
	}
	return apply_filters('humanstxt_lastupdate', $last_edit);
}

/**
 * Returns a comma separated list of all active WordPress plugins.
 * Uses the 'humanstxt_separator' filter which is ', ' (comma + space) by
 * which is rewritable with the 'humanstxt_plugins_separator' filter.
 * Final function result can be modified with the 'humanstxt_plugins' filter.
 * 
 * @uses get_plugin_data()
 * @return string $active_plugins List of active WP plugins.
 */
function humanstxt_callback_wpplugins() {
	$active_plugins = get_option('active_plugins', array());
	if (is_array($active_plugins) && !empty($active_plugins)) {
		require_once ABSPATH.'wp-admin/includes/plugin.php';
		foreach ($active_plugins as $key => $file) {
			$plugin_data = get_plugin_data(WP_PLUGIN_DIR.'/'.$file, false);
			$active_plugins[$key] = $plugin_data['Name'];
		}
		$separator = apply_filters('humanstxt_separator', ', ');
		$separator = apply_filters('humanstxt_plugins_separator', $separator);
		$active_plugins = apply_filters('humanstxt_plugins', $active_plugins);
		return implode($separator, $active_plugins);
	}
	return null;
}

/**
 * Returns a summary of the active WordPress theme:
 * "Theme-Name (Version) by Author (Author-Link)"
 * Function result can be modified with the 'humanstxt_wptheme' filter.
 * 
 * @return string The theme's author name.
 */
function humanstxt_callback_wptheme() {
	$theme_data = get_theme(get_current_theme());
	$theme = null;
	if (!empty($theme_data['Name'])) {
		$theme = $theme_data['Name'];
		if (!empty($theme_data['Version'])) $theme .= ' ('.$theme_data['Version'].')';
		if (!empty($theme_data['Author Name'])) $theme .= ' by '.$theme_data['Author Name'];
		if (!empty($theme_data['Author URI'])) { $theme .= ' ('.$theme_data['Author URI'].')'; }
	}
	return apply_filters('humanstxt_wptheme', $theme);
}

/**
 * Returns the theme name or NULL if n/a.
 *  
 * @return string The theme name.
 */
function humanstxt_callback_wptheme_name() {
	$theme_data = get_theme(get_current_theme());
	return empty($theme_data['Name']) ? null : $theme_data['Name'];
}

/**
 * Returns the theme's version or NULL if n/a.
 *  
 * @return string The theme's version name.
 */
function humanstxt_callback_wptheme_version() {
	$theme_data = get_theme(get_current_theme());
	return empty($theme_data['Version']) ? null : $theme_data['Version'];
}

/**
 * Returns the theme's author name or NULL if n/a.
 *  
 * @return string The theme's author name.
 */
function humanstxt_callback_wptheme_author() {
	$theme_data = get_theme(get_current_theme());
	return empty($theme_data['Author Name']) ? null : $theme_data['Author Name'];
}

/**
 * Returns the theme's author link or NULL if n/a.
 *  
 * @return string The theme's author URI.
 */
function humanstxt_callback_wptheme_author_link() {
	$theme_data = get_theme(get_current_theme());
	return empty($theme_data['Author URI']) ? null : $theme_data['Author URI'];
}

?>