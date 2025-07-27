<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Initializes and manages playground blueprint settings and profiles.
 *
 * @param string $profile    The profile name.
 * @param string $stylesheet The current theme stylesheet.
 */
function blueprint_settings_init($profile) {
    global $pagenow;
    $key = function_exists(('playground_premium_enabled')) ? playground_premium_enabled() : '';
    if(isset($_POST['build_profile'])) {  
        //sanitization is done in qckply_build
        $result = qckply_build($_POST,$profile);
        $blueprint = $result[0];
        $settings = $result[1];
        $cachemessage = qckply_cache_message($profile,$settings);
        //variables are sanitized in qckply_get_button. output includes svg code not compatible with wp_kses_post. was not able to get it work with wp_kses and custom tags
        printf('<div class="notice notice-success"><p>Updated</p><p>%s</p>%s</div>',qckply_get_button(['profile'=>$profile, 'key'=>$key]), wp_kses_post($cachemessage));
        update_option('quickplay_clone_settings_'.$profile,$settings);
        if(!empty($_POST['reset_cache'])) {
            //post variables are sanitized in qckply_delete_caches
            qckply_delete_caches($_POST['reset_cache'],$profile);
        }
    }
    else {
        $blueprint = get_option('playground_blueprint_'.$profile, array());
        $settings = get_option('quickplay_clone_settings_'.$profile,array());
        //variables are sanitized in qckply_get_button. output includes svg code not compatible with wp_kses_post. was not able to get it work with wp_kses and custom tags
        echo qckply_get_button(['profile'=>$profile, 'key'=>$key]);
        echo wp_kses_post(qckply_cache_message($profile,$settings));
    }
    $pp = get_option('playground_profiles',array('default'));
    if(!in_array($profile,$pp))
    {
        $pp[] = $profile;
        update_option('playground_profiles',$pp);
    }
    $ppoptions = '';

    foreach($pp as $key => $value) {
        if(empty($value)) {
            unset($pp[$key]);
        }
        $ppoptions .= sprintf('<option value="%s" %s>%s</option>',$value, ($value == $profile) ? ' selected="selected" ' : '', $value);
    }
    $ppoptions .= sprintf('<option value="add_custom">%s</option>', esc_html__('Add New Profile','quick-playground'));
    if(isset($_GET['reset']) || empty($blueprint)) {
        $page_on_front = intval(get_option('page_on_front'));
        $settings = array();
        $settings['page_on_front'] = $page_on_front;
        $settings['show_on_front'] = get_option('show_on_front');
        $settings['blogname'] = get_option('blogname');
        $settings['blogdescription'] = get_option('blogdescription'); 
        $settings['page_for_posts'] = intval(get_option('page_for_posts'));
        $settings['copy_pages'] = 0;
        $settings['copy_blogs'] = 10;
        $settings['copy_events'] = 1;
        $settings['key_pages'] = 1;
        $settings['origin_stylesheet'] = get_stylesheet();
    }
    if(isset($_GET['reset']) || empty($blueprint)) {
        $postvars['settings'] = $settings;
        $postvars['add_theme'][] = get_stylesheet();
        $postvars['profile'] = $profile;
        //sanitization is done in qckply_build
        $result = qckply_build($postvars,$profile);
        $blueprint = $result[0];
        $settings = $result[1];
        update_option('quickplay_clone_settings_'.$profile,$settings);
    }
    $page = sanitize_text_field($pagenow);
    $pagechoice = 'qckply_builder' == $page ? '<input type="radio" name="page" value="quickplayground" /> Gallery <input type="radio" name="page" value="qckply_builder" checked="checked" /> Builder ' : '<input type="radio" name="page" value="quickplayground" checked="checked" /> Gallery <input type="radio" name="page" value="qckply_builder" /> Builder ';
    printf('<form method="get" action="%s" class="qckply-form" > <div id="switch_add_profile">Profile: <select name="profile">%s</select> %s <button>Switch</button></div>%s</form>',esc_attr(admin_url('admin.php')),wp_kses($ppoptions, qckply_kses_allowed()),wp_kses($pagechoice, qckply_kses_allowed()),wp_nonce_field('quickplayground','playground',true,false));
}
