=== Humans TXT ===
Contributors: tillkruess
Donate link: http://tillkruess.com/donations/
Tags: Humans TXT, HumansTXT, humans.txt, human, humans, author, authors, contributor, contributors, credit, credits, robot, robots, robots.txt
Requires at least: 3.1
Tested up to: 3.2
Stable tag: 1.0.4

Credit the people behind your website in your humans.txt file. Easy to edit, directly within WordPress.

== Description ==

Credit the people behind your website in your **humans.txt** file. Easy to edit, directly within WordPress.

* Use **convenient variables** like a _last-updated_ date, active plugins and [many others...](http://wordpress.org/extend/plugins/humanstxt/other_notes/)
* Add an author link tag to your site's `<head>` tag
* Use the `[humanstxt]` shortcode to display your _humans.txt_ on a page or in a post
* Extend or modify this plugin with custom [filters and actions](http://wordpress.org/extend/plugins/humanstxt/other_notes/)

More information on the Humans TXT can be found on the [official Humans TXT website](http://humanstxt.org/).


== Installation ==

1. Upload the `/humanstxt/` directory and its contents to `/wp-content/plugins/`.
2. Login to your WordPress installation and activate the plugin through the _Plugins_ menu.
3. Activate the and edit your humans.txt file in the _Settings_ menu under _Humans TXT_.

**Please note:** This plugin does not modify or create a physical `humans.txt` file on your server, it generates it on the fly. If your site root already contains a `humans.txt` file, this file will be shown to the visitor. In order to use this plugin, please delete your physical `humans.txt`, but don't forget to migrate its contents.


== Frequently Asked Questions ==

= Error: WordPress is not installed in the site root =

This plugin can only work if WordPress is installed in the root of your domain.

= Error: The site root contains physical humans.txt file =

If your site root contains a physical `humans.txt` file, this file will be shown to the visitor and the plugin can not work.

= Error: Please update your permalink structure... =

The plugin can only work, if WordPress is using "Pretty Permalinks". You can activate them in WordPress in the _Settings_ menu under _Permalinks_. Read more about [using permalinks](http://codex.wordpress.org/Using_Permalinks).

= Why isn't my humans.txt file modified? =

This plugin does not modify or create a physical `humans.txt` file on your server, it generates it on the fly. If your site root contains a `humans.txt` file, this file physical will be shown to the visitor. In order to use this plugin, please delete your physical `humans.txt`. Don't forget to migrate or backup its contents. 

= Where is the humans.txt file located? =

Usually in the root of your site, **however** this plugin doesn't create a physical `humans.txt` file on your server, it serves it dynamically.


== Screenshots ==

1. Plugin options page.
2. Default shortcode result. (Theme: Twenty Eleven)
2. Shortcode result using `pre` attribute. (Theme: Twenty Eleven)


== Changelog ==

= 1.0.4 =

* Added `[humanstxt]` shortcode with several attributes
* Added new variables for the number of published posts and pages: `$wp-posts$`; `$wp-pages$`
* Minor changes to admin interface text, layout and scripts 
* Added few shortcut functions like: `humanstxt()` and `humanstxt_authortag()`
* Added filter for result of `$wp-language$` variable callback function 

= 1.0.3 =

* Adjusted admin UI metabox styling for WP 3.2
* Improved warning messages and notices

= 1.0.2 =

* Improved text editor functionality
* `$wp-language$` supports now WPML/SitePress, qTranslate and xili-language
* Fixed unwanted injection of author tag

= 1.0.1 =

* Added warning message if WordPress version is older than 3.1
* Prevented potential issue with `$wp-theme-author$` variable
* Prevented potential issue with preview of variable-callback result
* Improved textarea auto-grow functionality
* Improved Internet Explorer 6+7 support
* Added filter for `humanstxt_content()` result
* Revised plugin warning messages

= 1.0 =

* Initial release


== Upgrade Notice ==

= 1.0.4 =

This version introduces a shortcode, new variables and minor interface improvements.

= 1.0.3 =

This version ensures WordPress 3.2 compatibility and contains minor fixes and improvements.

= 1.0.2 =

This version contains minor fixes and improvements.

= 1.0.1 =

This version contains several fixes and improvements.


== Variables ==

* `$wp-lastupdate$` - Time of last modified post/page
* `$wp-version$` - Installed WordPress version
* `$php-version$` - Running PHP version
* `$wp-language$` - Active WordPress language
* `$wp-posts$` - Number of published posts
* `$wp-pages$` - Number of published pages
* `$wp-plugins$` - List of activated WordPress plugins
* `$wp-theme$` - Summary of the active WP theme
* `$wp-theme-name$` - Name of active WordPress theme
* `$wp-theme-version$` - Version of active WP theme
* `$wp-theme-author$` - Author name of active WP theme
* `$wp-theme-author-link$` - Author URL of active WP theme


== Shortcode Usage ==

The default shortcode `[humanstxt]` will display the contents of the virtual humans.txt file. URLs, email addresses and Twitter account names are converted into clickable links. Plain email addresses are encoded for spam protection. The output will be wrapped with a `<p>` tag and can be styled via the `humanstxt` CSS class.

You can turn off the "clickable links" functionality: `[humanstxt clickable="0"]`

You can also toggle the clickable links individually: `[humanstxt urls="1" emails="0" twitter="1"]`

To display the humans.txt as preformatted text, use the `pre` attribute: `<pre>[humanstxt pre="1"]</pre>`

To display the untouched humans.txt, use the `plain` attribute: `[humanstxt plain="1"]`

You can omit the wrapping with the `<p>` tag: `[humanstxt wrap="0"]`

You can set a CSS id for the wrapping `<p>` tag: `[humanstxt id="my-humans-txt"]`

And you can turn off the entity conversion for plain email addresses and common text entities: `[humanstxt filter="0"]` 


== Plugin Functions ==

**humanstxt()**  
Echos the content of the virtual humans.txt file. Use `get_humanstxt()` to get the contents as a _string_. 

**is_humans()**  
Determines if the current request is for the virtual humans.txt file.

**humanstxt_authortag()**  
Echos a XHTML-conform author link tag linked to the humans.txt file. Use `get_humanstxt_authortag()` to get the tag as a _string_.


== Plugin Actions & Filters ==

= Actions =

**do_humans**  
Runs when the current request is for the *humans.txt* file, right after the `template_redirect` action.

**do_humanstxt**  
Runs right before the *humans.txt* is printed to the screen.

= Filters =

**humans_txt**  
Applied to the final content of the virtual humans.txt file.

**humans_authortag**  
Applied to the author link tag.

**humanstxt_content**
Applied to the humans.txt content. Applied prior to the `humans_txt` filter.

**humanstxt_variables**  
Applied to the array of content-variables. See `humanstxt_variables()` for details.

**humanstxt_shortcode_output**  
Applied to the final `[humanstxt]` shortcode output.

**humanstxt_shortcode_content**  
Applied to the un-wrapped shortcode output.

**humanstxt_shortcode_headline_replacement**  
Applied to replacement string for matched standard headlines: `/* Title */`. See `humanstxt_shortcode()` for details.

**humanstxt_shortcode_twitter_replacement**  
Applied to replacement string for matched twitter account names. See `humanstxt_shortcode()` for details.

**humanstxt_separator**  
Applied to the global text separator. Default is a comma followed by a space.

**humanstxt_plugins_separator**  
Use to override the global text separator (see `humanstxt_separator` filter) for the list of active WordPress plugins.

**humanstxt_languages_separator**  
Use to override the global text separator (see `humanstxt_separator` filter), for the current WordPress language(s).

**humanstxt_wptheme**  
Applied to the summary of the active WordPress theme: `$wp-theme$`.

**humanstxt_plugins**  
Applied to the list of active WordPress plugins: `$wp-plugins$`.

**humanstxt_languages**  
Applied to current WordPress language(s): `$wp-language$`.

**humanstxt_lastupdate**  
Applied to returned date of the `$wp-lastupdate$` variable.

**humanstxt_lastupdate_format**  
Applied to the used date-format of the `$wp-lastupdate$` variable. See Codex: [Formatting Date and Time](http://codex.wordpress.org/Formatting_Date_and_Time)
