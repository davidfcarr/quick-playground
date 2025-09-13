<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Initializes and manages playground blueprint settings and profiles.
 *
 * @param string $profile    The profile name.
 * @param string $stylesheet The current theme stylesheet.
 */
function blueprint_settings_init($profile) {
    if(isset($_POST['build_profile'])) {
    //if the request includes anything other than $_GET['page'], check nonce
    if(sizeof($_REQUEST) > 1 && (empty( $_REQUEST['playground']) || !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['playground'])), 'quickplayground' ) )) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }  
        //iteratively applies sanitization to all values in the array
        $postvars = qckply_sanitize(wp_unslash($_POST));
        $result = qckply_build($postvars,$profile);
        $blueprint = $result[0];
        $settings = $result[1];
        $cachemessage = qckply_cache_message($profile,$settings);
        update_option('quickplay_clone_settings_'.$profile,$settings);
        $display = isset($_POST['qckply_display']) ? array_map('sanitize_text_field',wp_unslash($_POST['qckply_display'])) : [];
        if($display['iframe'] == 'custom_sidebar' && empty($display['iframe_sidebar'])) {
            $new['post_content'] = qckply_sidebar_default();
            $new['post_title'] = 'Quick Playground Sidebar '.$profile;
            $new['post_status'] = 'private';
            $new['post_type'] = 'page';
            $display['iframe_sidebar'] = wp_insert_post($new);
            printf('<div class="notice notice-success"><p>Added custom sidebar, which you can <a target="_blank" href="%s">edit</a> as a private page.</p></div>',esc_attr(admin_url('post.php?action=edit&post='.$display['iframe_sidebar'])));
        }
        update_option('qckply_display_'.$profile,$display);

        if(!empty($_POST['reset_cache'])) {
            qckply_delete_caches(array_map('sanitize_text_field',$_POST['reset_cache']),$profile);
        }
        //variables escaped within qckply_get_button; output cannot be escaped by wp_kses without messing up svg code
        echo '<div class="notice notice-success"><p>Updated</p>';
        qckply_get_button(['profile'=>$profile],true);
        echo wp_kses_post($cachemessage);
        echo '</div>';
    }
    else {
        $blueprint = get_option('qckply_blueprint_'.$profile, array());
        $settings = get_option('quickplay_clone_settings_'.$profile,array());
        qckply_get_button(['profile'=>$profile],true);
        echo wp_kses_post(qckply_cache_message($profile,$settings));
    }
    $pp = get_option('qckply_profiles',array('default'));
    if(!in_array($profile,$pp))
    {
        $pp[] = $profile;
        update_option('qckply_profiles',$pp);
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
        $settings['qckply_key_pages'] = 1;
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
    $screen = get_current_screen();
    $pagechoice = strpos($screen->id,'builder') ? '<input type="radio" name="page" value="quickplayground" /> Gallery <input type="radio" name="page" value="qckply_builder" checked="checked" /> Builder ' : '<input type="radio" name="page" value="quickplayground" checked="checked" /> Gallery <input type="radio" name="page" value="qckply_builder" /> Builder ';
    printf('<form method="get" action="%s" class="qckply-form" > <div id="switch_add_profile"><label>Profile</label> <select id="qckply-switcher" name="profile">%s</select> %s <button>Switch</button></div>',esc_attr(admin_url('admin.php')),wp_kses($ppoptions, qckply_kses_allowed()),wp_kses($pagechoice, qckply_kses_allowed()));
    wp_nonce_field('quickplayground','playground',true,true);
    echo '</form>';
}
