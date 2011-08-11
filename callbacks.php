<?php

/**
 * This file contains all default variable callback functions
 * of the Humans TXT plugin.
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

if (!function_exists('humanstxt_callback_phpversion')) :
/**
 * Returns the server's PHP version.
 * 
 * @return string Value of phpversion()
 */
function humanstxt_callback_phpversion() {
	return phpversion();
}
endif;

if (!function_exists('humanstxt_callback_wpversion')) :
/**
 * Returns the WordPress version.
 * 
 * @return string WordPress version.
 */
function humanstxt_callback_wpversion() {
	return get_bloginfo('version');
}
endif;

if (!function_exists('humanstxt_callback_wpblogname')) :
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
endif;

if (!function_exists('humanstxt_callback_wptagline')) :
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
endif;

if (!function_exists('humanstxt_callback_wpcharset')) :
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
endif;

if (!function_exists('humanstxt_callback_wpposts')) :
/**
 * Returns count of posts that are published. Can be
 * modified using the 'humanstxt_postcount' filter.
 * 
 * @since 1.0.4
 * 
 * @return string Number of published posts
 */
function humanstxt_callback_wpposts() {
	$postcounts = wp_count_posts();
	return apply_filters('humanstxt_postcount', $postcounts->publish);
}
endif;

if (!function_exists('humanstxt_callback_wppages')) :
/**
 * Returns count of pages that are published. Can be
 * modified using the 'humanstxt_pagecount' filter.
 * 
 * @since 1.0.4
 * 
 * @return string Number of published pages
 */
function humanstxt_callback_wppages() {
	$pagecounts = wp_count_posts('page');
	return apply_filters('humanstxt_pagecount', $pagecounts->publish);
}
endif;

if (!function_exists('humanstxt_callback_wplanguage')) :
/**
 * Returns user-friendly language of WordPress.
 * Supports WPML, qTranslate and xili-language.
 * 
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
endif;

if (!function_exists('humanstxt_callback_lastupdate')) :
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
endif;

if (!function_exists('humanstxt_callback_wpauthors')) :
/**
 * Returns all authors with a least 1 post. The format can be adjusted
 * with the 'humanstxt_authors_format' filter and the returned list
 * can be modified with the 'humanstxt_authors' filter.
 *
 * @since 1.1.0
 *
 * @return string|null A list of active authors
 */
function humanstxt_callback_wpauthors() {
	$authors = null;
	if (function_exists('get_users')) {
		$users = get_users(array('who' => 'author', 'orderby' => 'display_name', 'fields' => array('ID', 'display_name', 'user_email', 'user_url')));
	} else {
		$users = humanstxt_legacy_get_users();
	}
	if (!empty($users)) {
		foreach ($users as $user) $author_ids[] = $user->ID;
		$authors_posts = count_many_users_posts($author_ids);
		$format = apply_filters('humanstxt_authors_format', "\t".'%1$s: %2$s'."\n\n");
		foreach ($users as $user) {
			if ($authors_posts[$user->ID] > 0 && !empty($user->display_name)) {
				$contact = empty($user->user_url) ? $user->user_email : $user->user_url;
				$authors .= sprintf($format, $user->display_name, $contact);
			}
		}
	}
	return apply_filters('humanstxt_authors', ltrim($authors));
}
endif;

if (!function_exists('humanstxt_callback_wpplugins')) :
/**
 * Returns a comma separated list of all active WordPress plugins.
 * Uses the 'humanstxt_separator' filter which is ', ' (comma + space) by
 * which is rewritable with the 'humanstxt_plugins_separator' filter.
 * Final function result can be modified with the 'humanstxt_plugins' filter.
 * 
 * @return string|null $active_plugins List of active WP plugins.
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
endif;

if (!function_exists('humanstxt_callback_wptheme')) :
/**
 * Returns a summary of the active WordPress theme:
 * "Theme-Name (Version) by Author (Author-Link)"
 * Function result can be modified with the 'humanstxt_wptheme' filter.
 * 
 * @return string|null The theme's author name.
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
endif;

if (!function_exists('humanstxt_callback_wptheme_name')) :
/**
 * Returns the theme name or NULL if n/a.
 *  
 * @return string|null The theme name.
 */
function humanstxt_callback_wptheme_name() {
	$theme_data = get_theme(get_current_theme());
	return empty($theme_data['Name']) ? null : $theme_data['Name'];
}
endif;

if (!function_exists('humanstxt_callback_wptheme_version')) :
/**
 * Returns the theme's version or NULL if n/a.
 *  
 * @return string|null The theme's version name.
 */
function humanstxt_callback_wptheme_version() {
	$theme_data = get_theme(get_current_theme());
	return empty($theme_data['Version']) ? null : $theme_data['Version'];
}
endif;

if (!function_exists('humanstxt_callback_wptheme_author')) :
/**
 * Returns the theme's author name or NULL if n/a.
 *  
 * @return string|null The theme's author name.
 */
function humanstxt_callback_wptheme_author() {
	$theme_data = get_theme(get_current_theme());
	return empty($theme_data['Author Name']) ? null : $theme_data['Author Name'];
}
endif;

if (!function_exists('humanstxt_callback_wptheme_author_link')) :
/**
 * Returns the theme's author link or NULL if n/a.
 *  
 * @return string|null The theme's author URI.
 */
function humanstxt_callback_wptheme_author_link() {
	$theme_data = get_theme(get_current_theme());
	return empty($theme_data['Author URI']) ? null : $theme_data['Author URI'];
}
endif;

?>