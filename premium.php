<?php
/**
 * Checks if Playground Pro features are enabled and not expired.
 *
 * @return bool|string Returns the enabled key if valid, or false if not enabled or expired.
 */
function playground_premium_enabled() {
    if(get_option('disable_playground_premium',false))
        return false;
    $enabled = is_multisite() ? get_blog_option(1,'playground_premium_enabled') : get_option('playground_premium_enabled');
    $expiration = is_multisite() ? get_blog_option(1,'playground_premium_expiration') : get_option('playground_premium_expiration');
    $expiration = intval($expiration);
    //todo periodically re-validate
    return (!empty($enabled) && $expiration > time()) ? $enabled : false;
}

/**
 * Displays a status message about Playground Pro features and their expiration.
 *
 * @return bool True if Pro features are enabled, false otherwise.
 */
function playground_premium_status_message() {
    global $current_user;
    $enabled = is_multisite() ? get_blog_option(1,'playground_premium_enabled') : get_option('playground_premium_enabled');
    $expiration = is_multisite() ? get_blog_option(1,'playground_premium_expiration') : get_option('playground_premium_expiration');
    $expiration = intval($expiration);
    if(empty($enabled))
        return false;
    if($expiration > time() + (31 * DAY_IN_SECONDS)) {
        echo '<div class="notice notice-success"><p>Pro Features Enabled</p></div>';
        return true;
    }
    if(time() < $expiration)
    {
        printf('<div class="notice notice-success"><p>Pro Features Trial Expires %s</p></div>',rsvpmaker_date('F j, Y',$expiration));
        $email = get_option('playground_premium_email');
        $url = 'https://davidfcarr.com/wp-json/quickplayground/v1/payment?email='.$email.'&t='.time();
        $response = wp_remote_get($url);
        if(is_wp_error($response)) {
            echo '<p>Error: '.htmlentities($response->get_error_message()).'</p>';
        } else {
            $response = json_decode($response['body'],true);
            echo '<div class="notice"><p>'.$response['payprompt'].'</p></div>';
        }
    return true;
    }
    else    {
    printf('<div class="notice notice-warning"><p>Pro Features Trial Expired %s</p></div>',rsvpmaker_date('F j, Y',$expiration));
    return false;
    }    
}

/**
 * Displays and processes the Playground Pro admin page, including license key and registration form.
 */
function quickplayground_pro() {
    global $current_user;
    if(!empty($_POST) && !wp_verify_nonce( $_POST['playground'], 'quickplayground' ) ) 
    {
        echo '<h2>Security Error</h2>';
        return;
    }
    if(isset($_GET['reset'])) {
        if(is_multisite())
        delete_blog_option(1,'playground_premium_enabled');
        else
        delete_option('playground_premium_enabled');
    }
    if(isset($_POST['unlock'])) {
        $enabled = (isset($_POST['unlock'])) ? intval($_POST['unlock']) : 0;
        update_option('playground_premium_enabled', $enabled);
    }
    if(isset($_POST['email'])) {
        $post['email'] = sanitize_text_field($_POST['email']);
        $post['first'] = sanitize_text_field($_POST['first']);
        $post['last'] = sanitize_text_field($_POST['last']);
        update_option('playground_premium_email', $post['email']);
        $response = wp_remote_post("https://www.davidfcarr.com/wp-json/quickplayground/v1/register",array('body'=>$post));
        if(is_wp_error($response)) {
            echo '<p>Error: '.htmlentities($response->get_error_message()).'</p>';
        } else {
            $response = json_decode($response['body'],true);
            echo '<div class="notice"><p>'.$response['message'].'</p></div>';
        }
    }

    if(isset($_POST['key'])) {
        $enabled = $key = sanitize_text_field($_POST['key']);
    $enabled = is_multisite() ? get_blog_option(1,'playground_premium_enabled') : get_option('playground_premium_enabled');
    }
    else {
    $key = $enabled = is_multisite() ? get_blog_option(1,'playground_premium_enabled') : get_option('playground_premium_enabled');
    }
    if($key) {
        $email = get_option('playground_premium_email');
        echo "<p>Checking https://davidfcarr.com/wp-json/quickplayground/v1/license?email=$email&key=$key&t=".time()."</p>\n";
        $response = wp_remote_get("https://davidfcarr.com/wp-json/quickplayground/v1/license?email=$email&key=$key&t=".time());
        if(is_wp_error($response)) {
            echo '<p>Error: '.htmlentities($response->get_error_message()).'</p>';
        } else {
            $response = json_decode($response['body'],true);
            if($response['is_valid']) {
                $enabled = $key;
                printf('<div class="notice notice-success"><p>Valid %s</p></div>',htmlentities(var_export($response,true)));
                if(is_multisite()) update_blog_option(1,'playground_premium_enabled',$key); else update_option('playground_premium_enabled',$key);
                if(!empty($response['payprompt']))
                    echo '<div class="notice"><p>'.$response['payprompt'].'</p></div>';
            }
            else {
                echo '<div class="notice notice-error"><p>Failed to validate license key</p></div>';
                print_r($response);
            }
            if($response['expiration']) {
                if(is_multisite()) update_blog_option(1,'playground_premium_expiration',$response['expiration']); else update_option('playground_premium_expiration',$response['expiration']);
            }
        }
    }

    ?>
    <h2>PlayGround Pro</h2>
    <?php

    $good = playground_premium_status_message();
    if(!$enabled)
    {
    ?>
    <form method="post" class="playground" action="<?php echo admin_url('admin.php?page=quickplayground_pro')?>
        <?php wp_nonce_field('quickplayground','playground',true,true); ?>
        <h3>Request a license key by email</h3>
        <p><label>First Name</label> <input type="text" name="first" value="<?php echo $current_user->last_name; ?>" /> </p>
        <p><label>Last Name</label> <input type="text" name="last" value="<?php echo $current_user->first_name; ?>" /> </p>
        <p><label>Email</label> <input type="text" name="email" value="<?php echo $current_user->user_email; ?>" /> </p>
        <p><input type="checkbox" name="reset" value="1" /> Reset Code </p>
        <p><button>Submit</button></p>
    <p>You will be added to the Carr Communications Inc. email list for WordPress projects. The license key will be sent by email.</p>
    </form>
    <?php
    }
    ?>
    <form method="post" class="playground" action="<?php echo admin_url('admin.php?page=quickplayground_pro')?>
        <?php wp_nonce_field('quickplayground','playground',true,true); ?>
        <h3>Enter your license key</h3>
        <p><label>Key</label> <input type="text" name="key" value="<?php echo $enabled; ?>" /> </p>
        <p><button>Submit</button></p>
    </form>
    <p>In addition to enabling new UI options for customizing the playground, your license key gives you access to the following action and filter functions.<p>
<pre>
    //called at the bottom of the playground setup form 
    do_action('quickplayground_additional_setup_form_fields');
    Example:
    add_action('quickplayground_additional_setup_form_fields',my_quickplayground_fields');
    function my_quickplayground_fields() {echo '<?php echo htmlentities('<input type="checkbox" name="enable_special_feature" value="1">');?>';}

    //filter the new blueprint as a PHP array before it is saved
    $blueprint = apply_filters('quickplayground_new_blueprint',$blueprint);
    Example:
    add_filter('quickplayground_new_blueprint','my_quickplayground_new_blueprint');
    function my_quickplayground_new_blueprint($blueprint) {
        if(isset($_POST['enable_special_feature']))
            $blueprint['steps'][] = array("step"=>"importWordPressFiles",
            "wordPressFilesZip"=>array("resource": "url","url": "https://mysite.com/import.zip"));
        return $blueprint;
    }

    //filter the new settings to be applied to the playground environment.
    $settings = apply_filters('quickplayground_new_settings',$settings);
 
    //filter the previously saved playground steps ($blueprint['steps']) and other variables as a PHP array
    $blueprint = apply_filters('quickplayground_blueprint',$blueprint);
    Example: see above, but without access to the $_POST variables

    //filter the array used to copy content and settings from your website to the playground
    $clone = apply_filters('quickplayground_design_playground_clone',$clone);
    Example:
    add_filter('quickplayground_design_playground_clone','my_quickplayground_clone');
    function my_quickplayground_clone($clone) {
        $clone['posts'][] = (object) array('post_title'=>'Demo page','post_content'=>'custom_content','post_type'=>'custom_post_type','post_status'=>'publish');
        return $clone;
    }
    </pre>
<?php
}