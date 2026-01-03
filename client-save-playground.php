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
    printf('<h3>Saving %d Posts</h3>',esc_html(sizeof($clone['posts'])));
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
        printf('<h3>%d Posts to Save</h3>',sizeof($clone['posts']));
        $json = qckply_json_outgoing($json,$sync_origin.$site_dir);
        $response = wp_remote_post($save_posts_url, array(
            'body' => $json,
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
        printf('<p>Saved %s bytes of post content to %s</p>',esc_html($returned['saved']),esc_html($returned['file']));
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html('Server message: '.$returned['message']));
   }
    $clone = [];
    $clone['settings']['cache_created'] = time();
    $saved_options = get_option('qckply_clone_options',[]);
    foreach($saved_options as $option) {
        $v = get_option($option);
        $clone['settings'][$option] = $v;
    }
    $updated_options = get_option('qckply_updated_options',[]);
    foreach($updated_options as $option) {
        if(in_array($option,$saved_options))
            continue;
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
    } else {
        $returned = $response['body'];
        if(!is_array($returned)) {
            $returned = json_decode($returned, true);
        }
        if(!is_array($returned)) {
            echo 'not an array: '.esc_html(var_export($returned,true));
        }
        if(!empty($returned['saved']))
        printf('<p>Saved %s bytes of settings to %s</p>',esc_html($returned['saved']),esc_html($returned['file']));
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html($returned['message']));
   }

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
        printf('<p>Saved %s bytes to %s</p>',esc_html($returned['saved']),esc_html($returned['file']));
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html($returned['message']));
   }

    printf('<h3 style="color: green; font-weight: bold;">Playground save complete %s</h3>',esc_html(date('r')) );
    printf('<p><a href="%s" target="_blank">Save to Live Website</a> - administrator approval required on %s.</p>',esc_attr($sync_origin.'/wp-admin/admin.php?page=qckply_sync'),esc_html($sync_origin));
}
}

function qckply_posts() {
    global $wpdb;
    $qckply_mysite_url = site_url();
    $sync_origin = get_option('qckply_sync_origin');
    $site_dir = '/wp-content/uploads'.get_option('qckply_site_dir');
    $search_path = $qckply_mysite_url.'/wp-content/uploads';
    $replace_path = $sync_origin.$site_dir;
    $sql = $wpdb->prepare("SELECT * FROM %i WHERE post_status='publish' AND post_type != 'attachment' AND post_modified > %s ORDER BY post_modified DESC",$wpdb->posts, date('Y-m-d H:i:s', strtotime('-1 day')));
    $posts = $wpdb->get_results($sql);
    print_r($posts);
    printf('<p>Found %d posts modified since last sync with %s</p>',esc_html(count($posts)),esc_html($sql));
    $test_posts = get_posts(array(
        'post_type' => 'any',
        'post_status' => 'publish',
        'numberposts' => 2
    ));
    print_r($test_posts[0]);
    return ['posts'=>$posts];
    $play_posts = ['posts'=>$posts, 'sync_prompts'=>[]];
    $sync_prompts = ['posts_and_pages'=>[],'blocks'=>[],'images'=>[]];
    $results = $wpdb->get_results("select * from $wpdb->postmeta where meta_key='_thumbnail_id' ");
    foreach($results as $row)
        $thumbnail_ids[] = $row->meta_value;
    $site_icon = get_option('site_icon');
    $site_logo = get_option('site_logo');
    $updated_posts = [];
    foreach($posts as $post) {
        if($post->ID > $top['posts']) {
            $play_posts['new_ids'][] = $post->ID;
            $status = 'new';
        }
        elseif($post->post_modified > $top['post_modified']) {
            $play_posts['updated_ids'][] = $post->ID;
            $status = 'updated';
        }
        else {
            $status = 'cloned';
        }
        
            //replace image paths
            $post->post_content = str_replace($search_path,$replace_path,$post->post_content);
            //replace link paths
            $post->post_content = str_replace($qckply_mysite_url,$sync_origin,$post->post_content);
            $play_posts['posts'][] = $post;
            if(in_array($post->post_type,['wp_template','wp_template_part','wp_navigation','wp_global_styles'])) {
                if('new' == $status || 'updated' == $status) {
                    $excerpt = htmlentities($post->post_content);
                    $play_posts['sync_prompts']['blocks'][] = ['id'=>$post->ID, 'import_type'=>'posts', 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>$excerpt];
                }                
            }
            else {
                if('new' == $status || 'updated' == $status) {
                    $excerpt = ($post->post_excerpt) ? $post->post_excerpt : substr(trim(wp_strip_all_tags($post->post_content)),1,200);
                    $play_posts['sync_prompts']['posts_and_pages'][] = ['id'=>$post->ID, 'import_type'=>'posts', 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>$excerpt];
                }
        }
    }
    return $play_posts;
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

