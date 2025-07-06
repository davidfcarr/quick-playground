<?php
add_action('init','quickplayground_update_tracking');
function quickplayground_update_tracking() {
    //if we're in a playground and the initial import is done
    if(quickplayground_is_playground() && get_option('playground_sync_date',false)) {
    add_action('wp_after_insert_post','quickplayground_post_updated');
    add_action('post_updated','quickplayground_post_updated');
    add_action('updated_option','quickplayground_updated_option');
    add_action('added_option','quickplayground_updated_option');
    add_action('added_post_meta','quickplayground_updated_postmeta', 10, 4);
    add_action('updated_postmeta','quickplayground_updated_postmeta', 10, 4);
    }
}

/**

*/
function quickplayground_post_updated($post_id) {
    $updated = get_option('playground_updated_posts',array());
    if(!in_array($post_id,$updated)) {
        $updated[] = $post_id;
        update_option('playground_updated_posts',$updated);
    }
}

/**
*/
function quickplayground_updated_option($option) {
    if(strpos($option,'layground_updated'))
        return;
    $excluded = ['cron','wp_user_roles','fresh_site','users_can_register'];
    if(in_array($option,$excluded) || strpos($option,'transient'))
        return;
    $updated = get_option('playground_updated_options',array());
    $updated[] = $option;
    update_option('playground_updated_options',$updated);
}

/**

*/
function quickplayground_updated_postmeta($meta_id, $post_id, $meta_key, $meta_value) {
    $excluded = ['_edit_lock'];
    if(in_array($meta_key,$excluded))
        return;
    $updated = get_option('playground_updated_postmeta',array());
    //key to overwrite previous entries
    $idkey = $post_id.$meta_key;
    $updated[$idkey] = array('post_id'=>$post_id,'meta_key'=>$meta_key);
    update_option('playground_updated_postmeta',$updated);
}