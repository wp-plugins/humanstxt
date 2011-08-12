<?php

/**
 * This file contains backend related code of the Humans TXT plugin.
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
 * URL to Humans TXT plugin folder.
 */
define('HUMANSTXT_PLUGIN_URL', plugin_dir_url(HUMANSTXT_PLUGIN_FILE));

/**
 * Humans TXT's "WordPress basename".
 */
define('HUMANSTXT_PLUGIN_BASENAME', plugin_basename(HUMANSTXT_PLUGIN_FILE));

/**
 * URL to Humans TXT options page.
 */
define('HUMANSTXT_OPTIONS_URL', admin_url('options-general.php?page=humanstxt'));

/**
 * URL to Humans TXT options page.
 * @since 1.1.0
 */
define('HUMANSTXT_REVISIONS_URL', add_query_arg(array('subpage' => 'revisions'), HUMANSTXT_OPTIONS_URL));

/**
 * Register plugin admin actions, filters and hooks.
 */
add_action('admin_init', 'humanstxt_admin_init');
add_action('admin_menu', 'humanstxt_admin_menu');
add_action('admin_notices', 'humanstxt_version_warning'); 
add_action('contextual_help', 'humanstxt_contextual_help', 10, 2);
add_action('after_plugin_row_humans-txt/plugin.php', 'humanstxt_plugin_notice', 10, 3);
add_action('after_plugin_row_humans-dot-txt/humans-dot-txt.php', 'humanstxt_plugin_notice', 10, 3);
add_filter('plugin_action_links_'.HUMANSTXT_PLUGIN_BASENAME, 'humanstxt_actionlinks');
register_uninstall_hook(__FILE__, 'humanstxt_uninstall');

/**
 * Load plugin text-domain.
 */
humanstxt_load_textdomain();

/**
 * Callback function for 'admin_init' action.
 * Registers the CSS and JavaScript file.
 * Calls humanstxt_update_options() if necessary.
 * Calls humanstxt_restore_revision() if necessary.
 */
function humanstxt_admin_init() {

	if (isset($_GET['page']) && $_GET['page'] == HUMANSTXT_DOMAIN) {

		// register css/js files
		wp_register_style('humanstxt-options', HUMANSTXT_PLUGIN_URL.'options.css');
		wp_register_script('humanstxt-options', HUMANSTXT_PLUGIN_URL.'options.js');
		add_action('admin_print_styles', create_function(null, "wp_enqueue_style('humanstxt-options');"));
		add_action('admin_print_scripts', create_function(null, "wp_enqueue_script('humanstxt-options');"));

		// update plugin options?
		if (isset($_POST['action']) && $_POST['action'] == 'update') {
			check_admin_referer('humanstxt-options');
			humanstxt_update_options();
		}

		// restoring a revision?
		if (isset($_GET['action'], $_GET['revision']) && $_GET['action'] == 'restore') {
			check_admin_referer('restore-humanstxt_'.$_GET['revision']);
			humanstxt_restore_revision($_GET['revision']);
		}

	}

}

/**
 * Callback function if plugin is uninstalled.
 * Deletes all plugin options from the database.
 */
function humanstxt_uninstall() {
	delete_option('humanstxt_options');
	delete_option('humanstxt_content');
	delete_option('humanstxt_revisions');
}

/**
 * Callback function for 'admin_notices' action.
 * Prints warning message if the current WP version is too old.
 * 
 * @since 1.0.1
 */
function humanstxt_version_warning() {

	if (version_compare($GLOBALS['wp_version'], HUMANSTXT_VERSION_REQUIRED, '<')) {
		$updatelink = ' <a href="'.admin_url('update-core.php').'">'.sprintf(__('Please update your WordPress installation.', HUMANSTXT_DOMAIN)).'</a>';
		echo '<div id="humanstxt-warning" class="updated fade"><p><strong>'.sprintf(__('Humans TXT %1$s requires WordPress %2$s or higher.', HUMANSTXT_DOMAIN), HUMANSTXT_VERSION, HUMANSTXT_VERSION_REQUIRED).'</strong>'.(current_user_can('update_core') ? $updatelink : '').'</p></div>';
	}

}

/**
 * Callback function for 'admin_menu' action.
 * Registers the options page if the current user has access.
 * 
 * @global $humanstxt_screen_id
 */
function humanstxt_admin_menu() {

	global $humanstxt_screen_id;

	$roles = humanstxt_option('roles');
	array_unshift($roles, 'administrator'); // admins can always edit

	// loop through all roles that can edit the humans.txt and
	// add options page if the current user has one of the required roles
	foreach ($roles as $role) {
		if (current_user_can($role)) {
			$humanstxt_screen_id = add_options_page(__('Humans TXT', HUMANSTXT_DOMAIN), __('Humans TXT', HUMANSTXT_DOMAIN), $role, HUMANSTXT_DOMAIN, 'humanstxt_options');
			break;
		}
	}

}

/**
 * Callback function for 'plugin_action_links_{$plugin_file}' filter.
 * Adds a link to the plugin options page.
 * 
 * @param array $actions
 * @return array $actions Hijacked actions.
 */
function humanstxt_actionlinks($actions) {
	return array_merge(
		array('settings' => sprintf('<a href="%s">%s</a>', HUMANSTXT_OPTIONS_URL, __('Settings'))),
		$actions
	);
}

/**
 * Returns the content of our custom help menu.
 * 
 * @global $humanstxt_screen_id
 * 
 * @param string $contextual_help
 * @param string $screen_id
 * @return string $contextual_help Custom help menu content.
 */
function humanstxt_contextual_help($contextual_help, $screen_id) {

	global $humanstxt_screen_id;

	if ($humanstxt_screen_id && $humanstxt_screen_id == $screen_id) {

		$contextual_help = sprintf(
			'<p><strong>%s</strong> &mdash; %s</p>',
			__('What is the humans.txt?', HUMANSTXT_DOMAIN),
			__("It's an initiative for knowing the people behind a website. It's a TXT file in the site root that contains information about the humans who have contributed to the website.", HUMANSTXT_DOMAIN)
		);

		$contextual_help .= sprintf(
			'<p><strong>%s</strong> &mdash; %s</p>',
			__('Who should I mention?', HUMANSTXT_DOMAIN),
			__('Whoever you want to, provided they wish you to do so. You can mention the developer, the designer, the copywriter, the webmaster, the editor, ... anyone who contributed to the website.', HUMANSTXT_DOMAIN)
		);

		$contextual_help .= sprintf(
			'<p><strong>%s</strong> &mdash; %s</p>',
			__('How should I format it?', HUMANSTXT_DOMAIN),
			__('However you want, just make sure humans can easily read it. For some inspiration check the humans.txt of <a href="http://humanstxt.org/humans.txt" rel="external">humanstxt.org</a> or <a href="http://html5boilerplate.com/humans.txt" rel="external">html5boilerplate.com</a>.', HUMANSTXT_DOMAIN)
		);

		$contextual_help .= '
			<ul>
				<li><a href="http://humanstxt.org/" rel="external">'.__('Official Humans TXT website', HUMANSTXT_DOMAIN).'</a></li>
				<li><a href="http://wordpress.org/extend/plugins/humanstxt/" rel="external">'.__('Plugin Homepage', HUMANSTXT_DOMAIN).'</a></li>
				<li><a href="http://wordpress.org/tags/humanstxt" rel="external">'.__('Plugin Support Forum', HUMANSTXT_DOMAIN).'</a></li>
			</ul>
		';

	}

	return $contextual_help;

}

/**
 * Updates the plugin options and redirects to plugin options page.
 * 
 * @global $humanstxt_options
 */
function humanstxt_update_options() {

	global $humanstxt_options;

	// only update the admin-only options if current user is an admin
	if (current_user_can('administrator')) {

		$humanstxt_options['enabled'] = isset($_POST['humanstxt_enable']);
		$humanstxt_options['authortag'] = isset($_POST['humanstxt_authortag']);

		$humanstxt_options['roles'] = array();
		if (isset($_POST['humanstxt_roles']) && is_array($_POST['humanstxt_roles'])) {
			$humanstxt_options['roles'] = array_keys($_POST['humanstxt_roles']);
		}

		update_option('humanstxt_options', $humanstxt_options);

	}

	if (isset($_POST['humanstxt_content'])) {

		$humanstxt_content_old = humanstxt_content();
		$humanstxt_content_new = humanstxt_content_normalize(stripslashes($_POST['humanstxt_content']));

		// has the content changed?
		if ($humanstxt_content_new != $humanstxt_content_old) {
			humanstxt_add_revision($humanstxt_content_new);
			update_option('humanstxt_content', $humanstxt_content_new);
		}

	}

	wp_redirect(add_query_arg(array('settings-updated' => '1'), HUMANSTXT_OPTIONS_URL));
	exit;

}

/**
 * Replaces the 'humanstxt_content' with the content of the
 * given $revision if revisions aren't disabled. Redirects to
 * the plugin options page.
 * 
 * @since 1.1.0
 * 
 * @param int $revision Revisons number (key)
 */
function humanstxt_restore_revision($revision) {

	$revisions = humanstxt_revisions();

	if (!isset($revisions[$revision]))
		return;

	update_option('humanstxt_content', $revisions[$revision]['content']);
	humanstxt_add_revision($revisions[$revision]['content']);

	wp_redirect(add_query_arg(array('revision-restored' => '1'), HUMANSTXT_OPTIONS_URL));
	exit;

}

/**
 * Callback function for 'after_plugin_row_{$plugin_file}' action.
 * Prints a warning message which suggests to deactivate other
 * humans.txt plugins to avoid conflicts.
 *
 * @param string $plugin_file WordPress plugin path
 * @param string $plugin_data Plugin informations
 * @param string $status Plugin context: mustuse, dropins, etc.
 */
function humanstxt_plugin_notice($plugin_file, $plugin_data, $status) {
	if (is_plugin_active($plugin_file)) {
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message">'.sprintf(__('Humans TXT includes the functionality of %1$s. Please deactivate %1$s to avoid plugin conflicts.', HUMANSTXT_DOMAIN), '<em>'.$plugin_data['Name'].'</em>').'</div></td></tr>';
	}
}

/**
 * Returns an array with plugin rating and total votes from WordPress.org.
 * 
 * @return array|false Plugin rating and total votes. 
 */
function humanstxt_rating() {

	$api = get_transient('humanstxt_plugin_information');

	// update cache?
	if ($api === false) {

		require_once ABSPATH.'wp-admin/includes/plugin-install.php';
		$api = plugins_api('plugin_information', array('slug' => 'humanstxt'));

		if (!is_wp_error($api)) {
			set_transient('humanstxt_plugin_information', $api, 60 * 10);
		}

	}

	// return plugin rating when available
	if (!is_wp_error($api) && isset($api->rating, $api->num_ratings)) {
		return array('rating' => $api->rating, 'votes' => $api->num_ratings);
	}

	return false;

}

/**
 * Callback function registered with add_options_page().
 * Prints the requested page (options or revisions).
 */
function humanstxt_options() {

	// show revisions page and are they activated?
	if (isset($_GET['subpage']) && $_GET['subpage'] == 'revisions' && humanstxt_revisions() !== false) {
		humanstxt_revisions_page();
	} else {
		humanstxt_options_page();
	}

}

/**
 * Prints the plugin options page.
 * @since 1.1.0
 */
function humanstxt_options_page() {
?>
<div id="humanstxt" class="wrap <?php echo ($wp32 = version_compare(get_bloginfo('version'), '3.1.4', '>')) ? '' : 'not-wp32' ?>">

	<?php screen_icon() ?>

	<h2><?php _e('Humans TXT', HUMANSTXT_DOMAIN) ?></h2>

	<?php if (isset($_GET['settings-updated'])) : ?>
		<div class="updated"><p><strong><?php _e('Settings saved.') ?></strong></p></div>
	<?php elseif (isset($_GET['revision-restored'])) : ?>
		<div class="updated"><p><strong><?php _e('Revision restored.', HUMANSTXT_DOMAIN) ?></strong></p></div>
	<?php endif; ?>

	<?php $faqlink = sprintf('<a href="%s">%s</a>', 'http://wordpress.org/extend/plugins/humanstxt/faq/', __('Read FAQ...', HUMANSTXT_DOMAIN)) ?>

	<?php if (!humanstxt_is_rootinstall()) : ?>
		<div class="error"><p><strong><?php _e('Error: WordPress is not installed in the root of the domain.', HUMANSTXT_DOMAIN); ?></strong> <?=$faqlink?></p></div>
	<?php elseif (humanstxt_exists()) : ?>
		<div class="error"><p><strong><?php _e('Error: The site root already contains a physical humans.txt file.', HUMANSTXT_DOMAIN) ?></strong> <?=$faqlink?></p></div>
	<?php elseif (get_option('permalink_structure') == '' && current_user_can('manage_options')) : ?>
		<div class="error"><p><strong><?php printf(__('Error: Please <a href="%s">update your permalink structure</a> to something other than the default.', HUMANSTXT_DOMAIN), admin_url('options-permalink.php')) ?></strong> <?=$faqlink?></p></div>
	<?php endif; ?>

	<form method="post" action="<?=HUMANSTXT_OPTIONS_URL?>">

		<?php settings_fields('humanstxt') ?>

		<?php if (current_user_can('administrator')) : ?>

			<?php if (!defined('HUMANSTXT_METABOX')) define('HUMANSTXT_METABOX', true) ?>
			<?php if (HUMANSTXT_METABOX && ($rating = humanstxt_rating()) !== false) : ?>
				<div id="humanstxt-metabox" class="postbox humanstxt-box">
					<p class="text-rateit"><?php printf(__('If you like this plugin, why not <a href="%s" title="%s" rel="external">recommend it to others</a> by rating it?', HUMANSTXT_DOMAIN), 'http://wordpress.org/extend/plugins/humanstxt/', __('Rate this plugin on WordPress.org', HUMANSTXT_DOMAIN)) ?></p>
					<div class="star-holder">
						<?php $starimg = $wp32 ? admin_url('images/gray-star.png?v=20110615') : admin_url('images/star.gif') ?>
						<div class="star star-rating" style="width: <?php echo esc_attr($rating['rating']) ?>px"></div>
						<div class="star star5"><img src="<?=$starimg?>" alt="<?php _e('5 stars') ?>" /></div>
						<div class="star star4"><img src="<?=$starimg?>" alt="<?php _e('4 stars') ?>" /></div>
						<div class="star star3"><img src="<?=$starimg?>" alt="<?php _e('3 stars') ?>" /></div>
						<div class="star star2"><img src="<?=$starimg?>" alt="<?php _e('2 stars') ?>" /></div>
						<div class="star star1"><img src="<?=$starimg?>" alt="<?php _e('1 star') ?>" /></div>
					</div>
					<small class="text-votes"><?php printf(_n('(based on %s rating)', '(based on %s ratings)', $rating['votes']), number_format_i18n($rating['votes'])) ?></small>
				</div>
			<?php endif; ?>

			<h3><?php _e('Settings') ?></h3>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Humans TXT File', HUMANSTXT_DOMAIN) ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e('Humans TXT File', HUMANSTXT_DOMAIN) ?></span></legend>
							<label for="humanstxt_enable">
								<input name="humanstxt_enable" type="checkbox" id="humanstxt_enable" value="1" <?php checked('1', humanstxt_option('enabled')) ?> />
								<?php $humanstxt_link = '<a href="'.home_url('humans.txt').'" title="'.__("View this site's humans.txt file", HUMANSTXT_DOMAIN).'" rel="external">humans.txt</a>' ?>
								<?php printf( /* translators: %s: humans.txt (linked to the site's humans.txt file) */ __('Activate %s file', HUMANSTXT_DOMAIN), $humanstxt_link) ?>
							</label>
							<br />
							<label for="humanstxt_authortag" title="<?php esc_attr_e('Adds an <link rel="author"> tag to the site\'s <head> tag pointing to the humans.txt file.', HUMANSTXT_DOMAIN) ?>">
								<input name="humanstxt_authortag" type="checkbox" id="humanstxt_authortag" value="1" <?php checked('1', humanstxt_option('authortag')) ?> />
								<?php _e('Add an author link tag to the site', HUMANSTXT_DOMAIN) ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Editing Permissions', HUMANSTXT_DOMAIN) ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e('Editing Permissions', HUMANSTXT_DOMAIN) ?></span></legend>
							<?php _e('Roles that can edit the content of the humans.txt file', HUMANSTXT_DOMAIN) ?>:<br/>
							<?php
								$humanstxt_roles = humanstxt_option('roles');
								$wordpress_roles = get_editable_roles();
								unset($wordpress_roles['subscriber']);
							?>
							<?php foreach ($wordpress_roles as $role => $details) : ?>
								<?php $checked = ($role == 'administrator' || in_array($role, $humanstxt_roles)) ? 'checked="checked" ' : ''; ?>
								<?php $disabled = ($role == 'administrator') ? 'disabled="disabled" ' : ''; ?>
								<label for="humanstxt_role_<?=$role?>">
									<input name="humanstxt_roles[<?=$role?>]" type="checkbox" id="humanstxt_role_<?=$role?>" value="1" <?=$checked?><?=$disabled?>/>
									<?php echo translate_user_role($details['name']); ?>
								</label>
								<br />
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>	
			</table>

			<p class="submit clear">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

		<?php endif; ?>

		<h3><?php _e('Humans TXT File', HUMANSTXT_DOMAIN) ?></h3>

		<div id="humanstxt-editor-wrap">			
			<table class="form-table">
				<tr valign="top">
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php _e('Humans TXT File', HUMANSTXT_DOMAIN) ?></span></legend>
							<span class="description"><label for="humanstxt_content"><?php _e('If you need a little help with your humans.txt, try the "Help" button in the top right corner of this page.', HUMANSTXT_DOMAIN) ?></label></span>
							<textarea name="humanstxt_content" rows="25" cols="80" id="humanstxt_content" class="large-text code"><?=esc_textarea(humanstxt_content())?></textarea>
						</fieldset>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				<?php $revisions = humanstxt_revisions() ?>
				<?php if (count($revisions) > 1) : ?><a href="<?=HUMANSTXT_REVISIONS_URL?>" class="button"><?php _e('View Revisions', HUMANSTXT_DOMAIN) ?></a><?php endif; ?>
			</p>
		</div>

		<?php $humanstxt_variables = humanstxt_valid_variables() ?>
		<?php if (!empty($humanstxt_variables)) : ?>
			<div id="humanstxt-vars">
				<h4><?php _e('Variables', HUMANSTXT_DOMAIN) ?></h4>
				<ul>
					<?php foreach ($humanstxt_variables as $variable) : ?>
						<?php $callback_result = !isset($variable[4]) || $variable[4] ? call_user_func($variable[2]) : null ?>
						<li<?php if (!empty($callback_result)) : ?> class="has-result" title="<?php _e('Preview', HUMANSTXT_DOMAIN) ?>: <?=esc_attr($callback_result)?>"<?php endif; ?>>
							<code>$<?=$variable[1]?>$</code>
							<?php if (isset($variable[3]) && !empty($variable[3])) : ?>
								&mdash; <?=$variable[3]?>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<p><a href="http://wordpress.org/tags/humanstxt" rel="external"><?php _e('Suggest another variable...', HUMANSTXT_DOMAIN) ?></a></p>
			</div>
		<?php endif; ?>

		<div class="clear"></div>

		<h3><?php _e('Shortcode Usage', HUMANSTXT_DOMAIN) ?></h3>
		<p><?php printf(__('You can use the <code>[humanstxt]</code> shortcode to display the <em>humans.txt</em> file on a page or in a post. By default, all links, email addresses and Twitter account names will be converted into clickable links and email addresses will be encoded to block spam bots. <a href="%s" rel="external">Of course you can customize it...</a>', HUMANSTXT_DOMAIN), 'http://wordpress.org/extend/plugins/humanstxt/other_notes/#Shortcode-Usage') ?></p>

	</form>
</div>
<?php
}

/**
 * Prints the plugin options revisions page.
 * @since 1.1.0
 */
function humanstxt_revisions_page() {
?>
<div id="humanstxt-revisions" class="wrap <?php echo ($wp32 = version_compare(get_bloginfo('version'), '3.1.4', '>')) ? '' : 'not-wp32' ?>">

	<?php screen_icon() ?>

	<h2><?php _e('Humans TXT', HUMANSTXT_DOMAIN) ?>: <?php _e('Revisions') ?></h2>

	<?php
		$revisions = humanstxt_revisions(); krsort($revisions);
		$live_revision = max(array_keys($revisions));
		$current_revision = isset($_GET['revision']) && isset($revisions[$_GET['revision']]) ? (int) $_GET['revision'] : false;
	?>

	<?php if ($current_revision !== false) : ?>

		<h3><?php printf( /* translators: %s: revision date */ __('Revision created on %s', HUMANSTXT_DOMAIN), date_i18n(_x('j F, Y @ G:i', 'revision date format'), $revisions[$current_revision]['date'])) ?></h3>
		<pre id="revision-preview" class="postbox"><?php echo esc_html(trim($revisions[$current_revision]['content'])) ?></pre>
		<p class="submit"><a href="<?php echo wp_nonce_url(add_query_arg(array('revision' => $current_revision, 'action' => 'restore'), HUMANSTXT_OPTIONS_URL), 'restore-humanstxt_'.$current_revision) ?>" class="button-primary"><?php _e('Restore Revision', HUMANSTXT_DOMAIN) ?></a></p>

	<?php endif; ?>

	<h3><?php _e('Revisions') ?></h3>

	<table class="widefat humanstxt-revisions" cellspacing="0" id="humanstxt-revisions">
		<col style="width: 33%" />
		<col style="width: 33%" />
		<col style="width: 33%" />
		<thead>
			<tr>
				<th scope="col"><?php _ex('Date Created', 'revisions column name') ?></th>
				<th scope="col"><?php _e('Author') ?></th>
				<th scope="col" class="action-links"><?php _e('Actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($revisions as $key => $revision) : ?>
				<tr<?php echo ($key === $current_revision) ? ' class="current-revision"' : '' ?>>
					<td>
						<?php $date = '<a href="'.HUMANSTXT_REVISIONS_URL.'&revision='.$key.'">'.date_i18n(_x( 'j F, Y @ G:i', 'revision date format'), $revision['date']).'</a>'?>
						<?php printf($key == $live_revision ? __('%1$s [Current Revision]') : '%s', $date) ?>
					</td>
					<td>
						<?php if ($revision['user'] > 0) : ?>
							<?php echo get_the_author_meta('display_name', $revision['user']); ?>
						<?php endif; ?>
					</td>
					<td class="action-links">
						<?php if ($key != $live_revision) : ?>
							<a href="<?php echo wp_nonce_url(add_query_arg(array('revision' => $key, 'action' => 'restore'), HUMANSTXT_OPTIONS_URL), 'restore-humanstxt_'.$key) ?>"><?php _e('Restore') ?></a>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
		</tbody>
	</table>

	<p><?php printf( /* translators: %s: number of stored revisions */ __('WordPress is storing the last %s revisions of your <em>humans.txt</em> file.', HUMANSTXT_DOMAIN), (int) apply_filters('humanstxt_max_revisions', HUMANSTXT_MAX_REVISIONS)) ?></p>

</div>
<?php
}

?>