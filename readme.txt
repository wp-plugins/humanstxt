=== Humans TXT ===
Contributors: tillkruess
Donate link: http://tillkruess.com/donations/
Tags: Humans TXT, HumansTXT, humans.txt, human, humans, author, authors, contributor, contributors, credits
Requires at least: 3.1
Tested up to: 3.1.2
Stable tag: 1.0.1

Credit the people behind your website in your humans.txt file. Easy to edit, directly within WordPress.

== Description ==

Maintain your **humans.txt** file easily within WordPress and use **handy variables** in it like:

* A "last-updated" date
* A list of active plugins
* Information about the active theme
* The WP and PHP version
* ... and [many others](http://wordpress.org/extend/plugins/humanstxt/other_notes/).

More information on the Humans TXT can be found on the [official Humans TXT website](http://humanstxt.org/).


== Installation ==

1. Upload the `/humanstxt/` directory and its contents to `/wp-content/plugins/`.
2. Login to your WordPress installation and activate the plugin through the _Plugins_ menu.
3. Edit your humans.txt file in the _Settings_ menu under _Humans TXT_.

**Please note:** This plugin does not modify or create a physical `humans.txt` file on your server, it generates it on the fly. If your site root already contains a `humans.txt` file, this file will be shown to the visitor. If you want to use this plugin, delete your physical `humans.txt`, but don't forget to migrate its contents.


== Frequently Asked Questions ==

= Error: WordPress is not installed in the site root =

This plugin will only work if WordPress is installed in the root of your domain.

= Error: Site root contains a humans.txt file =

If your site root contains a physical `humans.txt` file, this file will be shown to the visitor and the plugin will not work.

= Error: "Pretty Permalinks" are not activated =

The plugin will only work, if WordPress is using the "Pretty Permalinks". You can activate them in WordPress in the _Settings_ menu under _Permalinks_. Read more about [using permalinks](http://codex.wordpress.org/Using_Permalinks).

= Why isn't my humans.txt file modified? =

This plugin does not modify or create a physical `humans.txt` file on your server, it generates it on the fly. If your site root contains a `humans.txt` file, this file physical will be shown to the visitor. To use this plugin, delete your `humans.txt` file (*but don't forget to migrate its contents*). 

= Where is the humans.txt file located! =

Usually in the root of your site, **BUT** this plugin doesn't need or create a physical `humans.txt` file, it serves it on the fly.


== Screenshots ==

1. Plugin options page.


== Changelog ==

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

= 1.0.1 =

This version contains several fixes and improvements.

== Available Variables ==

* `$wp-lastupdate$` - Time of last modified post/page
* `$wp-version$` - Installed WordPress version
* `$php-version$` - Running PHP version
* `$wp-language$` - Active WordPress language
* `$wp-plugins$` - List of activated WordPress plugins
* `$wp-theme$` - Summary of the active WP theme
* `$wp-theme-name$` - Name of active WordPress theme
* `$wp-theme-version$` - Version of active WP theme
* `$wp-theme-author$` - Author name of active WP theme
* `$wp-theme-author-link$` - Author URL of active WP theme
