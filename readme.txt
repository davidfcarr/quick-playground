=== Quick Playground ===

Contributors: davidfcarr  

Tags: design, theme, plugin, testing, playground  

Requires at least: 5.8  

Tested up to: 6.8

Stable tag: 0.9.0  

License: GPLv2 or later  

License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Simplify the process of creating WordPress Playground sites that can include unpublished code such as custom themes and plugins. Clone your website's home page and key content to experiment with design changes without altering your live website.

== Description ==

The Theme and Plugin Playground plugin provides a safe and convenient way to test new designs and features for your WordPress website. It creates a clone of your website's home page and key content, allowing you to experiment with design changes without affecting your live website.

The plugin takes advantage of WordPress Playground, the innovative software that simulates a complete PHP/WordPress/database server environment running in your web browser for testing and experimentation. Quick Playground simplifies the creation of Playground Blueprints, which define steps such as installing themes and plugins and loading content. No need to hand-code JSON or arrange for code to be served from Github.

Features:

- Clone your website's home page and key content for testing purposes. For performance reasons, the plugin does not attempt to clone your entire database.

- Experiment with themes and plugins, including unpublished custom code.

- Test new designs in a WordPress Playground environment.

Pro Version Upgrades 

- Save changes for future playground sessions, allowing you to keep experimenting. You can also create demo environments separate from your live website content, for example to showcase themes, plugins, or hosting services.

- Sync changes back to your live website (experimental feature).

This plugin is ideal for developers and designers who want to test new ideas without disrupting their live website.

How it Works

- You can create multiple Playground profiles, each of which can specify different themes, plugins, playgrounds, content, and settings. The Playground Blueprint is created for you, stored on your server as a PHP associative array, and served to the Playground as JSON file with the same data hierarchy.

- When a Playground is launched, it loads the themes, plugins, and content specified in your BluePrint. Any custom themes and plugins not in the WordPress repository will be archived on your server as ZIP files and downloaded on demand.

- Quick Playground loads a copy of itself into the Playground environment and assists with copying over content.

- If you obtain a Pro license key, a plugin with additional capabilities for saving and syncing content will be loaded into the Playground (not your live website).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/quick-playground` directory, or install the plugin through the WordPress plugins screen directly.

2. Activate the plugin through the 'Plugins' screen in WordPress.

3. Navigate to "Quick Playground" (between Appearance and Plugins) in the WordPress administrator's menu to access the plugin's features.

== Frequently Asked Questions ==

= What is the WordPress Playground? =  

The WordPress Playground is a version of WordPress software that runs in your web browser without requiring a server. It allows you to test themes, plugins, and design changes in a safe environment.

= Can I sync changes back to my live website? =  

Yes, the plugin includes an experimental feature for syncing changes back to your live website. However, this feature is still under development and should be used with caution.

= Is my live website affected by the changes I make in the Playground? =  

No, the Playground environment is completely separate from your live website. Changes made in the Playground do not affect your live website unless you explicitly sync them back.

== Changelog ==

= 1.0.0 =  

* Initial release.

== License ==

This plugin is licensed under the GPLv2 or later. See the [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html) for more details.

The Faker library, bundled with the plugin, is included under its own open source license