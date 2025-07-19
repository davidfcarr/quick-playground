<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action('init','qckply_update_tracking');
function qckply_update_tracking() {
    //if we're in a playground and the initial import is done
    if(qckply_is_playground() && get_option('qckply_sync_date',false)) {
    add_action('wp_after_insert_post','qckply_post_updated');
    add_action('post_updated','qckply_post_updated');
    add_action('updated_option','qckply_updated_option');
    add_action('added_option','qckply_updated_option');
    add_action('added_post_meta','qckply_updated_postmeta', 10, 4);
    add_action('updated_postmeta','qckply_updated_postmeta', 10, 4);
    }
}

function qckply_top_ids($fresh = false) {
    global $wpdb;
    if(!$fresh) {
    $top = get_option('qckply_top_ids',[]);
    if(!empty($top['post_modified']))
        return $top;
    }
    $top['posts'] = $wpdb->get_var("SELECT ID FROM $wpdb->posts ORDER BY ID DESC");
    $top['postmeta'] = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta ORDER BY meta_id DESC");
    $top['terms'] = $wpdb->get_var("SELECT term_id FROM $wpdb->terms ORDER BY term_id DESC");
    $top['term_taxonomy'] = $wpdb->get_var("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy ORDER BY term_taxonomy_id DESC");
    $top['post_modified'] = $wpdb->get_var("SELECT post_modified FROM $wpdb->posts ORDER BY post_modified DESC");
    if(!$fresh)
        update_option('qckply_top_ids',$top);
    return $top;
}

/**

*/
function qckply_post_updated($post_id) {
    $updated = get_option('qckply_updated_posts',array());
    if(!in_array($post_id,$updated)) {
        $updated[] = $post_id;
        update_option('qckply_updated_posts',$updated);
    }
}

/**
*/
function qckply_updated_option($option) {
    if(strpos($option,'layground_updated'))
        return;
    $excluded = ['cron','wp_user_roles','fresh_site','users_can_register'];
    if(in_array($option,$excluded) || strpos($option,'transient'))
        return;
    $updated = get_option('qckply_updated_options',array());
    $updated[] = $option;
    update_option('qckply_updated_options',$updated);
}

/**

*/
function qckply_updated_postmeta($meta_id, $post_id, $meta_key, $meta_value) {
    $excluded = ['_edit_lock'];
    if(in_array($meta_key,$excluded))
        return;
    $updated = get_option('qckply_updated_postmeta',array());
    //key to overwrite previous entries
    $idkey = $post_id.$meta_key;
    $updated[$idkey] = array('post_id'=>$post_id,'meta_key'=>$meta_key);
    update_option('qckply_updated_postmeta',$updated);
}