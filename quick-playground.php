<?php
/**
 * Plugin Name: Quick Playground
 * Plugin URI:  https://quickplayground.com
 * Description: Preview your content in different themes or test plugins using WordPress Playground. Quickly create Theme and Plugin demo, testing, and staging websites.
 * Version:     0.9.5
 * Author:      David F. Carr
*  License:     GPL2
*  Text Domain: quick-playground
*  Domain Path: /languages
*/
require_once('includes.php');

function qckply_get_directories()
{
    $qckply_directories = get_option('qckply_directories', array());
    if(!empty($qckply_directories)) {
        return $qckply_directories;
    }
    $temp = wp_upload_dir();
    $qckply_directories['site_uploads'] = $temp['basedir'].'/quick-playground';
    $qckply_directories['uploads'] = preg_replace('/sites.+\/quick-playground/','quick-playground',$qckply_directories['site_uploads']);
    if(!is_dir($qckply_directories['site_uploads'])) {
        wp_mkdir_p($qckply_directories['site_uploads']);
    }
    if($qckply_directories['uploads'] != $qckply_directories['site_uploads'] && !is_dir($qckply_directories['uploads'])) {
        wp_mkdir_p($qckply_directories['uploads']);
    }
    $qckply_directories['site_uploads_url'] = $temp['baseurl'].'/quick-playground';
    $qckply_directories['uploads_url'] = preg_replace('/sites.+\/quick-playground/','quick-playground',$qckply_directories['site_uploads_url']);
    unset($temp);
    update_option('qckply_directories',$qckply_directories);
    return $qckply_directories;
}

/**
 * Main function for the Quick Playground admin page.
 *
 * Handles form submission, loads and displays the current blueprint and settings,
 * and outputs the UI for managing playground profiles, themes, and plugins.
 */
function quickplayground() {
if((!empty($_POST) || isset($_REQUEST['update']) || isset($_REQUEST['reset'])) && (empty( $_REQUEST['playground']) || !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['playground'])), 'quickplayground' ) )) 
{
    echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
    return;
}
    do_action('qckply_form_top');
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];

    //nonce is checked above
    $profile = isset($_REQUEST['profile']) ? preg_replace('/[^a-z0-9]+/','_',strtolower(sanitize_text_field($_REQUEST['profile']))) : 'default';
    printf('<h2>Quick Playground for %s: %s</h2>',esc_html(get_bloginfo('name')),esc_html($profile));
    $stylesheet = get_stylesheet();
    blueprint_settings_init($profile);
    $origin_url = rtrim(get_option('siteurl'),'/');
    $blueprint = get_option('playground_blueprint_'.$profile, array());
    $settings = get_option('quickplay_clone_settings_'.$profile,array());
    $stylesheet = $settings['qckply_clone_stylesheet'] ?? $stylesheet;
    printf('<p>Theme: %s, Plugins: %s. For Customization options, see the <a href="%s">Playground Builder page</a>.</p>',esc_html($stylesheet),esc_html(implode(', ', qckply_plugin_list($blueprint))),esc_attr(admin_url('admin.php?page=qckply_builder')));
echo '<div class="qckply-doc">';

    $welcome_message = "<p>Quick Playground allows you to test themes, plugins, design ideas, and configuration settings on a virtual WordPress Playground copy of your website, without worrying about breaking your live site.</p>";
    $welcome_message .= "<p>Learn more about what it can do at <a href=\"https://quickplayground.com\">quickplayground.com</a>.</a>";
    echo wp_kses_post(apply_filters('qckply_welcome',$welcome_message));

    echo "<p>Your website content and settings are not shared with any external cloud service. The playground is a private instance of WordPress loaded into your web browser.</p>";
echo '</div>';
    $themes = wp_get_themes(['allowed'=>true]);
    if(!empty($themes) && sizeof($themes) > 1) {
        echo '<h2>Playground Design Gallery</h2><p>See how your website would look with any of the WordPress themes shown below.</p>';
    echo '<div class="qckply-theme-previews">';
    foreach($themes as $theme) {
        if($theme->stylesheet == $stylesheet)
            continue;
        $blueprint_url = get_qckply_api_url(['profile'=>$profile,'stylesheet'=>$theme->stylesheet]);
        $screenshot = $theme->get_screenshot(); ///get_stylesheet_directory_uri().'/screenshot.png';
        //variables are sanitized in qckply_get_button. output includes svg code not compatible with wp_kses_post. was not able to get it work with wp_kses and custom tags
        printf('<div class="qckply-stylesheet"><div style="">Theme: %s</div><div class="qckply-theme-screenshot"><img src="%s" width="300" /></div><div class="qckply-theme-button">%s<br /></div><p><a href="%s">BluePrint JSON</a></p>%s</div>',esc_html($theme->Name),esc_attr($screenshot),qckply_get_button(['profile'=>$profile,'stylesheet'=>$theme->stylesheet]),$blueprint_url,qckply_get_blueprint_link(['profile'=>$profile,'stylesheet' =>$theme->stylesheet]));
    }
    echo '</div>';
    }
}

/**
 * Enqueues admin scripts and styles for Quick Playground admin pages.
 *
 * @param string $hook The current admin page hook.
 */
function qckply_enqueue_admin_script( $hook = '' ) {
    if ( !strpos($hook,'qckply') && !strpos($hook,'quickplayground') && !qckply_is_playground()) {
        return;
    }
    wp_enqueue_script( 'qckply_script', plugin_dir_url( __FILE__ ) . 'quickplayground.js', array(), '1.0' );
    wp_enqueue_style( 'qckply_style', plugin_dir_url( __FILE__ ) . 'quickplayground.css', array(), '1.0'.time() );
}
function qckply_enqueue_script( $hook = '' ) {
    if ( qckply_is_playground() ) {
        wp_enqueue_script( 'qckply_script', plugin_dir_url( __FILE__ ) . 'quickplayground.js', array(), '1.0' );
        wp_enqueue_style( 'qckply_style', plugin_dir_url( __FILE__ ) . 'quickplayground.css', array(), '1.0'.time() );
    }
}

add_action( 'admin_enqueue_scripts', 'qckply_enqueue_admin_script' );
add_action( 'wp_enqueue_scripts', 'qckply_enqueue_script' );



/**
 * Generates the API URL for the playground based on the profile and optional parameters.
 *
 * @param string $profile    The profile name.
 * @return string            The API URL.
 */
function get_qckply_api_url($args=[]) {
    global $current_user;
    if(isset($args['url']))
        return 'https://playground.wordpress.net/?blueprint-url='.urlencode($args['url']);
    if(empty($args['profile'])) {
        $profile = 'default';
    }
    else {
        $profile = sanitize_text_field($args['profile']);
    }
    $args = apply_filters('qckply_api_url_args',$args);
    $args['t'] = time();
    unset($args['profile']);
    $display = get_option('qckply_display_'.$profile,[]);
    if(isset($args['iframe']))
        $display['iframe'] = sanitize_text_field($args['iframe']);
    if('no_iframe' != $display['iframe']) {
    $getv = ['qckply'=>$profile,'domain'=>empty($args['domain']) ? sanitize_text_field($_SERVER['SERVER_NAME']) : sanitize_text_field($args['domain'])];
    if('no_sidebar' != $display['iframe'] && !empty($display['iframe_sidebar']))
        $getv['sidebar'] = intval($display['iframe_sidebar']); 
    if('no_sidebar' == $display['iframe'])
        $getv['no_sidebar'] = 1;
    foreach($args as $key => $value) {
        $getv[$key] = $value;
    }
    $qckply_api_url = add_query_arg($getv,site_url('qpi')); 
    return $qckply_api_url;
    }
    $qckply_api_url = rest_url('quickplayground/v1/blueprint/'.$profile).'?x='.time().'&user_id='.$current_user->ID;
    foreach($args as $key => $value) {
        if(!empty($value)) {
            $qckply_api_url .= '&'.sanitize_text_field($key).'='.sanitize_text_field($value);
        }
    }
    $qckply_api_url = 'https://playground.wordpress.net/?blueprint-url='.urlencode($qckply_api_url);
    return $qckply_api_url;
}

add_shortcode('qckply_button', 'qckply_get_button_shortcode');
function qckply_get_button_shortcode($args) {
    if(empty($args['domain']))
        $args['domain'] = sanitize_text_field($_SERVER['SERVER_NAME']);
    if(empty($args['key']))
        $args['is_demo'] = '1';
    return qckply_get_button($args);
}
/**
 * Generates a button to access the playground for a specific profile.
 *
 * @param string $profile    The profile name.
 * @param string $stylesheet Optional. The stylesheet to use.
 * @param bool   $nocache    Optional. Whether to disable cache.
 * @return string            HTML for the button.
 */
function qckply_get_button($args = ['profile' => 'default']) {
global $current_user;
$qckply_api_url = get_qckply_api_url($args);// empty($args['url']) ? get_qckply_api_url($args) : sanitize_text_field($args['url']).'&random='.rand();

$button = sprintf('<div><a target="_blank" href="%s" style="
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
</a></div>',$qckply_api_url
);
return $button.'<p><small>';
}

/**
 * Outputs a public link to the playground blueprint for a given profile and stylesheet.
 *
 * @param string $profile    The profile name.
 * @param string $stylesheet Optional. The stylesheet to use.
 */
function qckply_get_blueprint_link($args = ['profile'=>'default','stylesheet' => '']) {
$args['is_demo'] = true;
$qckply_api_url = get_qckply_api_url($args);
return '<p><a href="'.$qckply_api_url.'">Public Link</a></p>';
}

function qckply_print_button_shortcode($args = ['profile'=>'default','is_demo' => '1']) {
//$qckply_api_url = get_qckply_api_url($args);
echo '<h3>Quick Playground Button Shortcode for Demos</h3>';
echo '<p>[qckply_button ';
if(empty($args['domain']))
    $args['domain'] = sanitize_text_field($_SERVER['SERVER_NAME']);
if(empty($args['key']))
    $args['is_demo'] = '1';
foreach($args as $key => $value) {
    if(!empty($value)) {
        echo esc_attr($key).'="'.esc_attr($value).'" ';
    }
}
echo ']</p>';
echo '<p>Optional attributes:</p> <ul><li>stylesheet="twentytwentyfive"</li><li>iframe="no_sidebar" OR iframe="no_iframe" OR iframe="custom_iframe"</li><li></li></ul>';
}