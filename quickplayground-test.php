<?php
function quickplayground_test() {
    global $wpdb, $playground_uploads;
    
    $baseurl = get_option('playground_sync_origin');
    $is_clone = get_option('is_playground_clone');
    if(isset($_POST['is_playground_clone']))
    {
        $is_clone = intval($_POST['is_playground_clone']);
        if($is_clone)
            update_option('is_playground_clone', $is_clone);
        else {
            echo '<p>Disabling clone mode</p>';
            delete_option('is_playground_clone');
        }
        printf('<p>Playground clone mode is now %s</p>', ($is_clone) ? 'enabled' : 'disabled');
        $baseurl = sanitize_text_field($_POST['playground_sync_origin']);
        update_option('playground_sync_origin', $baseurl);
    }
    echo "is clone $is_clone, baseurl $baseurl";
    printf('<form method="post" action="%s" class="playground-form" >
    <p><input name="is_playground_clone" type="radio" value="1" %s >Clone On <input name="is_playground_clone" type="radio" value="0" %s >Clone Off </p>
   <p> <input name="playground_sync_origin" type="text" value="%s" placeholder="https://example.com/quickplayground" /></p>
    <input type="hidden" name="action" value="quickplayground_test" />
    <button>Update</button></form>',admin_url('admin.php?page=quickplayground_test'),($is_clone) ? 'checked="checked"' : '',(!$is_clone) ? 'checked="checked"' : '', $baseurl);

    $disable = get_option('disable_playground_premium', false);

    if(isset($_POST['disable_playground_premium'])) {
        $disable = intval($_POST['disable_playground_premium']);
        update_option('disable_playground_premium', $disable);
    }

    printf('<form method="post" action="%s" class="playground-form" >
    <p><input name="disable_playground_premium" type="radio" value="1" %s > Disable Pro <input name="disable_playground_premium" type="radio" value="0" %s > Disable Pro Off </p>
    <input type="hidden" name="action" value="quickplayground_test" />
    <button>Update</button></form>',admin_url('admin.php?page=quickplayground_test'),($disable) ? 'checked="checked"' : '',(!$disable) ? 'checked="checked"' : '', $baseurl);

    echo '<h2>Custom Tables</h2>';
    print_r(quickplayground_custom_tables_clone());

    $updated_options = get_option('playground_updated_options');
    $updated_posts = get_option('playground_updated_posts');
    $updated_postmeta = get_option('playground_updated_postmeta');

    printf('<p>Playground Updated Posts</p><pre>%s</pre>',var_export($updated_posts,true));
    printf('<p>Playground Updated Postmeta</p><pre>%s</pre>',var_export($updated_postmeta,true));
    printf('<p>Playground Updated Options</p><pre>%s</pre>',var_export($updated_options,true));
    
}
