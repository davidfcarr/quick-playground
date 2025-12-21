<?php

function qckply_clone_prompts() {
    echo '<h1>Edit Playground Prompts</h1>';
    echo '<p>You can add prompts to be displayed with the Playground first loads or on specific pages. Optionally, you can have a prompt to add or edit content displayed on every page.</p>';
    $sync_origin = get_option('qckply_sync_origin');
    $qckply_profile = get_option('qckply_profile','default');
    $save_prompts_url = $sync_origin.'/wp-json/quickplayground/v1/save_prompts/'.$qckply_profile;
    if(!empty($_POST) ) 
    {
        if(!wp_verify_nonce( $_POST['playground'], 'quickplayground' )) {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
        }
        update_option('show_playground_prompt_keys',intval($_POST['show']));

        $prompts = [];
        $postvars = $_POST;
        foreach($postvars['qckply_messages'] as $key => $value) {
            if(!empty(trim($value)))
                $prompts[$key] = wp_kses_post(stripslashes($value));
        }
        if(!empty($postvars['custom_message_page']) && !empty($postvars['custom_message'])) {
            $page = trim(sanitize_text_field($postvars['custom_message_page']));
            $content = wp_kses_post(stripslashes($postvars['custom_message']));
            printf('<p>Setting %s to %s</p>',esc_html($page),wp_kses_post($content));
            $prompts[$page] = $content;
        }
        set_transient('qckply_messages',$prompts,5*DAY_IN_SECONDS);

        $clone = json_encode($prompts);
        printf('<p>Posting to %s</p>',esc_html($save_prompts_url));
        $response = wp_remote_post($save_prompts_url, array(
            'body' => $clone,
            'headers' => array('Content-Type' => 'application/json')
        ));
    if(is_wp_error($response)) {

        echo '<p>Error: '.esc_html(htmlentities($response->get_error_message())).'</p>';

    } else {

        $returned = $response['body'];

        if(!is_array($returned)) {

            $returned = json_decode($returned, true);

        }

        if(!is_array($returned)) {
            echo 'return not an array: '.esc_html(var_export($returned,true));
        } else {
          foreach($returned as $label => $value)
            printf('<p>Saved prompts result %s: %s</p>',esc_html($label),esc_html(var_export($value,true))); 
        }
   }

    }
    $messages = get_transient('qckply_messages',[]);
    $show = get_option('show_playground_prompt_keys',false);
    printf('<form  class="playground-form"  method="post" action="">',esc_attr($_SERVER['REQUEST_URI']));
    wp_nonce_field('quickplayground','playground',true,true);
    if(empty($prompts))
        $prompts = qckply_get_prompts_remote($qckply_profile);
    if(!is_array($prompts)) {
        echo '<h2>Error Loading Prompts</h2>';
    }
    foreach($prompts as $key => $value) {
    if('sync_prompts' == $key || 'new_ids' == $key)
        continue;
    printf('<p>Message: %s <br /><textarea name="qckply_messages[%s]"  cols="100" rows="3">%s</textarea></p>',esc_html($key),esc_attr($key),esc_html($value));
    if('welcome' == $key)
    echo '<p><em>Displayed when the Playground first loads.</em></p>';
    elseif('admin-welcome' == $key)
    echo '<p><em>Displayed on first visit to the administrator\'s dashboard.</em></p>';
    }
    $key = '';
    if(isset($_GET['key'])) $key = sanitize_text_field($_GET['key']); 
    echo '<p>Custom Message target: <input type="text" name="custom_message_page" placeholder="special-offers" value="'. esc_attr($key) . '" /><br /><em>Front End Examples: "home" for the home page, "special-offers" (for mysite.com/special-offers/) or post_type:rsvpmaker (any example of the specified post type)<br >Admin Examples: "dashboard" (/wp-admin/) or "qckply_clone_page" (mysite.com/wp-admin/admin.php?page=qckply_clone_page)</em><br /><textarea cols="100" rows="3" name="custom_message"></textarea></p>';
    printf('<p><input type="radio" name="show" value="1" %s /> Show ',$show ? ' checked="checked" ': '');
    printf('<input type="radio" name="show" value="0" %s /> Do NOT Show prompt targets as I browse</p>',!$show ? ' checked="checked" ': '');
    echo "<p><button>Update</button></p></form>";

    printf('<p>Messages from transients %s</p>',esc_html(var_export($messages,true)));
}