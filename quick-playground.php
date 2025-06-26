<?php

/**

 * Plugin Name: Quick Playground

 * Plugin URI:  https://davidfcarr.com/design-plugin-playground

 * Description: Quickly create Theme and Plugin demo, testing, and staging websites in WordPress Playground.

 * Version:     1.0.0

 * Author:      davidfcarr

 * License:     GPL2

 */

require_once('clone.php');
require_once('utility.php');
require_once('api.php');
require_once('makeBlueprintItem.php');
require_once('blueprint-builder.php');
require_once('premium.php');
require_once('build.php');
require_once("key_pages.php");
require_once('quickplayground_design_clone.php');
require_once('quickplayground-sync.php');
require_once('quickplayground-test.php');
require_once('blueprint-settings-init.php');
require_once('filters.php');
require_once 'faker/src/autoload.php';

if(is_multisite())
    require_once('networkadmin.php');
$temp = wp_upload_dir();
$playground_uploads = $temp['basedir'];
$playground_uploads = rtrim(preg_replace('/sites.+/','',$playground_uploads),'/');
$playground_uploads_url = $temp['baseurl'];
$playground_uploads_url = rtrim(preg_replace('/sites.+/','',$playground_uploads_url),'/');

unset($temp);

function quickplayground() {
    if(!empty($_POST) && !wp_verify_nonce( $_POST['playground'], 'quickplayground' ) ) 
    {
        echo '<h2>Security Error</h2>';
        return;
    }

    global $wpdb, $current_user, $playground_uploads, $playground_uploads_url;

    $profile = isset($_REQUEST['profile']) ? preg_replace('/[^a-z0-9]+/','_',strtolower(sanitize_text_field($_REQUEST['profile']))) : 'default';
    $origin_url = rtrim(get_option('siteurl'),'/');
    $stylesheet = get_stylesheet();
    blueprint_settings_init($profile,$stylesheet);
    $blueprint = get_option('playground_blueprint_'.$profile, array());
    $settings = get_option('playground_clone_settings_'.$profile,array());
    $stylesheet = $settings['clone_stylesheet'] ?? $stylesheet;
    $key = playground_premium_enabled();
    $button = quickplayground_get_button($profile, $key);
    $playground_api_url = rest_url('quickplayground/v1/blueprint/'.$profile).'?x='.time().'&user_id='.$current_user->ID;
    $clone_api_url = rest_url('quickplayground/v1/playground_clone/'.$profile);

printf('<h2>Quick Playground for %s</h2>',get_bloginfo('name'));

    printf('<p>Loading saved blueprint for profile %s with %d steps defined. You can add or modify the themes, plugins, and settings on the <a href="%s">plugin builder page</a>.</p>',htmlentities($profile),sizeof($blueprint['steps']),admin_url('admin.php?page=quickplayground_builder'));
echo $button;

printf('<form method="post" action="%s"><input type="hidden" name="build_profile" value="1"> %s ',admin_url('admin.php?page=quickplayground_builder'), wp_nonce_field('quickplayground','playground',true,false));
$themeslots = 1;
quickplayground_theme_options($blueprint, $stylesheet, $themeslots);
quickplayground_plugin_options($blueprint);
printf('<input type="hidden" name="keep_settings" value="1" >');
printf('<p><input type="checkbox" name="settings[key_pages]" value="1" %s > Include key pages and posts (linked to from the home page or menu)</p>',(!isset($settings['key_pages']) || $settings['key_pages']) ? ' checked="checked" ' : '');
printf('<input type="hidden" name="profile" value="%s" />',$profile);
echo '<p><button>Submit</button></p>';
echo '</form>';

echo '<div class="playground-doc">';
if($key) {
    echo "<p>Quick Playground allows you to test themes, plugins, design ideas, and configuration settings on a virtual WordPress Playground copy of your website, without worrying about breaking your live site.</p>";
    printf('<p>Premium features enabled. Themes and plugins that are not in the WordPress repository but are installed on this machine will be published to the playground as zip files saved to %s</p>',$playground_uploads);
    echo "<p>Your website content and settings are not shared with any external cloud service. The playground is a private instance of WordPress loaded into your web browser.</p>";
} else {
    $upgrade_url = admin_url('admin.php?page=quickplayground_pro');
    echo "<p>Quick Playground allows you to test themes, plugins, design ideas, and configuration settings on a virtual WordPress Playground copy of your website, without worrying about breaking your live site.</p>";
    echo "<p>The Pro version allows you greater freedom to customize the themes and plugins installed, including custom themes and plugins that are not in the WordPress.org repository, and provides more tools for developers to create demo environments.</p>";
    printf('<p><a href="%s">Upgrade</a> for access to these features.</p>
    <ul class="playgroundpro">
    <li>Install and active Themes and Plugins that are different from those on your live website</li>
    <li>Demonstrate Themes and Plugins that are not in the WordPress repository (served as zip files from your server instead)</li>
    <li>Specify a different front page from the one used your live website</li>
    <li>Publish additional demo pages to the playground, based on draft or published pages of your website</li>
    <li>Extend the plugin with custom filters and actions</li>
    <li>Call custom PHP code in the playground.</li>
    </ul>
    <p><strong>For a limited time, you can get the Pro version just by signing up for the Quick Playground Email List. <a href="%s">Upgrade Now</a>.</strong></p>
    ',$upgrade_url,$upgrade_url);
    echo "<p>Your website content and settings are not shared with any external cloud service. The playground is a private instance of WordPress loaded into your web browser.</p>";
}
echo '</div>';
    echo '<div class="playground-theme-previews">';
    $themes = wp_get_themes(['allowed'=>true]);
    foreach($themes as $theme) {
        if($theme->stylesheet == $stylesheet)
            continue;
        $button = quickplayground_get_button($profile,$theme->stylesheet, $key);
        $screenshot = $theme->get_screenshot(); ///get_stylesheet_directory_uri().'/screenshot.png';
        printf('<div class="playground-stylesheet"><div style="">Theme: %s</div><div class="playground-theme-screenshot"><img src="%s" width="300" /></div><div class="playground-theme-button">%s</div></div>',$theme->Name,$screenshot,$button);
        //print_r($theme->get_screenshot);
    }
    echo '</div>';
}

function quickplayground_enqueue_admin_script( $hook ) {
    if ( !strpos($hook,'quickplayground') ) {
        return;
    }
    wp_enqueue_script( 'quickplayground_script', plugin_dir_url( __FILE__ ) . 'quickplayground.js', array(), '1.0' );
    wp_enqueue_style( 'quickplayground_style', plugin_dir_url( __FILE__ ) . 'quickplayground.css', array(), '1.0' );
}
add_action( 'admin_enqueue_scripts', 'quickplayground_enqueue_admin_script' );

/**
 * Generates the API URL for the playground based on the profile and optional parameters.
 *
 * @param string $profile The profile name.
 * @param string $stylesheet Optional. The stylesheet to use.
 * @param string $key Optional. A key for premium features.
 * @return string The API URL.
 */
function get_playground_api_url($profile,$stylesheet = '',$key='') {
    global $current_user;
    $playground_api_url = rest_url('quickplayground/v1/blueprint/'.$profile).'?x='.time().'&user_id='.$current_user->ID;
    if($stylesheet)
        $playground_api_url .= '&stylesheet='.$key;
    if($key)
        $playground_api_url .= '&key='.$key;
    return $playground_api_url;
}

/**
 * Generates a button to access the playground for a specific profile.
 *
 * @param string $profile The profile name.
 * @param string $stylesheet Optional. The stylesheet to use.
 * @param string $key Optional. A key for premium features.
 * @return string HTML for the button.
 */
function quickplayground_get_button($profile,$stylesheet = '',$key='') {
global $current_user;
$playground_api_url = get_playground_api_url($profile,$stylesheet = '',$key='');

return sprintf('<a target="_blank" href="%s" style="
  background-color: #004165;
  color: white;
  font-size: 16px;
  font-family: \'Comic Sans MS\', cursive, sans-serif;
  border: none;
  border-radius: 12px;
  padding: 12px 20px;
  display: inline-flex;
  align-items: center;
  text-decoration: none;
  cursor: pointer;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s;
"
  onmouseover="this.style.transform=\'scale(1.05)\'"
  onmouseout="this.style.transform=\'scale(1)\'"
>
<svg fill="#FFFFFF" height="50px" width="50px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
	 viewBox="0 0 512.001 512.001" xml:space="preserve">
<g>
	<g>
		<path d="M501.335,170.587h-352v-21.333h10.667c4.181,0,7.979-2.453,9.728-6.251c1.728-3.819,1.067-8.299-1.685-11.435
			L93.377,46.235c-4.053-4.651-12.011-4.651-16.064,0L2.647,131.569c-2.752,3.157-3.435,7.616-1.685,11.435
			c1.728,3.797,5.525,6.251,9.707,6.251h10.667v309.333c0,5.888,4.779,10.667,10.667,10.667s10.667-4.779,10.667-10.667v-10.667
			h85.333v10.667c0,5.888,4.779,10.667,10.667,10.667s10.667-4.779,10.667-10.667V191.921h64V300.55
			c-12.395,4.416-21.333,16.149-21.333,30.037c0,17.643,14.357,32,32,32s32-14.357,32-32c0-13.888-8.939-25.621-21.333-30.037
			V191.921h85.333v87.296c-12.395,4.416-21.333,16.149-21.333,30.037c0,17.643,14.357,32,32,32c17.643,0,32-14.357,32-32
			c0-13.888-8.939-25.621-21.333-30.037v-87.296h64v266.667c0,5.888,4.779,10.667,10.667,10.667c5.888,0,10.667-4.779,10.667-10.667
			V191.921h42.667v23.573c-6.4,2.667-10.667,7.531-10.667,13.76c0,6.229,4.267,11.093,10.667,13.76v31.147
			c-6.4,2.667-10.667,7.531-10.667,13.76s4.267,11.093,10.667,13.76v31.147c-6.4,2.667-10.667,7.531-10.667,13.76
			c0,6.229,4.267,11.093,10.667,13.76v31.147c-6.4,2.667-10.667,7.531-10.667,13.76c0,6.229,4.267,11.093,10.667,13.76v18.24
			c0,5.888,4.779,10.667,10.667,10.667c5.888,0,10.667-4.779,10.667-10.667v-18.24c6.4-2.667,10.667-7.531,10.667-13.76
			c0-6.229-4.267-11.093-10.667-13.76v-31.147c6.4-2.667,10.667-7.531,10.667-13.76c0-6.229-4.267-11.093-10.667-13.76v-31.147
			c6.4-2.667,10.667-7.531,10.667-13.76c0-6.229-4.267-11.093-10.667-13.76v-31.147c6.4-2.667,10.667-7.531,10.667-13.76
			s-4.267-11.093-10.667-13.76v-23.573h10.667c5.888,0,10.667-4.779,10.667-10.667S507.223,170.587,501.335,170.587z
			 M128.001,426.587H42.668v-42.667h85.333V426.587z M128.001,362.587H42.668v-42.667h85.333V362.587z M128.001,298.587H42.668
			v-42.667h85.333V298.587z M128.001,234.587H42.668v-85.333h85.333V234.587z M34.177,127.921l51.157-58.475l51.157,58.475H34.177z
			 M224.001,341.254c-5.888,0-10.667-4.779-10.667-10.667s4.779-10.667,10.667-10.667s10.667,4.779,10.667,10.667
			S229.889,341.254,224.001,341.254z M330.668,319.921c-5.888,0-10.667-4.779-10.667-10.667s4.779-10.667,10.667-10.667
			s10.667,4.779,10.667,10.667S336.556,319.921,330.668,319.921z"/>
	</g>
</g>
</svg>
&nbsp;&nbsp;&nbsp;  Go To Playground
</a>','https://playground.wordpress.net/?blueprint-url='.urlencode($playground_api_url)
);
}