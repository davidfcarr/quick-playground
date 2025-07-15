<?php

/**
 * Handles syncing changes from the playground back to the live site.
 *
 * Displays a preview of proposed changes and, upon approval, applies changes to posts, meta, terms, taxonomies, and relationships.
 */
function quickplayground_sync() {

    global $wpdb, $playground_site_uploads;

    if(!empty($_POST) && !wp_verify_nonce( $_POST['playground'], 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }
    if(isset($_POST['import'])) {
        $saved = [];
        $profile = sanitize_text_field($_POST['profile']);
        $savedfile = $playground_site_uploads.'/quickplayground_posts_'.$profile.'.json';
        $saved['posts'] = json_decode(file_get_contents($savedfile),true);
        $savedfile = $playground_site_uploads.'/quickplayground_meta_'.$profile.'.json';
        $saved['meta'] = json_decode(file_get_contents($savedfile),true);
        $savedfile = $playground_site_uploads.'/quickplayground_images_'.$profile.'.json';
        $saved['images'] = json_decode(file_get_contents($savedfile),true);
        $savedfile = $playground_site_uploads.'/quickplayground_settings_'.$profile.'.json';
        $saved['settings'] = json_decode(file_get_contents($savedfile),true);
        $new_ids = empty($saved['posts']['new_ids']) ? [] : $saved['posts']['new_ids'];
        foreach($_POST['import'] as $slug => $value) {
            if('posts' == $slug) {
                foreach($saved[$slug]['posts'] as $s) {
                    if($s['ID'] == $value) {
                        //printf('<p>found %s</p>',var_export($s,true));
                        if(in_array($value,$new_ids)) {
                            echo '<div class="notice notice-success"><p>Adding post '.$s['post_title'].'</p></div>';
                            unset($s['ID']);
                            wp_insert_post($s);
                        }
                        else {
                            $result = $wpdb->replace($wpdb->posts,$s);
                            if($result) {
                            echo '<div class="notice notice-success"><p>Updating post '.$s['ID'].' '.$s['post_title'].'</p></div>';
                            }
                            else {
                            echo '<div class="notice notice-success"><p>Error updating post '.$s['ID'].' '.$s['post_title'].'</p></div>';
                            }
                        }
                        foreach($saved['meta']['postmeta'] as $m) {
                            if($value == $m['post_id']) {
                            update_post_meta($value,$m['meta_key'],$m['meta_value']);
                            //printf('<p>meta %d %s %s</p>',$value,$m['meta_key'],var_export($m['meta_value'],true));
                            }
                        }
                    }                   
                }
            }
            elseif('attachments' == $slug) {
                $key = 'images';
                echo "<p>searching for attachment post $value</p>";
                foreach($saved[$key]['attachments'] as $s) {
                    if($s['ID'] == $value) {
                        printf('<div class="notice notice-success"><p>Importing attachment for %s</p></div>',esc_html($s['guid']));
                        unset($s['ID']);
                        wp_insert_post($s);
                        foreach($saved['meta']['postmeta'] as $m) {
                            if($value == $m['post_id']) {
                            update_post_meta($value,$m['meta_key'],$m['meta_value']);
                            }
                        }
                    }                    
                }
            }
            elseif('thumbnails' == $slug) {
                $key = 'images';
                echo "<p>searching for thumbnail post $value</p>";
                foreach($saved[$key]['thumbnails'] as $s) {
                    if($s['ID'] == $value) {
                        printf('<div class="notice notice-success"><p>Importing thumbnail for %s ID %d %s</p></div>',esc_html($s['guid']),$s['ID'],$slug);
                        unset($s['ID']);
                        wp_insert_post($s);
                        foreach($saved['meta']['postmeta'] as $m) {
                            if($value == $m['post_id']) {
                            update_post_meta($value,$m['meta_key'],$m['meta_value']);
                            }
                        }
                    }                    
                }
            }
            elseif('settings' == $slug) {
                $settings = $saved[$slug]['settings'];
                printf('<p>Settings %s</p>',var_export($settings,true));
                if(isset($settings[$value])) {
                    $option_value = $settings[$value];
                    printf('<div class="notice notice-success"><p>Setting %s: %s</p></div>',esc_html($value),esc_html(substr(var_export($option_value,true),0,200)));
                }
                else {
                    printf('<div class="notice notice-warning"><p>no match %s</p></div>',$value);
                }
            }
        }
    }
    $pp = get_option('playground_profiles',array('default'));
    foreach($pp as $p) {
        $savedfile = $playground_site_uploads.'/quickplayground_prompts_'.$p.'.json';
        if(file_exists($savedfile)) {
            $play_posts = json_decode(file_get_contents($savedfile),true);
            if($play_posts) {
    printf('<h1>Saved Data from Playground %s profile</h1>',$p);
    printf('<form method="post" action="%s">%s',admin_url('admin.php?page=quickplayground_sync'),wp_nonce_field('quickplayground','playground',true,false));
    printf('<input type="hidden" name="profile" value="%s" >',esc_attr($p));
    if(!empty($play_posts['sync_prompts']['blocks']) && is_array($play_posts['sync_prompts']['blocks'])) {
        printf('<h2>Block Template Items: %d</h2>',sizeof($play_posts['sync_prompts']['blocks']));
        foreach($play_posts['sync_prompts']['blocks'] as $prompt) {
            playground_sync_prompt($prompt);
            //echo wp_kses($prompt,$allowed_html);
        }
    }
    if(!empty($play_posts['sync_prompts']['posts_and_pages']) && is_array($play_posts['sync_prompts']['posts_and_pages'])) {
        printf('<h2>Posts and Pages: %d</h2>',sizeof($play_posts['sync_prompts']['posts_and_pages']));
        foreach($play_posts['sync_prompts']['posts_and_pages'] as $prompt) {
            playground_sync_prompt($prompt);
            //echo wp_kses($prompt,$allowed_html);
            //echo wp_kses_post($prompt);
        }
    }
    if(!empty($play_posts['sync_prompts']['thumbnails']) && is_array($play_posts['sync_prompts']['thumbnails'])) {
        printf('<h2>Thumbnails: %d</h2>',sizeof($play_posts['sync_prompts']['thumbnails']));
        foreach($play_posts['sync_prompts']['thumbnails'] as $prompt) {
            playground_sync_prompt($prompt);
        }
    }
    if(!empty($play_posts['sync_prompts']['site_icon'])) {
        printf('<h2>Site Icon: %d</h2>',sizeof($play_posts['sync_prompts']['thumbnails']));
        foreach($play_posts['sync_prompts']['thumbnails'] as $prompt) {
            playground_sync_prompt($prompt);
        }
    }
    if(!empty($play_posts['sync_prompts']['attachments']) && is_array($play_posts['sync_prompts']['attachments'])) {
        printf('<h2>Image Attachments: %d</h2>',sizeof($play_posts['sync_prompts']['attachments']));
        foreach($play_posts['sync_prompts']['attachments'] as $prompt) {
            playground_sync_prompt($prompt);
        }
    }
    if(!empty($play_posts['sync_prompts']['options']) && is_array($play_posts['sync_prompts']['options'])) {
        printf('<h2>Options: %d</h2>',sizeof($play_posts['sync_prompts']['options']));
        foreach($play_posts['sync_prompts']['options'] as $prompt) {
            playground_sync_prompt($prompt);
        }
    }
    submit_button('Import');
    echo '</form>';

            }
            else
                printf('<p>Error decoding data for %s profile</p>',$p);
        }
        else {
            printf('<p>%s not found</p>',$savedfile);
        }
    }




    return;
    $changes = get_transient('changes_from_playground');
    print_r($_REQUEST);
    if(!empty($_POST) && !wp_verify_nonce( $_POST['playground'], 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }
    if($changes) {
        $status = 'Preview';
        if(isset($_POST['approve'])) {
            printf('<h2>%s</h2>',esc_html__('Processing Changes','quick-playground'));
            $status = 'Doing';
        }
        else {
            printf('<h2>%s</h2>',esc_html__('Proposed Changes','quick-playground'));
            printf('<form method="post" class="playground-form"  action="%s" ><input type="hidden" name="approve" value="1"><div><button>Approve Changes</button></div>%s %s</form>',esc_attr(admin_url('admin.php?page=quickplayground_sync'),wp_nonce_field('quickplayground','playground',true,false),wp_nonce_field('quickplayground','playground',true,false)));
        }      
        if(isset($changes['switch_theme'])) {
            printf('<p>%s: Switch theme to <strong>%s</strong></p>',esc_attr($status),esc_html($changes['switch_theme']));
            if(isset($_POST['approve']))
                switch_theme($changes['switch_theme']);
        }
        if(isset($changes['posts'])) {
            foreach($changes['posts'] as $i  => $p) {
                $meta = (empty($changes['meta'][$i])) ? [] : $changes['meta'][$i];
                $post_id = $p['ID'];
                $exists = get_post($post_id);
                if($exists) {
                    if(isset($_POST['approve'])) {
                        $result = wp_update_post($p);
                        printf('<p>Update result for %s %s %s</p>',intval($p['ID']),esc_html($p['post_title']),esc_html(var_export($result,true)));
                    }
                    printf('<p>%s: Update %s <strong>%s</strong></p>',esc_html($status),esc_html($p['post_type']),esc_html($p['post_title']));
                }
                else {
                    printf('<p>%s: Add %s <strong>%s</strong></p>',esc_html($status),esc_html($p['post_type']),esc_html($p['post_title']));
                    if(isset($_POST['approve'])) {
                        unset($p['ID']);
                        $post_id = wp_insert_post($p);
                        printf('<p>Insert result %s %s</p>',esc_html(var_export($post_id,true)),esc_html($p['post_title']));
                    }
                }
                printf('<p><a href="%s">Edit</a></p>',esc_attr(get_edit_post_link($post_id,false)));
                foreach($meta as $key => $values)
                {
                    foreach($values as $value) {
                        echo "<p>".esc_html($status).": update_post_meta($post_id,$key,$value); </p>";
                        if(isset($_POST['approve']))    
                            update_post_meta($post_id,$key,$value);
                    }
                }
                if($p['post_type'] == 'rsvpmaker') {
                    $event = $changes['rsvpmakers'][$i];
                    if(isset($_POST['approve']))
                        $wpdb->replace(get_rsvpmaker_event_table(),$event);
                }
            }
            $changesoutput = '';

            if(!empty($changes['termmmeta']) && isset($_POST['approve'])) {
                foreach($changes['termmeta'] as $meta) {
                    $result = $wpdb->replace($wpdb->termmeta,$meta);
                    if(!$result) {
                        $changesoutput .= '<p>Error: termmeta '.esc_html($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['terms']) && isset($_POST['approve'])) {
                foreach($changes['terms'] as $row) {
                    $result = $wpdb->replace($wpdb->terms,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: terms '.esc_html($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['term_relationships']) && isset($_POST['approve'])) {
                $changesoutput .= sprintf('<p>%d term_relationships',sizeof($changes['term_relationships']));
                foreach($changes['term_relationships'] as $row) {
                    $result = $wpdb->replace($wpdb->term_relationships,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: term_relationships '.esc_html($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['term_taxonomy']) && isset($_POST['approve'])) {
                foreach($changes['term_taxonomy'] as $row) {
                    $result = $wpdb->replace($wpdb->term_taxonomy,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: term_taxonomy '.esc_html($wpdb->last_error).'</p>';
                    }
                }
            }
            if(isset($_POST['approve']))
                echo $changesoutput;
        }

        if(empty($_POST))
            printf('<pre>%s</pre>',esc_html(var_export($changes,true)));

    }//end if changes

}

function playground_sync_prompt($prompt_array, $input_type = 'checkbox') {
    //['id'=>$post->ID, 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>$post->post_excerpt,'guid'=>$post->guid];
    $image = (empty($prompt_array['guid'])) ? '' : sprintf('<br /><img src="%s" width="300" />',esc_attr($prompt_array['guid']));
    if('options' == $prompt_array['import_type'])
        $prompt_array['import_type'] = 'settings';
    printf('<p><input type="%s" name="import[%s]" value="%s"> %s: %s (%s) %s %s</p>',esc_attr($input_type),esc_attr($prompt_array['import_type']),esc_attr($prompt_array['id']),esc_html($prompt_array['status']),esc_html($prompt_array['title']),esc_html($prompt_array['post_type']),esc_html($prompt_array['excerpt']),$image);
}