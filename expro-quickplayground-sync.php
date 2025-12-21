<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Handles syncing changes from the playground back to the live site.
 *
 * Displays a preview of proposed changes and, upon approval, applies changes to posts, meta, terms, taxonomies, and relationships.
 */
function qckply_sync() {

    global $wpdb;
    $qckply_directories = qckply_get_directories();
    $qckply_site_uploads = $qckply_directories['site_uploads'];
    $qckply_uploads = $qckply_directories['uploads'];
    $qckply_uploads_url = $qckply_directories['uploads_url'];
    $qckply_site_uploads_url = $qckply_directories['site_uploads_url'];
    $playground_imported = get_option('playground_imported',array());
    $playground_imported_items = sizeof($playground_imported);

    $profile = (empty($_REQUEST['profile'])) ? 'default' : sanitize_text_field($_REQUEST['profile']);
    $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$profile.'.json';
    if(file_exists($savedfile)) {
    $saved['posts'] = json_decode(file_get_contents($savedfile),true);
    }    
    $savedfile = $qckply_site_uploads.'/quickplayground_meta_'.$profile.'.json';
    if(file_exists($savedfile)) {
    $saved['meta'] = json_decode(file_get_contents($savedfile),true);
    }
    $savedfile = $qckply_site_uploads.'/quickplayground_images_'.$profile.'.json';
    if(file_exists($savedfile)) {
        $saved['images'] = json_decode(file_get_contents($savedfile),true);
    }
    $savedfile = $qckply_site_uploads.'/quickplayground_settings_'.$profile.'.json';
    if(file_exists($savedfile)) {
    $saved['settings'] = json_decode(file_get_contents($savedfile),true);
    }

    if((!empty($_POST) || isset($_REQUEST['update']) || isset($_REQUEST['profile']) || isset($_REQUEST['reset'])) && !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['playground'])), 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }
    if(isset($_POST['preview'])) {
        foreach($_POST['preview'] as $id) {
                foreach($saved['posts']['posts'] as $saved_post) {
                    if($saved_post['ID'] == $id) {
                        printf('<h3>Preview</h3><h2>%s</h2>',esc_html($saved_post['post_title']));
                        echo wp_kses_post(do_blocks($saved_post['post_content']));
                        if($saved['meta']['related'] && !empty($saved['meta']['related']['p'.$saved_post['ID']])) {
                            echo '<h3>Related Data</h3>';
                            if(!empty($saved['meta']['related']['p'.$saved_post['ID']]['postmeta'])) {
                            $metadata = qckply_fix_thumbnail_meta($saved['meta']['related']['p'.$saved_post['ID']]['postmeta'], $saved);
                            foreach($metadata as $meta) {
                                printf('<p>Post meta %s: %s</p>',esc_html($meta['meta_key']),esc_html($meta['meta_value']));
                            }
                            }
                            if(!empty($saved['meta']['related']['p'.$saved_post['ID']]['term_taxonomy'])) {
                                foreach($saved['meta']['related']['p'.$saved_post['ID']]['term_taxonomy'] as $termtax) {
                                    $signature = implode('-', $termtax);
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->term_taxonomy.' WHERE term_taxonomy_id = %d', $termtax['term_taxonomy_id']), ARRAY_A);
                                    $maybe_signature = (empty($maybematch)) ? '' : implode('-', $maybematch);
                                    $new = (empty($maybematch) || sizeof(array_diff_assoc($termtax,$maybematch))) ? 'New' : 'Existing'; 
                                    printf('<h3>%s Term taxonomy for %s</h3>',esc_html($new),esc_html(var_export($termtax,true)));
                                    printf('<p>%s</p>',esc_html(var_export($maybematch,true)));
                                }
                            }
                            if(!empty($saved['meta']['related']['p'.$saved_post['ID']]['terms'])) {
                                foreach($saved['meta']['related']['p'.$saved_post['ID']]['terms'] as $term) {
                                    $signature = implode('-', $term);
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->terms.' WHERE term_id = %d', $term['term_id']), ARRAY_A);
                                    $maybe_signature = (empty($maybematch)) ? '' : implode('-', $maybematch);
                                    $new = (empty($maybematch) || sizeof(array_diff_assoc($term,$maybematch))) ? 'New' : 'Existing'; 
                                    printf('<h3>%s Terms for %s</h3>',esc_html($new),esc_html(var_export($term,true)));
                                    printf('<p>%s</p>',esc_html(var_export($maybematch,true)));
                                }
                            }
                            if(!empty($saved['meta']['related']['p'.$saved_post['ID']]['term_relationships'])) {
                                foreach($saved['meta']['related']['p'.$saved_post['ID']]['term_relationships'] as $tr) {
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->term_relationships.' WHERE object_id = %d', $tr['object_id']), ARRAY_A);
                                    $signature = implode('-', $tr);
                                    $maybe_signature = (empty($maybematch)) ? '' : implode('-', $maybematch);
                                    $new = (empty($maybematch) || sizeof(array_diff_assoc($tr,$maybematch))) ? 'New' : 'Existing'; 
                                    printf('<h3>%s Term relationships for %s</h3>',esc_html($new),esc_html(var_export($tr,true)));
                                    printf('<p>%s</p>',esc_html(var_export($maybematch,true)));
                                }
                            }
                            /*
                            foreach($saved['meta']['related']['p'.$saved_post['ID']] as $key => $value) {
                                if('postmeta' == $key)
                                    continue;
                                printf('<h3>Post related %s</h3><pre>%s</pre>',esc_html($key),esc_html(var_export($value,true)));
                        }
                        */
                    }
                        
                }
            }
        }
    }
    if(isset($_POST['import_from'])) {
        printf('<h1>Importing from Playground "%s"</h1>',esc_html($profile));
        $new_posts = empty($_POST['new_posts']) ? [] : $_POST['new_posts'];
        foreach($_POST['import_from'] as $id) {
            foreach($saved['posts']['posts'] as $saved_post) {
                if($saved_post['ID'] == $id) {
                    printf('<h3>Importing</h3><h2>%s</h2>',esc_html($saved_post['post_title']));
                    $iddate = $saved_post['ID'].$saved_post['post_date'];
                    if(!empty($playground_imported[$iddate])) {
                        printf('<p>Updating previously imported imported %s</p>',esc_html($saved_post['post_title']));
                        $saved_post['ID'] = $playground_imported[$iddate];
                        wp_update_post($saved_post);
                    }
                    elseif(in_array($id,$new_posts)) {
                        printf('<p>New: %s</p>',esc_html($saved_post['post_title']));
                        $new_post = $saved_post;
                        unset($new_post['ID']);
                        $id = wp_insert_post($new_post);
                        $playground_imported[$iddate] = $id;
                    }
                    else {
                        printf('<p>Updating: %s</p>',esc_html($saved_post['post_title']));
                        wp_update_post($saved_post);
                    }
                    $metadata = empty($saved['meta']['related']['p'.$saved_post['ID']]['postmeta']) ? [] : qckply_fix_thumbnail_meta($saved['meta']['related']['p'.$saved_post['ID']]['postmeta'],$saved);
                    printf('<p>Importing %d post meta items for %s</p>',esc_html(sizeof($metadata)),esc_html($saved_post['post_title']));
                    foreach($metadata as $meta) {
                        update_post_meta($id,$meta['meta_key'],$meta['meta_value']);
                        if(isset($_POST['show_details']) && $_POST['show_details']) {
                            printf('<p>Updated post %d meta %s: %s</p>',esc_html($id),esc_html($meta['meta_key']),esc_html($meta['meta_value']));
                        }
                    }
                            if(!empty($saved['meta']['related']['p'.$saved_post['ID']]['term_taxonomy'])) {
                                foreach($saved['meta']['related']['p'.$saved_post['ID']]['term_taxonomy'] as $termtax) {
                                    unset($termtax['count']);
                                    $signature = qckply_array_signature($termtax);
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->term_taxonomy.' WHERE term_taxonomy_id = %d', $termtax['term_taxonomy_id']), ARRAY_A);
                                    unset($maybematch['count']);
                                    $maybe_signature = (empty($maybematch)) ? '' : qckply_array_signature($maybematch);
                                    if(isset($_POST['show_details']) && $_POST['show_details']) {
                                    if(in_array($signature, $playground_imported)) {
                                        printf('<p>Term taxonomy %s already imported</p>',esc_html($signature));
                                    }
                                    }
                                    elseif($signature != $maybe_signature) {
                                        unset($termtax['term_taxonomy_id']);
                                        $wpdb->insert($wpdb->term_taxonomy,$termtax);
                                        if(isset($_POST['show_details']) && $_POST['show_details']) {
                                        printf('<p>Updated term taxonomy %s != %s</p>',esc_html($signature),esc_html($maybe_signature));
                                        }
                                        $new = (empty($maybematch) || sizeof(array_diff_assoc($termtax,$maybematch))) ? 'New' : 'Existing'; 
                                        if(isset($_POST['show_details']) && $_POST['show_details']) {
                                        printf('<h3>%s Term taxonomy for %s</h3>',esc_html($new), esc_html(var_export($termtax,true)));
                                        printf('<p>%s</p>',esc_html(var_export($maybematch,true)));
                                        }
                                        $playground_imported[$signature] = $signature;
                                    }
                                }
                            }
                            if(!empty($saved['meta']['related']['p'.$saved_post['ID']]['terms'])) {
                                foreach($saved['meta']['related']['p'.$saved_post['ID']]['terms'] as $term) {
                                    $signature = qckply_array_signature($term);
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->terms.' WHERE term_id = %d', $term['term_id']), ARRAY_A);
                                    $maybe_signature = (empty($maybematch)) ? '' : qckply_array_signature($maybematch);
                                    if(in_array($signature, $playground_imported)) {
                                        if(isset($_POST['show_details']) && $_POST['show_details']) {
                                        printf('<p>Term %s already imported</p>',esc_html($signature));
                                        }
                                    }
                                    elseif($signature != $maybe_signature) {
                                        unset($term['term_id']);
                                        $wpdb->insert($wpdb->terms,$term);
                                        if(isset($_POST['show_details']) && $_POST['show_details']) {
                                        printf('<p>Updated term %s != %s</p>',esc_html($signature),esc_html($maybe_signature));
                                        }
                                        $playground_imported[$signature] = $signature;
                                    }
                                }
                            }
                            if(!empty($saved['meta']['related']['p'.$saved_post['ID']]['term_relationships'])) {
                                foreach($saved['meta']['related']['p'.$saved_post['ID']]['term_relationships'] as $tr) {
                                    $maybematch = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->term_relationships.' WHERE object_id = %d', $tr['object_id']), ARRAY_A);
                                    $signature = qckply_array_signature($tr);
                                    $maybe_signature = (empty($maybematch)) ? '' : qckply_array_signature($maybematch);
                                    if(in_array($signature, $playground_imported)) {
                                        if(isset($_POST['show_details']) && $_POST['show_details']) {
                                        printf('<p>Term relationship %s already imported</p>',esc_html($signature));
                                        }
                                    }
                                    elseif($signature != $maybe_signature) {
                                        $result = @$wpdb->insert($wpdb->term_relationships,$tr);
                                        if(isset($_POST['show_details']) && $_POST['show_details']) {
                                        printf('<p>Updated term relationships %s != %s, %s</p>',esc_html($signature),esc_html($maybe_signature),esc_html(var_export($result,true)));
                                        }
                                        $playground_imported[$signature] = $signature;
                                    }
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

    $play_posts = (empty($saved['posts'])) ? false : $saved['posts'];
    if($play_posts) {
    printf('<h1>Saved Data from Playground "%s"</h1>',esc_html($profile));
    printf('<form method="post" action="%s">',esc_attr(admin_url('admin.php?page=qckply_sync')));
    wp_nonce_field('quickplayground','playground',true,true);
    foreach($play_posts['posts'] as $saved_post) {
        if(is_array($saved_post)) {
            $iddate = $saved_post['ID'].$saved_post['post_date'];
            $existing_post = empty($playground_imported[$iddate]) ? get_post($saved_post['ID']) : get_post($playground_imported[$iddate]);
            if(empty($existing_post) || $existing_post->post_date != $saved_post['post_date']) {
                printf('<h2>%s</h2>',esc_html($saved_post['post_title']));
                printf('<p><input type="checkbox" name="preview[]" value="%d"> Preview <input type="checkbox" name="import_from[]" value="%d"> Import - New content, ID %s %s</p>',esc_html($saved_post['ID']),esc_html($saved_post['ID']),esc_html($saved_post['ID']), esc_html($saved_post['post_title']));
                printf('<input type="hidden" name="new_posts[]" value="%d" />',esc_html($saved_post['ID']));
            }
            else {
                if($existing_post->post_modified != $saved_post['post_modified']) {
                    printf('<h2>%s</h2>',esc_html($saved_post['post_title']));
                    $oldernewer = $saved_post['post_modified'] > $existing_post->post_modified ? '<strong>Newer</strong>' : 'Older';
                    printf('<p><input type="checkbox" name="preview[]" value="%d"> Preview <input type="checkbox" name="import_from[]" value="%d"> Import - Modified content, ID %s %s<br />Playground version %s, Live site version %s (%s)</p>',esc_html($saved_post['ID']),esc_html($saved_post['ID']),esc_html($saved_post['ID']), esc_html($saved_post['post_title']), esc_html($saved_post['post_modified']), esc_html($existing_post->post_modified),esc_html($oldernewer));
                }
            }
        }
    }
    printf('<p><input type="checkbox" name="show_details" value="1" /> %s</p>',esc_html__('Show Details for Debugging','quick-playground'));
    submit_button('Import Selected Items','primary','import_button',false);
    printf('<input type="hidden" name="profile" value="%s" >',esc_attr($profile));
    echo '</form>';
    $pp = get_option('playground_profiles',array('default'));
    foreach($pp as $p) {
        if($p == $profile)
            continue;
        $savedfile = $qckply_site_uploads.'/quickplayground_posts_'.$p.'.json';
        if(file_exists($savedfile)) {
            printf('<h2>See saved data for <a href="%s">%s</a></h2>',esc_attr(admin_url('admin.php?page=qckply_sync&profile='.$p)),esc_html($p));
        }
    }
    } //end if play_posts
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