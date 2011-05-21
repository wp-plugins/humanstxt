<?php
/*
Plugin Name: Humans TXT
Plugin URI: http://tillkruess.com/projects/humanstxt/
Description: Credit the people behind your website in your <strong>humans.txt</strong> file. Easy to edit, directly within WordPress.
Version: 1.0.2
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
 *
 */

/**
 * @since 1.0.1
 */
define('HUMANSTXT_VERSION', '1.0.2');

/**
 * Absolute path to the main plugin file.
 */
define('HUMANSTXT_PLUGIN_FILE', __FILE__);

/**
 * Absolute path the the plugin directory.
 */
define('HUMANSTXT_PLUGIN_PATH', dirname(HUMANSTXT_PLUGIN_FILE));

/**
 * Plugin basename and text domain.
 */
define('HUMANSTXT_DOMAIN', basename(HUMANSTXT_PLUGIN_PATH));

/**
 * The default plugin settings.
 * @global array $humanstxt_defaults Default plugin settings.
 */
$humanstxt_defaults = array(
	'enabled' => false,
	'authortag' => false
);

/**
 * Register plugin actions and filters.
 */
add_action('init', 'humanstxt_init');
add_action('template_redirect', 'humanstxt_template_redirect', 8);
add_action('do_humans', 'humanstxt_do_humans');
add_filter('humans_txt', 'humanstxt_replace_variables');

/**
 * Load plugin code for WordPress backend, if needed.
 */
if (is_admin()) {
	require_once HUMANSTXT_PLUGIN_PATH.'/options.php';
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
 * Callback function for 'init' action.
 * Loads plugin settings from database and registers
 * rewrite rules for the humans.txt file.
 * 
 * @uses humanstxt_is_rootinstall()
 * 
 * @global $humanstxt_options
 * @global $humanstxt_defaults
 * @global $wp_rewrite
 */
function humanstxt_init() {

	global $humanstxt_options, $humanstxt_defaults, $wp_rewrite;

	$rewrite_rules = get_option('rewrite_rules');
	$humanstxt_options = get_option('humanstxt_options');

	// populate $humanstxt_options with defaults if necessary
	foreach ($humanstxt_defaults as $option => $value) {
		if (!isset($humanstxt_options[$option])) {
			$humanstxt_options[$option] = $value;
		}
	}

	if (humanstxt_option('enabled')) {

		// rewrite humans.txt file only if installed at the root
		if (humanstxt_is_rootinstall()) {
			add_filter('query_vars', create_function('$qv', '$qv[] = "humans"; return $qv;'));
			add_rewrite_rule('humans\.txt$', $wp_rewrite->index.'?humans=1', 'top');
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
 * Calls 'do_humanstxt' action and prints humanstxt_content()
 * after applying 'humans_txt' filter to it.
 * 
 * @uses humanstxt_content()
 */
function humanstxt_do_humans() {

	header('Content-Type: text/plain; charset=utf-8');
	do_action('do_humanstxt');

	echo apply_filters('humans_txt', humanstxt_content());

}

/**
 * Prints XHTML-conform author tag.
 */
function humanstxt_authortag() {
	echo '<link rel="author" type="text/plain" href="'.home_url('humans.txt').'" />'."\n";
}

/**
 * Returns plugin setting value of given $option.
 * Returns plugin default value if $option is not set.
 * Return NULL if $option doesn't exist.
 * 
 * @param string $option Name of the option.
 * @return mixed Plugin option value
 */
function humanstxt_option($option) {

	global $humanstxt_options, $humanstxt_defaults;

	if (isset($humanstxt_options[$option])) {
		return $humanstxt_options[$option];
	}

	// just in case humanstxt_init() has not been called
	if (isset($humanstxt_defaults[$option])) {
		return $humanstxt_defaults[$option];
	}

	return null;

}

/**
 * Returns stored humans.txt file content.
 * Stores and returns default humans.txt content, if not set yet.
 * 
 * @uses humanstxt_default_content()
 * 
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
 * @uses humanstxt_load_textdomain()
 * @return array $variables Default content-variables.
 */
function humanstxt_variables() {

	humanstxt_load_textdomain();

	$variables = array();
	$variables[] = array(__('wp-lastupdate', HUMANSTXT_DOMAIN), 'humanstxt_callback_lastupdate', __('Time of last modified post/page', HUMANSTXT_DOMAIN));
	$variables[] = array(__('wp-version', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpversion', __('Installed WordPress version', HUMANSTXT_DOMAIN));
	$variables[] = array(__('php-version', HUMANSTXT_DOMAIN), 'humanstxt_callback_phpversion', __('Running PHP version', HUMANSTXT_DOMAIN));
	$variables[] = array(__('wp-language', HUMANSTXT_DOMAIN), 'humanstxt_callback_wplanguage', __('Active WordPress language', HUMANSTXT_DOMAIN));
	$variables[] = array(__('wp-plugins', HUMANSTXT_DOMAIN), 'humanstxt_callback_wpplugins', __('List of activated WordPress plugins', HUMANSTXT_DOMAIN));
	$variables[] = array(__('wp-theme', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme', __('Summary of the active WP theme', HUMANSTXT_DOMAIN));
	$variables[] = array(__('wp-theme-name', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_name', __('Name of active WordPress theme', HUMANSTXT_DOMAIN));
	$variables[] = array(__('wp-theme-version', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_version', __('Version of active WP theme', HUMANSTXT_DOMAIN));
	$variables[] = array(__('wp-theme-author', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_author', __('Author name of active WP theme', HUMANSTXT_DOMAIN));
	$variables[] = array(__('wp-theme-author-link', HUMANSTXT_DOMAIN), 'humanstxt_callback_wptheme_author_link', __('Author URL of active WP theme', HUMANSTXT_DOMAIN));

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
 * but we have a translated string *without* having to load the plugin
 * text-domain on every request.
 * 
 * @used humanstxt_load_textdomain()
 * 
 * @return string Default humans.txt file content.
 */
function humanstxt_default_content() {

	humanstxt_load_textdomain();

	return __(
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
', HUMANSTXT_DOMAIN);

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
 * Returns user-friendly language of WordPress.
 * Supports WPML, qTranslate and xili-language.
 * 
 * @uses format_code_lang()
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

		return implode($separator, $languages);

	} elseif (function_exists('qtrans_getSortedLanguages')) { // is qTranslate active?

		$languages = qtrans_getSortedLanguages();
		foreach ($languages as $key => $language) {
			// try to get internatinal language name
			$languages[$key] = isset($q_config['locale'][$language]) ? format_code_lang($language) : qtrans_getLanguageName($language);
		}

		return implode($separator, $languages);

	} elseif (defined('XILILANGUAGE_VER')) { // is xili-language active?

		$languages = $xili_language->get_listlanguages();
		foreach ($languages as $key => $language) {
			$languages[$key] = $language->description;
		}

		return implode($separator, $languages);

	} else {

		// just return the standard WordPress language...
		return format_code_lang(get_bloginfo('language'));

	}

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