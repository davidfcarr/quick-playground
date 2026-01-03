<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Handles syncing changes from the playground back to the live site.
 *
 * Displays a preview of proposed changes and, upon approval, applies changes to posts, meta, terms, taxonomies, and relationships.
 */
function qckply_sync() {
    if((!empty($_POST) || isset($_REQUEST['update']) || isset($_REQUEST['profile']) || isset($_REQUEST['reset'])) && !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['playground'])), 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }

    global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];
    $playground_imported = get_option('playground_imported',array());
    $playground_imported_items = sizeof($playground_imported);

    $profile = (empty($_REQUEST['profile'])) ? 'default' : sanitize_text_field($_REQUEST['profile']);
    $shown = [];

    $pp = get_option('qckply_profiles',array('default'));
    $getnonce = wp_create_nonce('quickplayground');
    echo '<div class="qckply_other_profiles" style="border:1px solid #ccc; padding:10px; margin-bottom:20px;width: 30%; float:right;">';
    foreach($pp as $p) {
        if($p == $profile)
            continue;
        $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$p.'.json';
        if(file_exists($savedfile)) {
            printf('<h2>See saved data for <a href="%s">%s</a></h2>',esc_attr(admin_url('admin.php?page=qckply_sync&profile='.$p.'&playground='.$getnonce)),esc_html($p));
        }
    }
    echo '</div>';

    $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$profile.'.json';
    if(file_exists($savedfile)) {
    $saved = json_decode(file_get_contents($savedfile),true);
    }    
    $savedfile = $qckply_site_uploads.'/quickplayground_meta_'.$profile.'.json';
    if(file_exists($savedfile)) {
    $temp = json_decode(file_get_contents($savedfile),true);
    if(is_array($temp))
        $saved = array_merge($saved,$temp);
    }
    $savedfile = $qckply_site_uploads.'/quickplayground_settings_'.$profile.'.json';
    if(file_exists($savedfile)) {
    $temp = json_decode(file_get_contents($savedfile),true);
    if(is_array($temp))
        $saved = array_merge($saved,$temp);
    }

    if(isset($_POST['switch_theme'])) {
        $new_stylesheet = sanitize_text_field($_POST['switch_theme']);
        $theme = wp_get_theme($new_stylesheet);
        if($theme->exists()) {
        switch_theme($new_stylesheet);
        printf('<p>Switched to theme: %s, stylesheet %s</p>',$theme->get('Name'),esc_html($new_stylesheet));
        
        if(isset($saved['settings']['theme_mods_'.$new_stylesheet])) {
            printf('<p>Theme mods %s</p>',esc_html(var_export($saved['settings']['theme_mods_'.$new_stylesheet],true)));
            update_option('theme_mods_'.$new_stylesheet,$saved['settings']['theme_mods_'.$new_stylesheet]);
        }
        
        }
        else {
            echo '<p>Theme is not installed locally: '.esc_html($new_stylesheet).'</p>';
        }
    }

    if(isset($_POST['import_setting'])) {
        foreach($_POST['import_setting'] as $option_name) {
            $option_name = sanitize_text_field($option_name);
            if(isset($saved['settings'][$option_name])) {
                $option_value = $saved['settings'][$option_name];
                update_option($option_name,maybe_unserialize($option_value));
                printf('<p>Imported setting %s</p>',esc_html($option_name));
            }
        }
    }
/*
    if(isset($_POST['preview'])) {
        foreach($_POST['preview'] as $id) {
                foreach($saved['posts']as $saved_post) {
                    if(is_array($saved_post) && $saved_post['ID'] == $id) {
                        if(in_array($saved_post['ID'],$shown))
                            continue;
                        $shown[] = $saved_post['ID'];
                        printf('<h3>Preview</h3><h2>%s</h2>',esc_html($saved_post['post_title']));
                        echo wp_kses_post(do_blocks($saved_post['post_content']));
                        if($saved['related'] && !empty($saved['related']['p'.$saved_post['ID']])) {
                            echo '<h3>Related Data</h3>';
                            if(!empty($saved['related']['p'.$saved_post['ID']]['postmeta'])) {
                            $metadata = $saved['related']['p'.$saved_post['ID']]['postmeta'];
                            foreach($metadata as $meta) {
                                printf('<p>Post meta %s: %s</p>',esc_html($meta['meta_key']),esc_html($meta['meta_value']));
                            }
                            }
                            if(!empty($saved['related']['p'.$saved_post['ID']]['term_taxonomy'])) {
                                foreach($saved['related']['p'.$saved_post['ID']]['term_taxonomy'] as $termtax) {
                                    $signature = implode('-', $termtax);
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->term_taxonomy.' WHERE term_taxonomy_id = %d', $termtax['term_taxonomy_id']), ARRAY_A);
                                    $maybe_signature = (empty($maybematch)) ? '' : implode('-', $maybematch);
                                    $new = (empty($maybematch) || sizeof(array_diff_assoc($termtax,$maybematch))) ? 'New' : 'Existing'; 
                                    printf('<h3>%s Term taxonomy for %s</h3>',esc_html($new),esc_html(var_export($termtax,true)));
                                    printf('<p>%s</p>',esc_html(var_export($maybematch,true)));
                                }
                            }
                            if(!empty($saved['related']['p'.$saved_post['ID']]['terms'])) {
                                foreach($saved['related']['p'.$saved_post['ID']]['terms'] as $term) {
                                    $signature = implode('-', $term);
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->terms.' WHERE term_id = %d', $term['term_id']), ARRAY_A);
                                    $maybe_signature = (empty($maybematch)) ? '' : implode('-', $maybematch);
                                    $new = (empty($maybematch) || sizeof(array_diff_assoc($term,$maybematch))) ? 'New' : 'Existing'; 
                                    printf('<h3>%s Terms for %s</h3>',esc_html($new),esc_html(var_export($term,true)));
                                    printf('<p>%s</p>',esc_html(var_export($maybematch,true)));
                                }
                            }
                            if(!empty($saved['related']['p'.$saved_post['ID']]['term_relationships'])) {
                                foreach($saved['related']['p'.$saved_post['ID']]['term_relationships'] as $tr) {
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->term_relationships.' WHERE object_id = %d', $tr['object_id']), ARRAY_A);
                                    $signature = implode('-', $tr);
                                    $maybe_signature = (empty($maybematch)) ? '' : implode('-', $maybematch);
                                    $new = (empty($maybematch) || sizeof(array_diff_assoc($tr,$maybematch))) ? 'New' : 'Existing'; 
                                    printf('<h3>%s Term relationships for %s</h3>',esc_html($new),esc_html(var_export($tr,true)));
                                    printf('<p>%s</p>',esc_html(var_export($maybematch,true)));
                                }
                            }
                    }                        
                }
            }
        }
    }
*/
    if(isset($_POST['import_from'])) {
        $terms_checked = [];
        printf('<h1>Importing from Playground "%s"</h1>',esc_html($profile));
        $new_posts = empty($_POST['new_posts']) ? [] : $_POST['new_posts'];
        foreach($_POST['import_from'] as $id) {
            foreach($saved['posts'] as $saved_post) {
                if($saved_post['ID'] == $id) {
                    if(!empty($_POST['import_button']))
                        printf('<h3>Importing</h3><h2>%s</h2>',esc_html($saved_post['post_title']));
                    else
                        printf('<h3>Import PREVIEW</h3><h2>%s</h2>',esc_html($saved_post['post_title']));
                    
                    $iddate = $saved_post['ID'].$saved_post['post_date'];
                    if(!empty($playground_imported[$iddate])) {
                        printf('<p>Updating previously imported imported %s</p>',esc_html($saved_post['post_title']));
                        $saved_post['ID'] = $playground_imported[$iddate];
                        if(!empty($_POST['import_button']))
                            wp_update_post($saved_post);
                    }
                    elseif(in_array($id,$new_posts)) {
                        printf('<p>New: %s</p>',esc_html($saved_post['post_title']));
                        $new_post = $saved_post;
                        unset($new_post['ID']);
                        if(!empty($_POST['import_button']))
                        {
                        $id = wp_insert_post($new_post);
                        $playground_imported[$iddate] = $id;
                        }
                        else
                        {
                            $id = 0;
                        }
                    }
                    else {
                        printf('<p>Updating: %s</p>',esc_html($saved_post['post_title']));
                        if(!empty($_POST['import_button']))
                        {
                        wp_update_post($saved_post);
                        }
                    }
                   $metadata = empty($saved['related']['p'.$saved_post['ID']]['postmeta']) ? [] : $saved['related']['p'.$saved_post['ID']]['postmeta'];
                    printf('<p>Importing %d post meta items for %s</p>',esc_html(sizeof($metadata)),esc_html($saved_post['post_title']));
                    foreach($metadata as $meta) {
                        if(!empty($_POST['import_button']))
                        {
                        update_post_meta($id,$meta['meta_key'],$meta['meta_value']);
                        }
                        if(!empty($_POST['preview_button']) || !empty($_POST['show_details'])) {
                            printf('<p>Updated post %d meta %s: %s</p>',esc_html($id),esc_html($meta['meta_key']),esc_html($meta['meta_value']));
                        }
                    }
                    if(!empty($saved['related']['p'.$saved_post['ID']]['term_join'])) {
                        foreach($saved['related']['p'.$saved_post['ID']]['term_join'] as $taxonomy => $termjoins) {
                            $tax_terms = [];
                            foreach($termjoins as $termjoin) {
                                if(!in_array($termjoin['name'],$tax_terms))
                                $tax_terms[] = $termjoin['name'];
                            }
                            if(!empty($_POST['import_button']))
                            {
                            //insert new taxonomy terms if needed, do not delete existing terms
                            wp_set_post_terms($id, $tax_terms, $taxonomy, false);    
                            }
                            if(!empty($_POST['preview_button']) || !empty($_POST['show_details'])) {
                                printf('<p>Set terms for taxonomy %s: %s</p>',esc_html($taxonomy),esc_html(implode(', ',$tax_terms)) );
                            }
                        }
                    }
        }
    }
    }
    
    if(sizeof($playground_imported) != $playground_imported_items) {
        // Save the updated playground import data
        update_option('playground_imported',$playground_imported);
    }
    }
    $theme_options = ['default'=>'','changed'=>''];
    $internal_settings = ['copy_blogs','copy_events','origin_template','cache_created'];

    if(!empty($saved['settings']) && is_array($saved['settings'])) {
        $settings_output = '';
        foreach($saved['settings'] as $option_name => $option_value) {
        $option_name = trim($option_name);
        if('stylesheet' == $option_name)
            $theme_options['changed'] = $option_value;
        elseif('qckply_clone_stylesheet' == $option_name)
            $theme_options['default'] = $option_value;
        if(in_array($option_name,$internal_settings) || strpos($option_name,'qckply_') === 0 || strpos($option_name,'quickplay_') === 0)
            continue;
        $current_value = get_option($option_name);
        if($option_value != $current_value) {
        $settings_output .= sprintf('<p><input type="checkbox" name="import_setting[]" value="%s" /> Import: %s</p><pre>%s</pre>',esc_attr($option_name),esc_html($option_name),esc_html(var_export($option_value,true)));
        }
        }
    $qckply_stylesheet = (empty($theme_options['changed'])) ? $theme_options['default'] : $theme_options['changed'];
    $current_stylesheet = get_option('stylesheet');
    }

    $action = admin_url('admin.php?page=qckply_sync&profile='.$profile);
    if(empty($saved['posts'])) 
        printf('<h1>No Saved Post Data from Playground "%s"</h1>',esc_html($profile));
    else {
    printf('<h1>Saved Data from Playground "%s"</h1>',esc_html($profile));
    printf('<form method="post" action="%s">',esc_attr($action));
    wp_nonce_field('quickplayground','playground',true,true);
    foreach($saved['posts'] as $index => $saved_post) {
        if(is_array($saved_post)) {
            $iddate = $saved_post['ID'].$saved_post['post_date'];
            if(in_array($iddate,$shown))
                continue;
            $shown[] = $iddate;
            $existing_post = empty($playground_imported[$iddate]) ? get_post($saved_post['ID']) : get_post($playground_imported[$iddate]);
            if(empty($existing_post) || $existing_post->post_date != $saved_post['post_date']) {
                printf('<h2>%s</h2>',esc_html($saved_post['post_title']));
                printf('<p><input type="checkbox" name="import_from[]" value="%d"> New content, ID %s %s</p>',esc_attr($saved_post['ID']),esc_html($saved_post['ID']), esc_html($saved_post['post_title']));
                printf('<input type="hidden" name="new_posts[]" value="%d" />',esc_html($saved_post['ID']));
            }
            else {
                if($existing_post->post_modified != $saved_post['post_modified']) {
                    printf('<h2>%s</h2>',esc_html($saved_post['post_title']));
                    $oldernewer = $saved_post['post_modified'] > $existing_post->post_modified ? '<strong>Newer</strong>' : 'Older';
                    printf('<p><input type="checkbox" name="import_from[]" value="%d"> Modified content, ID %s %s<br />Playground version %s, Live site version %s (playground version is '.$oldernewer.')</p>',esc_html($saved_post['ID']),esc_html($saved_post['ID']),esc_html($saved_post['post_title']), esc_html($saved_post['post_modified']), esc_html($existing_post->post_modified));
                }
            }
        }
    }
    printf('<p><input type="checkbox" name="show_details" value="1" /> %s</p>',esc_html__('Show Details for Debugging','quick-playground'));
    if($qckply_stylesheet != $current_stylesheet) {
        printf('<p><input type="checkbox" name="switch_theme" value="%s" /> Switch to Playground theme to "%s" (currently "%s").</p>',esc_attr($qckply_stylesheet),esc_html($qckply_stylesheet),esc_html($current_stylesheet));
    }
    submit_button('Preview','primary','preview_button',false,['style'=>'float: left; background-color: black;margin-right: 10px;']);
    submit_button('Import','primary','import_button',false);
    printf('<input type="hidden" name="profile" value="%s" >',esc_attr($profile));
    echo '</form>';
    } //end if play_posts

    if(!empty($settings_output)) {
        printf('<h2>Settings to import from Playground "%s"</h2>',esc_html($profile));
        printf('<form method="post" action="%s">',esc_attr($action));
        wp_nonce_field('quickplayground','playground',true,true);
        echo $settings_output;
        printf('<input type="hidden" name="profile" value="%s" >',esc_attr($profile));
        submit_button('Import Selected Settings','primary','import_settings',false);
        echo '</form>';
    }

    //printf('<pre>%s</pre>',esc_html(var_export($saved,true)));
}

function qckply_array_signature($array) {
    ksort($array);
    $output = '';
    foreach($array as $key => $value) {
        if('count' != $key)
        $output .= $key.'-'.$value.'!';
    }
    return $output;
}
/*
function qckply_fix_thumbnail_meta($metadata, $saved) {
    global $wpdb;
    foreach($metadata as $mindex => $meta) {
        if('_thumbnail_id' == $meta['meta_key']) {
            $thumbnail_id = intval($meta['meta_value']);
            $thumbnail = get_the_guid($thumbnail_id);
            $is_image = strpos($thumbnail,'wp-content/uploads') !== false;
            if(isset($_POST['show_details']) && $_POST['show_details']) {
            printf('<p>Thumbnail ID %s GUID %s"</p>',esc_html($meta['meta_value']),esc_html($thumbnail));
            }
            if($is_image) {
                printf('<p><img src="%s" width="300" /></p>',esc_attr($thumbnail));
            }
            else {
                foreach($saved['images']['thumbnails'] as $related_post) {
                    if($related_post['ID'] == $thumbnail_id) {
                        
                        $thumbnail_id = $wpdb->get_var($wpdb->prepare('SELECT ID FROM %i WHERE guid = %s',$wpdb->posts, $related_post['guid']));
                        if($thumbnail_id) {
                            if(isset($_POST['show_details']) && $_POST['show_details']) {
                            printf('<p>Related attachment %s %s matches %d</p>',esc_html($related_post['post_title']), esc_html($related_post['guid']),esc_html($thumbnail_id));
                            printf('<p><img src="%s" width="300" /></p>',esc_attr($related_post['guid']));
                            }
                            $metadata[$mindex]['meta_value'] = $thumbnail_id;
                        }
                        else {
                            if(isset($_POST['show_details']) && $_POST['show_details']) {
                            printf('<p>No matching attachment for thumbnail %d</p>',esc_html($related_post['ID']));
                            printf('<p><img src="%s" width="300" /></p>',esc_attr($related_post['guid']));
                            }
                            $metadata[$mindex]['meta_value'] = 0;
                        }
                        break;
                    }
                }
            }
        }
    } //end postmeta loop
    return $metadata;
}

function qckply_sync_prompt($prompt_array, $input_type = 'checkbox') {
    //['id'=>$post->ID, 'status'=>$status, 'title'=>$post->post_title, 'post_type'=>$post->post_type, 'excerpt'=>$post->post_excerpt,'guid'=>$post->guid];
    $image = (empty($prompt_array['guid'])) ? '' : sprintf('<br /><img src="%s" width="300" />',esc_attr($prompt_array['guid']));
    if('options' == $prompt_array['import_type'])
        $prompt_array['import_type'] = 'settings';
    printf('<p><input type="%s" name="import[%s]" value="%s"> %s: %s (%s) %s %s</p>',esc_attr($input_type),esc_attr($prompt_array['import_type']),esc_attr($prompt_array['id']),esc_html($prompt_array['status']),esc_html($prompt_array['title']),esc_html($prompt_array['post_type']),esc_html($prompt_array['excerpt']),wp_kses_post($image));
}
*/