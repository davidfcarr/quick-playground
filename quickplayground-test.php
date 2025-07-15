<?php
function quickplayground_test() {
    $top = quickplayground_top_ids();
    $fresh = quickplayground_top_ids(true);
    foreach($fresh as $slug => $f) {
        $changed = $f > $top[$slug] ? 'changed' : 'unchanged';
        printf('<p>%s is %s by test %s > %s</p>',$slug, $changed, $f, $top[$slug]);
    }
    return;
    global $wpdb, $playground_uploads;
    $sync_origin = 'https://www.clubawesome.org';//get_option('playground_sync_origin');
    $mysite_url = rtrim(get_option('siteurl'),'/');
    //reset
    $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type='attachment'");

    $json = file_get_contents('https://www.clubawesome.org/wp-json/quickplayground/v1/clone_posts/default?nocache=1');
    $clone = json_decode($json,true);
    if(!empty($clone['site_logo'])) {
        $result = quickplayground_sideload($clone['site_logo']);
        if(is_wp_error($result)) {
            $out = "<p>Error downloading ".$clone['site_icon']['guid']."</p>";
        }
        else
            $out = $result;
        $clone = quickplayground_clone_output($clone, $out);
    }
    if(!empty($clone['site_icon'])) {
        $result = quickplayground_sideload($clone['site_icon']);
        if(is_wp_error($result)) {
            $out = "<p>Error downloading ".$clone['site_icon']['guid']."</p>";
        }
        else
            $out = $result;
        $clone = quickplayground_clone_output($clone, $out);
    }
    if(!empty($clone['front_page_thumbnail'])) {
        $result = quickplayground_sideload($clone['front_page_thumbnail']);
        if(is_wp_error($result)) {
            $out = "<p>Error downloading ".$clone['front_page_thumbnail']['guid']."</p>";
        }
        else
            $out = $result;
        $clone = quickplayground_clone_output($clone, $out);
    }

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
