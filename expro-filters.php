<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('qckply_form_demo_content','qckply_form_demo_pro_content',10,1);
/**
 * Adds RSVPMaker fields to form 
 *
 * @param array $settings      The list of settings to to determine what should be displayed.
*/

function qckply_form_demo_pro_content($settings) {

$types = get_post_types(array(
   'public'   => true,
   '_builtin' => false
),'objects','and');

if(!empty($types)) {
$output = '';
    foreach($types as $type) {
        if(strpos($type->name,'rsvp') !== false)
            continue;
        $checked = (!empty($settings['post_types']) && is_array($settings['post_types']) && in_array($type->name,$settings['post_types'])) ? ' checked="checked" ' : '';
        $output .= sprintf('<p><input type="checkbox" name="post_types[]" value="%s" %s > %s (%s)</p>',esc_attr($type->name),$checked,esc_html($type->label),esc_html($type->name));
    }
    if(!empty($output)) {
        echo '<h2>'.esc_html__('Additional Content Types','quick-playground').'</h2>';
        echo '<p>'.esc_html__('Content types added by plugins','quick-playground').'</p>';
        echo wp_kses_post($output);
    }
}

}

//do_action('qckply_form_steps',$blueprint,$profile);
add_action('qckply_form_steps','qckply_pro_form_steps',10,2);
function qckply_pro_form_steps($blueprint, $profile = 'default') {
    global $qckply_site_uploads;
    if(empty($blueprint)) {
        $blueprint = get_option('json_steps_'.$profile, '');
        if(empty($blueprint)) {
            $blueprint = [];
        } else {
            $blueprint = json_decode($blueprint, true);
        }
    }
    if(!is_array($blueprint)) {
        $blueprint = [];
    }
    if(!isset($blueprint['steps']) || !is_array($blueprint['steps'])) {
        $blueprint['steps'] = [];
    }
    $allowed_html = qckply_kses_allowed();
    
if(!empty($blueprint)) {
    $saved_code = '';
    foreach($blueprint['steps'] as $step) {
        if(!is_array($step))
            $step = (array) $step;
        if('runPHP' == $step['step']) {
            if(!strpos($step['code'],'qckply_clone')) {
            $saved_code = $step['code'];
            }
        }
    }
}
echo '<p>Additional themes or plugins from the WordPress repository<br /><textarea name="repo" cols="100" rows="3"></textarea><br>Example:<br /><em>https://wordpress.org/themes/oceanwp/</em><br /><em>https://wordpress.org/plugins/rsvpmaker/</em></p>';

printf('<p>Custom PHP code *<br /><textarea name="add_code[]" cols="100" rows="3">%s</textarea></p>',esc_html($saved_code));
echo '<p><code>&lt;?php require_once \'wordpress/wp-load.php\';</code> will be added automatically to enable WordPress and plugin functions.</p>';

printf('<p>Custom Step (JSON)<br /><textarea name="json_steps" cols="100" rows="3">%s</textarea></p><p>Example from the <a href="https://wordpress.github.io/wordpress-playground/blueprints/examples/">documentation</a>:</p><pre>%s</pre>',esc_html(get_option('json_steps_'.$profile)),'
{
    "step": "installPlugin",
    "pluginData": {
        "resource": "wordpress.org/plugins",
        "slug": "coblocks"
    }
},
{
    "step": "installTheme",
    "themeData": {
        "resource": "wordpress.org/themes",
        "slug": "pendant"
    }
}');
}

function qckply_pro_email() {
    return;
}

function qckply_cloning_code($profile) {
    if(get_option('qckply_disable_sync_'.$profile))
        return;
    $code = get_transient('qckply_sync_code');
    if(empty($code)) {
        $code = wp_generate_password(20, false, false);
        set_transient('qckply_sync_code',$code,DAY_IN_SECONDS);
    }
    return $code;
} 

add_action('qckply_sideload_saved_image','qckply_sideload_saved_image');
function qckply_sideload_saved_image($file = '') {
set_transient('qckply_sideload_url',var_export($file,true));
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
$id = media_sideload_image( $file, 0, 'playground uploaded image', 'id' );
if(!$id)
    return [];
set_transient('qckply_sideload_id',$id);
$meta = wp_get_attachment_metadata($id);
$meta['attachment_id'] = $id;
return $meta;
}
