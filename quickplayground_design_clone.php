<?php

/**
 * Displays the Design and Theme Playground admin page, handles cloning actions, and outputs API endpoints.
 */
function quickplayground_clone_page() {
    if(!empty($_POST) && !wp_verify_nonce( $_POST['playground'], 'quickplayground' ) ) 
    {
        echo '<h2>Security Error</h2>';
        return;
    }

    echo '<h1>Design and Theme Playground June 26, 2025</h1>';
    $baseurl = get_option('playground_sync_origin');
    $no_cache = get_option('playground_no_cache',false);
    $playground_profile = get_option('playground_profile','default');
    
    $url = $baseurl .'/wp-json/quickplayground/v1/playground_clone/'.$playground_profile.'?t='.time();
    if($no_cache) $url .= '&nocache=1';
    $taxurl = $baseurl .'/wp-json/quickplayground/v1/clone_taxonomy/'.$playground_profile.'?t='.time();
    if($no_cache) $taxurl .= '&nocache=1';
    $imgurl = $baseurl .'/wp-json/quickplayground/v1/clone_images/'.$playground_profile.'?t='.time();
    if($no_cache) $imgurl .= '&nocache=1';

    if(isset($_POST['clonenow'])) {
        echo '<h2>Cloning...'.$_POST['target'].'</h2>';
        echo quickplayground_clone($_POST['target']);
        echo '<p>Cloning complete. <a href="'.admin_url('admin.php?page=quickplayground_clone_log').'">View log</a></p>';
    }
    printf('<form method="post" action=""><input type="hidden" name="clonenow" value="1" />
    <p><input type="radio" name="target" value="" checked="checked" /> All <input type="radio" name="target" value="posts" /> Posts <input type="radio" name="target" value="taxonomy" /> Taxonomy and Metadata <input type="radio" name="target" value="images" /> Images</p>
    <p><button class="button button-primary">Clone Now</button></p>%s</form></p>',wp_nonce_field('quickplayground','playground',true,false));
    printf('<p>API endpoints <br>Posts: <a  target="_blank" href="%s">%s</a><br>Taxonomy and Metadata: <a  target="_blank" href="%s">%s</a><br>Images: <a target="_blank" href="%s">%s</a></p>',
        $url, $url, $taxurl, $taxurl, $imgurl, $imgurl);

    $premium = get_option('playground_premium',false);
    if($premium) {
        echo '<p>Welcome to the Pro version of the Design Playground!</p>';
        do_action('quickplayground_clone_pro_form');
        return;
    } else {
        echo '<p>Upgrade to the Pro version for the ability to post changes from this playground back to your live website.</p>';
    }
}

/**
 * Syncs post changes from the playground back to the live site.
 *
 * @param array $changes Array of post data to sync.
 * @return string        Output log of the sync process.
 */
function quickplayground_sync_changes($changes) {

    foreach($changes as $post) {

        $post_id = isset($post['ID']) ? intval($post['ID']) : 0;

        if($post_id) {

            $result = wp_update_post($post, true);

            if(is_wp_error($result)) {

                $output = '<p>Error inserting post: '.$post['post_title'].' '.$result->get_error_message().'</p>';

            }

            else {

                $output = '<p>Inserted post: '.$post['post_title'].' ID '.$post_id.'</p>';

            }

        }

        else {

           $result = wp_insert_post($post, true);

            if(is_wp_error($result)) {

                $output = '<p>Error inserting post: '.$post['post_title'].' '.$result->get_error_message().'</p>';

            }

            else {

                $output = '<p>Inserted post: '.$post['post_title'].' ID '.$result.'</p>';

            }

        }

    }

return $output;

}

/**
 * Displays the clone log and provides links to view JSON and logs for posts, taxonomy, and images.
 */
function quickplayground_clone_log() {
    printf('<h1>Design Playground Clone Log</h1><p>');
    printf('<a href="%s">Posts JSON</a> |',admin_url('admin.php?page=quickplayground_clone_log&posts_json=1'));
    printf('<a href="%s">Taxonomy and Metadata</a> |',admin_url('admin.php?page=quickplayground_clone_log&tax=1'));
    printf('<a href="%s">Images</a>',admin_url('admin.php?page=quickplayground_clone_log&images=1'));
    echo '</p>';
    if(isset($_GET['posts_json'])) {
        echo '<h2>Incoming JSON</h2>';
        echo '<pre>'.htmlentities(get_option('clone_posts_json')).'</pre>';
        echo '<h2>Incoming JSON modified</h2>';
        echo '<pre>'.htmlentities(json_encode(json_decode(get_option('clone_posts_modified')),JSON_PRETTY_PRINT)).'</pre>';
    } 
    elseif(isset($_GET['tax'])) {
        echo '<h2>Metadata Copy</h2>';
        echo get_option('clone_tax_log');
        echo '<h2>JSON</h2>';
        echo '<pre>'.htmlentities(json_encode(json_decode(get_option('clone_tax_json')), JSON_PRETTY_PRINT)).'</pre>';
    }
    elseif(isset($_GET['images'])) {
        echo '<h2>Images</h2>';
        echo get_option('clone_images_log');
        echo '<h2>JSON</h2>';
        echo '<pre>'.htmlentities(json_encode(json_decode(get_option('clone_images_json')),JSON_PRETTY_PRINT)).'</pre>';
    }
    else {
    echo get_option('clone_posts_log');
    }
}