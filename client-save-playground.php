<?php

function qckply_clone_save_playground($clone) {
	global $wpdb;
    $profile = get_option("qckply_profile",'default');
    printf('<h2>Saving Playground Data for %s</h2>',esc_html($profile));
    $sync_origin = get_option('qckply_sync_origin');
    $site_dir = '/wp-content/uploads'.get_option('qckply_site_dir');
    $qckply_mysite_url = site_url();
    $origin_stylesheet = get_option('origin_stylesheet');
    $save_posts_url = $sync_origin.'/wp-json/quickplayground/v1/save_posts/'.$profile;
    $save_settings_url = $sync_origin.'/wp-json/quickplayground/v1/save_settings/'.$profile;
    $save_meta_url = $sync_origin.'/wp-json/quickplayground/v1/save_meta/'.$profile;
    $save_custom_url = $sync_origin.'/wp-json/quickplayground/v1/save_custom/'.$profile;
    $save_prompts_url = $sync_origin.'/wp-json/quickplayground/v1/save_prompts/'.$profile;
    $post_ids = [];

    $local_directories = qckply_get_directories();
    $remote_directories = get_option('qckply_origin_directories');
    if(!empty($clone['posts']))
    {
        $clone = qckply_posts_related($clone, true);
        $json = json_encode($clone);
        if(!$json) {
            foreach($clone['posts'] as $index => $p) {
                $p = (array) $p;
                if(in_array($p['ID'],$post_ids))
                    continue;
                $post_ids[] = $p['ID'];
                $fix = false;
                foreach($p as $field => $value) {
                printf('<p>check %s encoding post content ID %d title %s value %s</p>',esc_html($field),esc_html($p['ID']),esc_html($p['post_title']),esc_html(substr($value,0,50)));
                $clean = wp_check_invalid_utf8( $value );
                    if($clean !== $value) {
                        $fix = true;
                        $p[$field] = $clean;
                        printf('<p>fix %s encoding post content ID %d title %s</p>',esc_html($field),esc_html($p['ID']),esc_html($p['post_title']));
                    }
                }
                if($fix) {
                    $clone['posts'][$index] = (object) $p;
                }
            }
            $json = json_encode($clone);
        }
        if(!$json) {
            echo '<p>Error encoding JSON: '.esc_html(json_last_error_msg()).'</p>';
        }
        else
            printf('<h3>Save %d Posts and %s Related Items</h3><p>Sample JSON: %s</p>',empty($clone['posts']) ? 0 : sizeof($clone['posts']),empty($clone['related']) ? 0 : sizeof($clone['related']),esc_html(substr($json,0,300)));
        $json = qckply_json_outgoing($json,$sync_origin.$site_dir);
        $response = wp_remote_post($save_posts_url, array(
            'body' => $json,
            'headers' => array('Content-Type' => 'application/json')
        ));
    $status_code = wp_remote_retrieve_response_code( $response );
    if(is_wp_error($response)) {
        echo '<p>Error: '.esc_html( htmlentities($response->get_error_message()) ).'</p>';
    }
    elseif(200 != $status_code) {
        echo '<p>Error: HTTP status code '.esc_html( $status_code ).'</p>';
        if(!empty($returned['message']))
        printf('<div class="notice notice-error"><p>Error saving over the network. Try <a href="%s">downloading</a> instead.</p></div>',esc_attr(rest_url('quickplayground/v1/download_json/'.$profile)));
        printf('<p>%s</p>',esc_html('Server message: '.$returned['message']));
        printf('<p><a href="%s">Retry</a></p>',esc_attr(admin_url('admin.php?page=qckply_save')));
        echo '<p>If you see this repeatedly, please report the issue via <a href="https://wordpress.org/support/plugin/quick-playground/">https://wordpress.org/support/plugin/quick-playground/</a></p>';
        $file = 'quickplayground_posts_'.$profile.'.json';
        $filename = trailingslashit($local_directories['site_uploads']).$file;
        file_put_contents($filename,$json);
        return;
    }
    else {
        $returned = $response['body'];
        if(!is_array($returned)) {
            $returned = json_decode($returned, true);
        }
        if(!is_array($returned)) {
            echo 'not an array: '.esc_html(var_export($returned,true));
        }
        if(!empty($returned['saved']))
        printf('<p>Saved %s bytes of post content to %s</p>',esc_html($returned['saved']),esc_html($returned['file']));
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html('Server message: '.$returned['message']));
   }
    }
    $clone = [];
    $clone['settings']['cache_created'] = time();
    $updated_options = get_option('qckply_updated_options',[]);
    foreach($updated_options as $option) {
        $v = get_option($option);
        $clone['settings'][$option] = $v;
    }
    $clone = apply_filters('qckply_clone_save_settings',$clone);
    printf('<h3>Saving %d Settings</h3>',esc_html(sizeof($clone['settings'])));
    $clone = qckply_json_outgoing(json_encode($clone),$sync_origin.$site_dir);
    $response = wp_remote_post($save_settings_url, array(
        'body' => $clone,
        'headers' => array('Content-Type' => 'application/json')
    ));

    if(is_wp_error($response)) {
        echo '<p>Error: '.esc_html( htmlentities($response->get_error_message()) ).'</p>';
        return;
    } else {
        $returned = $response['body'];
        if(!is_array($returned)) {
            $returned = json_decode($returned, true);
        }
        if(!is_array($returned)) {
            echo 'not an array: '.esc_html(var_export($returned,true));
        }
        if(!empty($returned['saved']))
        printf('<p>Saved %s bytes of settings to %s %s</p>',esc_html($returned['saved']),esc_html($returned['file']),($returned['saved'] < 10) ? '(probable error)' : '');
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html($returned['message']));
   }

   /*
    $clone = [];
    $clone['related'] = qckply_posts_related($post_ids);
    $clone['postmeta'] = $wpdb->get_results("SELECT * FROM $wpdb->postmeta");
    $clone['users'] = $wpdb->get_results("SELECT * FROM $wpdb->users");
    $clone['usermeta'] = $wpdb->get_results("SELECT * FROM $wpdb->usermeta");
    $clone = apply_filters('qckply_clone_save_meta',$clone);
    
    $clone = qckply_json_outgoing(json_encode($clone),$sync_origin.$site_dir);
    echo '<h3>Saving Meta and Taxonomy Data</h3>';

    $response = wp_remote_post($save_meta_url, array(
        'body' => $clone,
        'headers' => array('Content-Type' => 'application/json')
    ));

    if(is_wp_error($response)) {

        echo '<p>Error: '.esc_html( htmlentities($response->get_error_message()) ).'</p>';

    } else {

        $returned = $response['body'];

        if(!is_array($returned)) {

            $returned = json_decode($returned, true);

        }

        if(!is_array($returned)) {

            echo 'not an array: '.esc_html( var_export($returned,true) );

        }
        if(!empty($returned['saved']))
        printf('<p>Saved %s bytes of metadata</p>',esc_html($returned['saved']));
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html($returned['message']));
   }
    */

   $clone = qckply_custom_tables_clone([]);
    printf('<h3>Checking for Custom Table Data</h3><p>%s</p>',esc_html( var_export(!empty($clone['custom_tables']),true)) );
    if(!empty($clone['custom_tables']))
    {
    echo '<h3>Saving Custom Table Data</h3>';
    $clone = qckply_json_outgoing(json_encode($clone),$sync_origin.$site_dir);
    $response = wp_remote_post($save_custom_url, array(
        'body' => $clone,
        'headers' => array('Content-Type' => 'application/json')
    ));

    if(is_wp_error($response)) {
        echo '<p>Error: '.esc_html( htmlentities($response->get_error_message()) ).'</p>';
    } else {

        $returned = $response['body'];

        if(!is_array($returned)) {

            $returned = json_decode($returned, true);

        }

        if(!is_array($returned)) {
            echo 'not an array: '.esc_html(var_export($returned,true));
        }
        if(!empty($returned['saved']))
        printf('<p>Saved %s bytes to %s %s</p>',esc_html($returned['saved']),esc_html($returned['file']),($returned['saved'] < 10) ? '(probable error)' : '');
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html($returned['message']));
   }

}
    printf('<h3 style="color: green; font-weight: bold;">Playground save complete %s</h3>',esc_html(date('r')) );
    printf('<p><a href="%s" target="_blank">Save to Live Website</a> - administrator approval required on %s.</p>',esc_attr($sync_origin.'/wp-admin/admin.php?page=qckply_sync'),esc_html($sync_origin));
}

function qckply_posts() {
    global $wpdb;
    $qckply_mysite_url = site_url();
    $top = qckply_top_ids();
    $sync_origin = get_option('qckply_sync_origin');
    $site_dir = '/wp-content/uploads'.get_option('qckply_site_dir');
    $search_path = $qckply_mysite_url.'/wp-content/uploads';
    $replace_path = $sync_origin.$site_dir;
    $sql = $wpdb->prepare("SELECT * FROM %i WHERE post_status='publish' AND post_type != 'attachment' AND post_modified > %s ORDER BY post_modified DESC",$wpdb->posts, $top['post_modified']);
    $posts = $wpdb->get_results($sql);
    printf('<p>Found %d posts modified since last sync with %s</p>',esc_html(count($posts)),esc_html($sql));
    $ids = [];
    foreach($posts as $index => $post) {
        printf('<p>ID %s %s (%s) %s</p>',esc_html($post->ID),esc_html($post->post_title),esc_html($post->post_type),esc_html($post->post_modified));
        $ids[] = $post->ID;
    }
    return ['posts'=>$posts,'ids'=>$ids];
}

function qckply_save() {
    global $wpdb;    
    printf('<h1>%s</h1><p>%s</p>',esc_html__('Save and Sync Playground','quick-playground'),esc_html__('You can save the current state of the Playground for future sessions or to sync to your live website.','quick-playground'));
    if(!empty($_POST) && !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['playground'])), 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }
    qckply_upload_images_form();
    if(!empty($_POST))
        return;
    $origin_stylesheet = get_option('origin_stylesheet');
    $sync_origin = $qckply_baseurl = get_option('qckply_sync_origin');
    $no_cache = get_option('qckply_no_cache',false);
    $qckply_profile = get_option('qckply_profile','default');
    $url = $qckply_baseurl .'/wp-json/quickplayground/v1/clone_posts/'.$qckply_profile.'?t='.time();
    if($no_cache) $url .= '&nocache=1';
    $taxurl = $qckply_baseurl .'/wp-json/quickplayground/v1/clone_taxonomy/'.$qckply_profile.'?t='.time();
    if($no_cache) $taxurl .= '&nocache=1';
    $imgurl = $qckply_baseurl .'/wp-json/quickplayground/v1/clone_images/'.$qckply_profile.'?t='.time();
    if($no_cache) $imgurl .= '&nocache=1';
    $sync_date = get_option('qckply_sync_date');
    $qckply_sync_code = get_option('qckply_sync_code');
    $action = admin_url('admin.php?page=qckply_save');
    printf('<p><a href="%s" target="_blank">Save to Live Website</a> - administrator approval required on %s.</p>',esc_attr($sync_origin.'/wp-admin/admin.php?page=qckply_sync'),esc_html($sync_origin));
    $play_posts = qckply_posts();//new, updated, clone, all, ids
    error_log('play_posts '.sizeof($play_posts));
    qckply_clone_save_playground($play_posts);
    set_transient('qckply_messages_updated',false);
}

