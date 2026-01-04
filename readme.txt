=== Quick Playground ===

Contributors: davidfcarr  

Tags: testing, staging, demo, playground  

Requires at least: 6.2  

Tested up to: 6.9

Stable tag: 1.0.8

License: GPLv2 or later  

License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Simplify creation of WordPress Playground test, staging, and demo sites. Specify the theme, plugins and content from the WP admin dashboard.

== Description ==

The Quick Playground plugin provides a safe and convenient way to test new designs and features for your WordPress website, or to create demos and share proposed design changes. It creates a clone of your website's home page and key content, allowing you to experiment with plugins, themes, and design changes without affecting your live website.

https://youtu.be/2nrRLy6bXZk

The plugin takes advantage of WordPress Playground, the innovative software that simulates a complete PHP/WordPress/database server environment running in your web browser for testing and experimentation. Quick Playground simplifies the creation of Playground Blueprints, which define steps such as installing themes and plugins and loading content. No need to hand-code JSON or arrange for code to be served from Github.

This plugin is ideal for developers and designers who want to test new ideas without disrupting their live website.

Features:

- Clone your website's home page and key content for testing purposes. For performance reasons, the plugin does not attempt to clone your entire database.

- Experiment with themes and plugins, including unpublished custom code.

- Test new block theme design customizations in a WordPress Playground environment before implementing them on your live site. 

- Save changes for future playground sessions, allowing you to keep experimenting.

- Create demo environments separate from your live website content, for example to showcase themes, plugins, or hosting services.

- Sync changes back to your live website. For example, you can prototype block theme changes in Playground and copy the updated templates or template parts back to the live site.

- Define pop-up prompts / help tips to be displayed on any front end or admin page within the playground environment.

- Works on WordPress multisite (clones the individual site, not the whole network). The multisite network administrator can set default themes and plugins to include or exclude.

Note: some of these features were previously reserved for a "Pro" version but are now available for free. You're welcome.

Learn more at [quickplayground.com](https://quickplayground.com)

Developer Friendly Features

- [Source code on GitHub](https://github.com/davidfcarr/quick-playground)

- [Examples of Using the Filters and Actions](https://github.com/davidfcarr/quick-playground/blob/main/filters.php)

How it Works

- You can create multiple Playground profiles, each of which can specify different themes, plugins, playgrounds, content, and settings. The Playground Blueprint is created for you, stored on your server as a PHP associative array, and served to the Playground as JSON file with the same data hierarchy.

- When a Playground is launched, it loads the themes, plugins, and content specified in your BluePrint. Any custom themes and plugins not in the WordPress repository will be archived on your server as ZIP files and downloaded on demand.

- Quick Playground loads a copy of itself into the Playground environment and assists with copying over content.

- If you obtain a Pro license key, a plugin with additional capabilities for saving and syncing content will be loaded into the Playground (not your live website).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/quick-playground` directory, or install the plugin through the WordPress plugins screen.

2. Activate the plugin through the 'Plugins' screen in WordPress.

3. Navigate to "Quick Playground" (between Appearance and Plugins on the dashboard).

== Frequently Asked Questions ==

= What is the WordPress Playground? =  

The WordPress Playground is a version of WordPress software that runs in your web browser without requiring a server. It allows you to test themes, plugins, and design changes in a safe environment.

= Can I sync changes back to my live website? =  

Yes, the plugin includes an experimental feature for syncing changes back to your live website. However, this feature is still under development and should be used with caution.

= Is my live website affected by the changes I make in the Playground? =  

No, the Playground environment is completely separate from your live website. Changes made in the Playground do not affect your live website unless you explicitly sync them back.

== Screenshots ==

1. The Quick Playground Blueprint Builder screen lets you specify the themes, plugins, and content to be included in a Playground.
2. A Go to Playground button embedded in a web page, good for demos or education.
3. The Quick Playground block lets you specify a Playground profile and display options (button, link, or embedded iFrame).
4. The optional iFrame display of a Playround lets you include a sidebar with an explanation or instructions, for example in the context of a tutorial.


== Changelog ==

= 1.0.8 =

* Download / upload mechanism as an alternative to file sync.

= 1.0.7 =

* Fix for zip images function, multisite

= 1.0.4 =

* Incorporated features for saving playground content between sessions and syncing it back to the live website.
* Faster downloading of images and attachments.

= 1.0 =  

* First public release to the WordPress plugins repository.

== License ==

This plugin is licensed under the GPLv2 or later. See the [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html) for more details.

== External services ==

Users may configure Quick Playground to display content from other websites that also run Quick Playground.
