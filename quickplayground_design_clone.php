<?php

function quickplayground_clone_page() {
    if($_SERVER['SERVER_NAME'] == 'delta.local')
        update_option('playground_sync_origin','https://clubawesome.org');
    if(!empty($_POST) && !wp_verify_nonce( $_POST['playground'], 'quickplayground' ) ) 
    {
        echo '<h2>Security Error</h2>';
        return;
    }

    echo '<h1>Design and Theme Playground</h1>';
    $url = get_option('playground_sync_origin');
    echo '<p>Sync Origin: <a href="'.$url.'">'.$url.'</a></p>';
    if(!$url) {
        echo '<p>Design Playground is not configured. Please set the sync origin URL in the plugin settings.</p>';
        return;
    }

    $origin_url = get_option('playground_sync_origin').'/wp-json/quickplayground/v1/playground_clone/'.get_option('playground_profile');
    if(isset($_POST['clonenow']))
        echo quickplayground_clone();
    else{
        printf('<form method="post" action=""><input type="hidden" name="clonenow" value="1" /><button class="button button-primary">Clone Now</button>%s</form> | <a href="%s">See JSON</a></p>',wp_nonce_field('quickplayground','playground',true,false),$origin_url);
    }
    $premium = get_option('playground_premium',false);
    if($premium) {
        echo '<p>Welcome to the Pro version of the Design Playground!</p>';
        printf("<p>Calling do_action('quickplayground_clone_pro_form'), function exists %d</p>",var_export(function_exists('quickplayground_clone_pro_form'),true));
        do_action('quickplayground_clone_pro_form');
        return;
    } else {
        echo '<p>Upgrade to the Pro version for the ability to post changes from this playground back to your live website.</p>';
    }
}

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

function quickplayground_clone_log() {
    echo get_option('quickplayground_log');
}