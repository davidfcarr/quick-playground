<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Handles syncing changes from the playground back to the live site.
 *
 * Displays a preview of proposed changes and, upon approval, applies changes to posts, meta, terms, taxonomies, and relationships.
 */
function qckply_sync() {

    global $wpdb, $qckply_site_uploads;

    if(!empty($_POST) && !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['playground'])), 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }
    if(isset($_POST['import'])) {
        $saved = [];
        $profile = sanitize_text_field($_POST['profile']);
        $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$profile.'.json';
        $saved['posts'] = json_decode(file_get_contents($savedfile),true);
        $savedfile = $qckply_site_uploads.'/quickplayground_meta_'.$profile.'.json';
        $saved['meta'] = json_decode(file_get_contents($savedfile),true);
        $savedfile = $qckply_site_uploads.'/quickplayground_images_'.$profile.'.json';
        $saved['images'] = json_decode(file_get_contents($savedfile),true);
        $savedfile = $qckply_site_uploads.'/quickplayground_settings_'.$profile.'.json';
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
        $savedfile = $qckply_site_uploads.'/quickplayground_prompts_'.$p.'.json';
        if(file_exists($savedfile)) {
            $play_posts = json_decode(file_get_contents($savedfile),true);
            if($play_posts) {
    printf('<h1>Saved Data from Playground %s profile</h1>',$p);
    printf('<form method="post" action="%s">%s',admin_url('admin.php?page=qckply_sync'),wp_nonce_field('quickplayground','playground',true,false));
    printf('<input type="hidden" name="profile" value="%s" >',esc_attr($p));
    if(!empty($play_posts['sync_prompts']['blocks']) && is_array($play_posts['sync_prompts']['blocks'])) {
        printf('<h2>Block Template Items: %d</h2>',sizeof($play_posts['sync_prompts']['blocks']));
        foreach($play_posts['sync_prompts']['blocks'] as $prompt) {
            qckply_sync_prompt($prompt);
            //echo wp_kses($prompt,$allowed_html);
        }
    }
    if(!empty($play_posts['sync_prompts']['posts_and_pages']) && is_array($play_posts['sync_prompts']['posts_and_pages'])) {
        printf('<h2>Posts and Pages: %d</h2>',sizeof($play_posts['sync_prompts']['posts_and_pages']));
        foreach($play_posts['sync_prompts']['posts_and_pages'] as $prompt) {
            qckply_sync_prompt($prompt);
            //echo wp_kses($prompt,$allowed_html);
            //echo wp_kses_post($prompt);
        }
    }
    if(!empty($play_posts['sync_prompts']['thumbnails']) && is_array($play_posts['sync_prompts']['thumbnails'])) {
        printf('<h2>Thumbnails: %d</h2>',sizeof($play_posts['sync_prompts']['thumbnails']));
        foreach($play_posts['sync_prompts']['thumbnails'] as $prompt) {
            qckply_sync_prompt($prompt);
        }
    }
    if(!empty($play_posts['sync_prompts']['site_icon'])) {
        printf('<h2>Site Icon: %d</h2>',sizeof($play_posts['sync_prompts']['thumbnails']));
        foreach($play_posts['sync_prompts']['thumbnails'] as $prompt) {
            qckply_sync_prompt($prompt);
        }
    }
    if(!empty($play_posts['sync_prompts']['attachments']) && is_array($play_posts['sync_prompts']['attachments'])) {
        printf('<h2>Image Attachments: %d</h2>',sizeof($play_posts['sync_prompts']['attachments']));
        foreach($play_posts['sync_prompts']['attachments'] as $prompt) {
            qckply_sync_prompt($prompt);
        }
    }
    if(!empty($play_posts['sync_prompts']['options']) && is_array($play_posts['sync_prompts']['options'])) {
        printf('<h2>Options: %d</h2>',sizeof($play_posts['sync_prompts']['options']));
        foreach($play_posts['sync_prompts']['options'] as $prompt) {
            qckply_sync_prompt($prompt);
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
}

function qckply_sync_prompt($prompt_array, $input_type = 'checkbox') {
    //['id'=>$post->ID, 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>$post->post_excerpt,'guid'=>$post->guid];
    $image = (empty($prompt_array['guid'])) ? '' : sprintf('<br /><img src="%s" width="300" />',esc_attr($prompt_array['guid']));
    if('options' == $prompt_array['import_type'])
        $prompt_array['import_type'] = 'settings';
    printf('<p><input type="%s" name="import[%s]" value="%s"> %s: %s (%s) %s %s</p>',esc_attr($input_type),esc_attr($prompt_array['import_type']),esc_attr($prompt_array['id']),esc_html($prompt_array['status']),esc_html($prompt_array['title']),esc_html($prompt_array['post_type']),esc_html($prompt_array['excerpt']),$image);
}