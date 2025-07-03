<?php
function quickplayground_test() {
    global $wpdb;
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
    printf('<form method="post" action="%s">
    <p><input name="is_playground_clone" type="radio" value="1" %s >Clone On <input name="is_playground_clone" type="radio" value="0" %s >Clone Off </p>
   <p> <input name="playground_sync_origin" type="text" value="%s" placeholder="https://example.com/quickplayground" /></p>
    <input type="hidden" name="action" value="quickplayground_test" />
    <button>Update</button></form>',admin_url('admin.php?page=quickplayground_test'),($is_clone) ? 'checked="checked"' : '',(!$is_clone) ? 'checked="checked"' : '', $baseurl);

    $disable = get_option('disable_playground_premium', false);

    if(isset($_POST['disable_playground_premium'])) {
        $disable = intval($_POST['disable_playground_premium']);
        update_option('disable_playground_premium', $disable);
    }

    printf('<form method="post" action="%s">
    <p><input name="disable_playground_premium" type="radio" value="1" %s > Disable Pro <input name="disable_playground_premium" type="radio" value="0" %s > Disable Pro Off </p>
    <input type="hidden" name="action" value="quickplayground_test" />
    <button>Update</button></form>',admin_url('admin.php?page=quickplayground_test'),($disable) ? 'checked="checked"' : '',(!$disable) ? 'checked="checked"' : '', $baseurl);

    for($i = 0; $i < 100; $i++) {
        $fake = quickplayground_fake_user($i);
        printf('<p>Fake User: %s</p>', var_export($fake, true));
    }
}
