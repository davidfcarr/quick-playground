<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action('init','qckply_update_tracking');
function qckply_update_tracking() {
    //if we're in a playground and the initial import is done
    if(qckply_is_playground()) {
    add_action('wp_after_insert_post','qckply_post_updated',10,2);
    add_action('post_updated','qckply_post_updated',10,2);
    add_action('updated_option','qckply_updated_option');
    add_action('added_option','qckply_updated_option');
    add_action('added_post_meta','qckply_updated_postmeta', 10, 4);
    add_action('updated_postmeta','qckply_updated_postmeta', 10, 4);
    }
}

function qckply_top_ids($fresh = false, $update = true) {
    global $wpdb;
    if(!$fresh) {
    $top = get_option('qckply_top_ids',[]);
    if(is_array($top) && !empty($top['post_modified']))
        return $top;
    }
    $top['posts'] = $wpdb->get_var($wpdb->prepare("SELECT ID FROM %i ORDER BY ID DESC",$wpdb->posts));
    $top['postmeta'] = $wpdb->get_var($wpdb->prepare("SELECT meta_id FROM %i ORDER BY meta_id DESC",$wpdb->postmeta));
    $top['terms'] = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM %i ORDER BY term_id DESC",$wpdb->terms));
    $top['term_taxonomy'] = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM %i ORDER BY term_taxonomy_id DESC",$wpdb->term_taxonomy));
    $top['post_modified'] = date('Y-m-d H:i:s', strtotime('-1 day'));
    if($update)
        update_option('qckply_top_ids',$top);
    return $top;
}

function qckply_post_updated($post_id, $post) {
    if('attachment' == $post->post_type)
        update_post_meta($post_id,'attachment_updated',time());
    $updated = get_option('qckply_updated_posts',array());
    if(!in_array($post_id,$updated)) {
        $updated[] = $post_id;
        update_option('qckply_updated_posts',$updated);
    }
}

function qckply_updated_option($option) {
    if(strpos($option,'ckply_updated'))
        return;
    $excluded = ['cron','wp_user_roles','fresh_site','users_can_register','siteurl','home'];
    if(in_array($option,$excluded) || strpos($option,'transient'))
        return;
    $updated = get_option('qckply_updated_options',array());
    $updated[] = $option;
    update_option('qckply_updated_options',$updated);
}

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