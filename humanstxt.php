<?php
/*
Plugin Name: Humans TXT
Plugin URI: http://tillkruess.com/projects/humanstxt/
Description: Credit the people behind your website in your <strong>humans.txt</strong> file. Easy to edit, directly within WordPress.
Version: 1.0.6
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
define('HUMANSTXT_VERSION', '1.0.6');

/**
 * Required WordPress version.
 * @since 1.0.6
 */
define('HUMANSTXT_VERSION_REQUIRED', '3.2');

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
 * Default amount of stored revisions.
 * @since 1.0.6
 */
define('HUMANSTXT_MAX_REVISIONS', '50');

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
	return (bool) get_query_var('humans');
}

/**
 * Determines if there is a physical humans.txt file in WP's root folder.
 * 
 * @return bool
 */
function humanstxt_exists() {
	return file_exists(ABSPATH.'humans.txt');
}

/**
 * Determines if WordPress is installed the site root. Returned
 * value can be overridden with the HUMANSTXT_IS_ROOTINSTALL constant. 
 * 
 * @return bool
 */
function humanstxt_is_rootinstall() {

	if (defined('HUMANSTXT_IS_ROOTINSTALL'))
		return HUMANSTXT_IS_ROOTINSTALL;

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

		// rewrite humans.txt file only if installed in the root
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

// TODO: write func comment
function humanstxt_revisions() {

	// are revisions disabled?
	if ((int) apply_filters('humanstxt_max_revisions', 1) < 1)
		return false;

	$revisions = get_option('humanstxt_revisions');

	// add or reset option?
	if (!is_array($revisions)) {
		$revisions = array(array('date' => current_time('timestamp'), 'user' => 0, 'content' => humanstxt_content()));
		add_option('humanstxt_revisions', $revisions, '', 'no');
	}

	return $revisions;

}

// TODO: write func comment
function humanstxt_add_revision($content) {

	$current_user = wp_get_current_user();
	$revisions = humanstxt_revisions();
	$revisions[] = array('date' => current_time('timestamp'), 'user' => $current_user->ID, 'content' => $content);

	// limit amount of revisions
	$revisions = array_slice($revisions, -abs((int) apply_filters('humanstxt_max_revisions', HUMANSTXT_MAX_REVISIONS)), count($revisions), true);

	update_option('humanstxt_revisions', $revisions);

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
	require_once HUMANSTXT_PLUGIN_PATH.'/callbacks.php';

	$variables = array();	
	$variables[] = array(_x('wp-title', 'Name of variable for the site/blog name (title)', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpblogname', __('Name (title) of site/blog', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-tagline', 'Name of variable for the site/blog tagline (description)', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptagline', __('Tagline (description) of site/blog', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-posts', 'Name of variable for the number of published posts', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpposts', __('Number of published posts', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-pages', 'Name of variable for the number of published pages', HUMANSTXT_DOMAIN), 'humanstxt_callback_wppages', __('Number of published pages', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-lastupdate', 'Name of variable for the last modified timestamp', HUMANSTXT_DOMAIN), 'humanstxt_callback_lastupdate', __('Date of last modified post/page', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-language', 'Name of variable for WordPress languages(s)', HUMANSTXT_DOMAIN), 'humanstxt_callback_wplanguage', __('WordPress language(s)', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-plugins', 'Name of variable for activated WordPress plugins', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpplugins', __('Activated WordPress plugins', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-charset', 'Name of variable for the encoding (charset)', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpcharset', __('Encoding used for pages and feeds', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-version', 'Name of variable for the installed WordPress version', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpversion', __('Installed WordPress version', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('php-version', 'Name of variable for the running PHP version', HUMANSTXT_DOMAIN), 'humanstxt_callback_phpversion', __('Running PHP parser version', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme', 'Name of variable for the summary of the active WordPress theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme', __('Summary of the active WordPress theme', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme-name', 'Name of variable for the name of the active WordPress theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_name', __('Name of the active theme', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme-version', 'Name of variable for the version of the active WordPress theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_version', __('Version of the active theme', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme-author', 'Name of variable for author name of the active WordPress theme ', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_author', __('Author name of the active theme', HUMANSTXT_DOMAIN));
	$variables[] = array(_x('wp-theme-author-link', 'Name of variable for author URL of the active WordPress theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_author_link', __('Author URL of the active theme', HUMANSTXT_DOMAIN));

	return (array) apply_filters('humanstxt_variables', $variables);

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

?>