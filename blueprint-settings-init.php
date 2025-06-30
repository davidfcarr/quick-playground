<?php
/**
 * Initializes and manages playground blueprint settings and profiles.
 *
 * @param string $profile    The profile name.
 * @param string $stylesheet The current theme stylesheet.
 */
function blueprint_settings_init($profile, $stylesheet) {

    if(isset($_POST['build_profile'])) {  
        $result = quickplayground_build($_POST,$profile);
        $blueprint = $result[0];
        $settings = $result[1];
        $key = playground_premium_enabled();
        $button = quickplayground_get_button(['profile'=>$profile, 'key'=>$key]);
        printf('<div class="notice notice-success"><p>Updated</p><p>%s</p></div>',$button);
        update_option('playground_clone_settings_'.$profile,$settings);
    }
    else
        $blueprint = get_option('playground_blueprint_'.$profile, array());

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
    $ppoptions .= sprintf('<option value="add_custom">%s</option>', __('Add New Profile','theme-plugin-playground'));
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
        $postvars['repo'] = 'https://wordpress.org/plugins/sql-buddy/';
        $result = quickplayground_build($postvars,$profile);
        $blueprint = $result[0];
        $settings = $result[1];
        update_option('playground_clone_settings_'.$profile,$settings);
    }    
    printf('<form method="get" action="%s" class="playground-form" ><input type="hidden" name="page" value="quickplayground" /><div id="switch_add_profile">Profile: <select name="profile">%s</select> <button>Switch</button></div>%s</form>',admin_url('admin.php'),$ppoptions,wp_nonce_field('quickplayground','playground',true,false));
}
