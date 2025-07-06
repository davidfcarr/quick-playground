<?php
/**
 * Initializes and manages playground blueprint settings and profiles.
 *
 * @param string $profile    The profile name.
 * @param string $stylesheet The current theme stylesheet.
 */
function blueprint_settings_init($profile) {

    $key = playground_premium_enabled();
    
    if(isset($_POST['build_profile'])) {  
        $result = quickplayground_build($_POST,$profile);
        $blueprint = $result[0];
        $settings = $result[1];
        $button = quickplayground_get_button(['profile'=>$profile, 'key'=>$key]);
        $cachemessage = quickplayground_cache_message($profile,$settings);
        
        printf('<div class="notice notice-success"><p>Updated</p><p>%s</p>%s</div>',$button, $cachemessage);
        update_option('playground_clone_settings_'.$profile,$settings);
    }
    else {
        $blueprint = get_option('playground_blueprint_'.$profile, array());
        $settings = get_option('playground_clone_settings_'.$profile,array());
        echo quickplayground_get_button(['profile'=>$profile, 'key'=>$key]);
        echo quickplayground_cache_message($profile,$settings);
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
    $ppoptions .= sprintf('<option value="add_custom">%s</option>', __('Add New Profile','quick-playground'));
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
        $result = quickplayground_build($postvars,$profile);
        $blueprint = $result[0];
        $settings = $result[1];
        update_option('playground_clone_settings_'.$profile,$settings);
    }
    $page = sanitize_text_field($_GET['page']);    
    printf('<form method="get" action="%s" class="playground-form" ><input type="hidden" name="page" value="quickplayground_builder" /><div id="switch_add_profile">Profile: <select name="profile">%s</select> <button>Switch</button></div>%s</form>',esc_attr(admin_url('admin.php')),$ppoptions,wp_nonce_field('quickplayground','playground',true,false));
}
