<?php

function qckply_clone_save_playground($play_posts) {
    echo "start qckply_clone_save_playground";
	global $wpdb;
    $profile = get_option("qckply_profile",'default');
    printf('<h2>Saving Playground Data for %s qckply_clone_save_playground</h2>',esc_html($profile));
    $sync_origin = get_option('qckply_sync_origin');
    $site_dir = '/wp-content/uploads'.get_option('qckply_site_dir');
    $qckply_mysite_url = rtrim(get_option('siteurl'),'/');
    $origin_stylesheet = get_option('origin_stylesheet');
    $save_posts_url = $sync_origin.'/wp-json/quickplayground/v1/save_posts/'.$profile;
    $save_settings_url = $sync_origin.'/wp-json/quickplayground/v1/save_settings/'.$profile;
    $save_meta_url = $sync_origin.'/wp-json/quickplayground/v1/save_meta/'.$profile;
    $save_custom_url = $sync_origin.'/wp-json/quickplayground/v1/save_custom/'.$profile;
    $save_prompts_url = $sync_origin.'/wp-json/quickplayground/v1/save_prompts/'.$profile;
    $post_ids = [];
    echo "<p>Save posts url $save_posts_url</p>";

    $local_directories = qckply_get_directories();
    $remote_directories = get_option('qckply_origin_directories');

    $clone['profile'] = $profile;
    $clone['timezone_string'] = get_option('timezone_string');
    $template_part = get_block_template( get_stylesheet() . '//header', 'wp_template_part' );
    $header_content = (empty($template_part->content)) ? '' : $template_part->content;
    $clone['nav_id'] = 0;
    if($header_content) {
    preg_match('/"ref":([0-9]+)/',$header_content,$match);
    if(!empty($match[1]))
        $clone['nav_id'] = $match[1];
    }
    $files = [];
    $clone['posts'] = $play_posts['posts'];
    printf('<h3>Saving %d Posts</h3>',esc_html(sizeof($clone['posts'])));
    foreach($clone['posts'] as $p)
        $post_ids[] = $p->ID;
    $clone['new_ids'] = $play_posts['new_ids'];
    $clone = apply_filters('qckply_clone_save_posts',$clone);
    $clone = json_encode($clone,JSON_PRETTY_PRINT);
    $clone = qckply_json_outgoing($clone,$sync_origin.$site_dir);

    $response = wp_remote_post($save_posts_url, array(
        'body' => $clone,
        'headers' => array('Content-Type' => 'application/json')
    ));

    if(is_wp_error($response)) {

        echo '<p>Error: '.esc_html(htmlentities($response->get_error_message())).'</p>';

    } else {

        $returned = $response['body'];

        if(!is_array($returned)) {

            $returned = json_decode($returned, true);

        }

        if(!is_array($returned)) {
            echo 'not an array: '.esc_html(var_export($returned,true));
        }
        if(!empty($returned['saved']))
        printf('<p>Saved %s bytes of post data</p>',esc_html($returned['saved']));
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html($returned['message']));
    }

    echo "<p>End after save posts</p>";
    //reset array
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
}

$clone = ['sync_prompts'=>$play_posts['sync_prompts'],'new_ids'=>$play_posts['new_ids']];
$clone = qckply_json_outgoing(json_encode($clone),$sync_origin.$site_dir);
    echo '<h3>Saving Sync Prompts</h3>';
    $clone = qckply_json_outgoing(json_encode($clone),$sync_origin.$site_dir);
    $response = wp_remote_post($save_prompts_url, array(
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
        printf('<p>Saved %s bytes to %s</p>',esc_html($returned['saved']),esc_html($returned['file']) );
        if(!empty($returned['message']))
        printf('<p>%s</p>',esc_html($returned['message']) );
   }
    qckply_save_image_data($play_posts);
    printf('<h3 style="color: green; font-weight: bold;">Playground save complete %s</h3>',esc_html(date('r')) );
    printf('<p><a href="%s" target="_blank">Save to Live Website</a> - administrator approval required on %s.</p>',esc_attr($sync_origin.'/wp-admin/admin.php?page=qckply_sync'),esc_html($sync_origin));
    qckply_upload_images();
}

function qckply_posts() {
    echo "<p>start qckply_posts</p>";
    global $wpdb;
    echo $qckply_mysite_url = rtrim(get_option('siteurl'),'/');
    echo $sync_origin = get_option('qckply_sync_origin');
    printf('<p>site dir option %s</p>',var_export(get_option('qckply_site_dir'),true));
    echo $site_dir = '/wp-content/uploads'.get_option('qckply_site_dir');
    echo $search_path = $qckply_mysite_url.'/wp-content/uploads';
    echo $replace_path = $sync_origin.$site_dir;
    $top = qckply_top_ids();
    echo $sql = $wpdb->prepare("SELECT * FROM %i WHERE post_status='publish' OR (post_status='inherit' and post_type='attachment') ORDER BY post_date DESC",$wpdb->posts);
    $wpdb->show_errors();
    $posts = $wpdb->get_results($sql);
    $play_posts = ['new_ids' => [],'posts'=>[], 'sync_prompts'=>[],'thumbnails'=>[],'attachments'=>[]];
    printf('<p>play posts default %s</p>',var_export($play_posts,true));
    $thumbnail_ids = [];
    $attachments = [];
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
        if('attachment' == $post->post_type) {
            if('new' == $status)
                $play_posts['upload_images'][] = $post->guid;
            if(in_array($post->ID,$thumbnail_ids)) {
                $play_posts['thumbnails'][] = $post;
                if('new' == $status || 'updated' == $status) {
                    $play_posts['sync_prompts']['thumbnails'][] = ['id'=>$post->ID, 'import_type'=>'thumbnails', 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>$post->post_excerpt,'guid'=>$post->guid];
                }                
            }
            elseif($site_icon == $post->ID) {
                $play_posts['site_icon'] = $post;
                if('new' == $status || 'updated' == $status) {
                    $play_posts['sync_prompts']['attachments'][] = ['id'=>$post->ID, 'import_type'=>'site_icon', 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>'site icon '.$post->post_excerpt,'guid'=>$post->guid];
                }
            }
            elseif($site_logo == $post->ID) {
                $play_posts['site_logo'][] = $post;
                if('new' == $status || 'updated' == $status) {
                $play_posts['sync_prompts']['attachments'][] = ['id'=>$post->ID, 'import_type'=>'site_logo', 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>'site logo '.$post->post_excerpt,'guid'=>$post->guid];
                }
            }
            else {
                $play_posts['attachments'][] = $post;
                if('new' == $status || 'updated' == $status) {
                    $play_posts['sync_prompts']['attachments'][] = ['id'=>$post->ID, 'import_type'=>'attachments', 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>$post->post_excerpt,'guid'=>$post->guid];;
                }                
            }
        }
        else {
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
    }
    return $play_posts;
}

add_action( 'admin_bar_menu', 'qckply_save_toolbar_link', 99 );

function qckply_save_toolbar_link( $wp_admin_bar ) {
    $code = get_option('qckply_sync_code');
    if(empty($code))
        return;
        $args = array(

            'id'    => 'playground-save',

            'title' => 'Save Playground',

            'href'  => admin_url('admin.php?page=qckply_save'),

            'parent' => 'site-name',

            'meta'  => array( 'class' => 'playground' )

        );    

	$wp_admin_bar->add_node( $args );

        $args = array(

            'id'    => 'playground-prompts',

            'title' => 'Edit Playground Prompts',

            'href'  => admin_url('admin.php?page=qckply_clone_prompts'),

            'parent' => 'site-name',

            'meta'  => array( 'class' => 'playground' )

        );    

	$wp_admin_bar->add_node( $args );
}

function qckply_save() {
    global $wpdb;    
    echo '<h1>Save and Sync</h1>';
    if(!empty($_POST) && !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['playground'])), 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }

    $origin_stylesheet = get_option('origin_stylesheet');
    $sync_origin = $qckply_baseurl = get_option('qckply_sync_origin');
    $no_cache = get_option('qckply_no_cache',false);
    $qckply_profile = get_option('qckply_profile','default');
    $prompts = qckply_get_prompts_remote($qckply_profile);
    $url = $qckply_baseurl .'/wp-json/quickplayground/v1/clone_posts/'.$qckply_profile.'?t='.time();
    if($no_cache) $url .= '&nocache=1';
    $taxurl = $qckply_baseurl .'/wp-json/quickplayground/v1/clone_taxonomy/'.$qckply_profile.'?t='.time();
    if($no_cache) $taxurl .= '&nocache=1';
    $imgurl = $qckply_baseurl .'/wp-json/quickplayground/v1/clone_images/'.$qckply_profile.'?t='.time();
    if($no_cache) $imgurl .= '&nocache=1';
    $sync_date = get_option('qckply_sync_date');
    $qckply_sync_code = get_option('qckply_sync_code');
    printf('<p>Sync code %s</p>',$qckply_sync_code);

    echo '<p>You can save a copy of the playground content for future playground sessions. In addition to allowing you to continue your experimentation, this can be a good way of creating demo content for a theme or plugin. You can also save selected changes to your live website.</p>';
    $action = admin_url('admin.php?page=qckply_save');
    error_log('save action '.$action);
    printf('<form method="post" action="%s">',esc_attr($action));
    wp_nonce_field('quickplayground','playground',true,true);
    echo '<h3>Save</h3><p>The save option causes your updates to the Playground environment to be saved to a JSON file on your web server. By default, that cached copy will be used the next time you visit the playground. <input type="hidden" name="save" value="1" /></p>';
    submit_button('Save Playground');
    echo '</form>';
    printf('<p><a href="%s" target="_blank">Save to Live Website</a> - administrator approval required on %s.</p>',esc_attr($sync_origin.'/wp-admin/admin.php?page=qckply_sync'),esc_html($sync_origin));
    echo '<p>See below for a preview of the content you can approve to add or update.</p>';
    error_log('call to qckply_posts');
    echo "<p>Before qckply_posts </p>";
    $play_posts = qckply_posts();//new, updated, clone, all, ids
    echo 'play_posts '.sizeof($play_posts);
    error_log('play_posts '.sizeof($play_posts));
    qckply_clone_save_playground($play_posts);
}

function qckply_upload_images() {
global $wpdb;
    $profile = get_option("qckply_profile");
    $qckply_sync_code = get_option('qckply_sync_code');
    $sync_origin = get_option('qckply_sync_origin');
    $upload_image_url = $sync_origin.'/wp-json/quickplayground/v1/upload_image/'.$profile;
if(isset($_POST['uploads']) && is_array($_POST['uploads']) && count($_POST['uploads'])) {
    $uploads = $_POST['uploads'];
    $upload_count = 0;
    foreach($uploads as $file) {
        $file = sanitize_text_field($file);
        if(empty($file)) continue;
        if(!file_exists($file)) {
            echo '<p>File '.esc_html($file).' does not exist</p>';
            continue;
        }
        $upload_count++;
        //$file_path, $remote_url, $form_field_name, $additional_data = array()
        $result = qckply_upload($file);
        printf('<p>Result for %s: %s</p>',esc_html($file),esc_html(var_export($result,true)));
    }
    printf('<p>Uploaded %d files</p>',esc_html($upload_count));
}   
$last_result = get_transient('qckply_upload_last_result');
$images = get_transient('qckply_uploaded_files');
echo '<h1>Save Images</h1>';
if(!is_array($images) || !count($images)) {
    echo '<p>No images to save</p>';
    $images = [];
}
$uploaded = get_transient('qckply_successful_uploads');
if(is_array($uploaded) && count($uploaded)) {
    printf('<p>Found %d automatically saved images</p>',count($uploaded));
    foreach($uploaded as $up) {
        printf('<p><img src="%s" width="300" /><br />Search %s<br />Replace %s</p>',esc_attr($up['url']),esc_html($up['search']),esc_html($up['replace']));
    }
}
else {
    echo '<p>No previously uploaded images</p>';
}
printf('<form method="post" action="%s">',esc_attr(admin_url('admin.php?page=qckply_upload_images')));
printf('<p>Found %d unsaved images</p>',count($images));
if(!empty($images)) {
    echo '<h2>Select images to upload:</h2>';
}
foreach($images as $image) {
    echo "<p>Image: ".esc_html(var_export($image,true))."</p>";
    printf('<p><input type="checkbox" name="uploads[]" value="%s" /> Upload: %s <br /><img src="%s" width="300" /></p>',esc_attr($image['file']),esc_html($image['file']),esc_attr($image['url']));
    }
echo '<p><input type="submit" value="Upload Selected Images" /></p>';
wp_nonce_field('qckply_upload_images','playground',true,true);
echo '</form>';

printf('<h2>Debugging Information</h2><p>Last upload result: <pre>%s</pre></p>',esc_html(var_export($last_result,true)));

}

function qckply_upload($file_path) {
    if ( ! file_exists( $file_path ) ) {
        return new WP_Error( 'file_not_found', 'The specified file does not exist.' );
    }
    update_option('qckply_upload_image_attempt',$file_path);
    $filesize = filesize($file_path);
    if($filesize > 750000) {
        update_option('qckply_upload_image_too_big',$filesize);
        $result = wp_schedule_single_event(time() + MINUTE_IN_SECONDS,'qckply_smaller_upload',array(basename($file_path)));
        update_option('qckply_upload_schedule_smaller',var_export($result,true));
        return;
    }
    $profile = get_option("qckply_profile");
    $qckply_sync_code = qckply_get_sync_code();
    $sync_origin = get_option('qckply_sync_origin');
    $remote_url = $sync_origin.'/wp-json/quickplayground/v1/upload_image/'.$profile.'?t='.time();
    update_option('qckply_upload_image_path',$file_path);
    update_option('qckply_upload_image_url',$remote_url);
    $data['sync_code'] = $qckply_sync_code;
    $data['base64'] = base64_encode(file_get_contents($file_path));
    $data['filename'] = basename($file_path);
    $request = array(
    'body'    => json_encode($data),
    'headers' => array(
        'Content-Type' => 'application/json',
    ));
    $response = wp_remote_post( $remote_url, $request);
    if ( ! is_wp_error( $response ) ) {
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    update_option('qckply_upload_image_result',var_export($body,true));
    return $body;
    } else {
        $error_message = $response->get_error_message();
        update_option('qckply_upload_image_result',var_export($error_message,true));
        throw new Exception( esc_html($error_message) );
    }

    return json_decode($result,true);
}

function qckply_save_image_data($play_posts) {
    global $wpdb;
    $profile = get_option("qckply_profile");
    $qckply_sync_code = get_option('qckply_sync_code');
    $sync_origin = get_option('qckply_sync_origin');
    $site_dir = '/wp-content/uploads'.get_option('qckply_site_dir');
    $qckply_mysite_url = rtrim(get_option('siteurl'),'/');
    $origin_stylesheet = get_option('origin_stylesheet');
    $save_image_url = $sync_origin.'/wp-json/quickplayground/v1/save_image/'.$profile;
    printf('<h2>Saving %d Thumbnails %s</h2>',esc_html(sizeof($play_posts['thumbnails'])),esc_html($save_image_url));
 
    $clone = ['attachments' => $play_posts['attachments'],'thumbnails'=>$play_posts['thumbnails'],'site_logo'=>$play_posts['site_logo'],'site_icon'=>$play_posts['site_icon'], 'code' => $qckply_sync_code];

    $clone = qckply_json_outgoing(json_encode($clone),$sync_origin.$site_dir);
    $response = wp_remote_post($save_image_url, array(
        'body' => $clone,
        'headers' => array('Content-Type' => 'application/json')
    ));
    if(is_wp_error($response)) {
        echo '<p>Error: '.esc_html(htmlentities($response->get_error_message())).'</p>';
    } else {

        $returned = $response['body'];

        if(!is_array($returned)) {

            $returned = json_decode($returned, true);

        }

        if(!is_array($returned)) {
            echo 'return not an array: '.esc_html(var_export($returned,true).' '.$response['body']);
        } else {
        print_r('<p>returned %s</p>',wp_kses_post(var_export($returned,true)));
          foreach($returned as $label => $value)
            printf('<p>Attachment posts upload result %s: %s</p>',esc_html($label),esc_html(var_export($value,true)));
 
        }
   }
}
