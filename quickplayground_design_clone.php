<?php

/**
 * Displays the Design and Theme Playground admin page, handles cloning actions, and outputs API endpoints.
 */
function quickplayground_clone_page() {
    if(!empty($_POST) && !wp_verify_nonce( $_POST['playground'], 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }

    echo '<h1>'.esc_html__('Design and Theme Playground','quick-playground').'</h1>';
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
        echo '<h2>'.esc_html__('Cloning...','quick-playground').' '.esc_html($_POST['target']).'</h2>';
        if(!empty($_POST['disable_cache'])) {
            update_option('playground_no_cache',true);
            delete_option('cache_created');
        }
        echo wp_kses_post(quickplayground_clone(sanitize_text_field($_POST['target'])));
        echo '<p>'.sprintf(
            /* translators: %s: log page URL */
            esc_html__('Cloning complete. %s','quick-playground'),
            '<a href="'.esc_url(admin_url('admin.php?page=quickplayground_clone_log')).'">'.esc_html__('View log','quick-playground').'</a>'
        ).'</p>';
    }
    $cache_created = intval(get_option('cache_created'),0);
    if($cache_created) {
        $cache_notice = '<p>'.sprintf(
            /* translators: 1: cache created time, 2: cache duration */
            esc_html__('By default, Quick Playground will fetch the cached content created on %1$s.','quick-playground'),
            esc_html(date_i18n(get_option('date_format').' '.get_option('time_format'),$cache_created))
        ).'</p><p><input type="checkbox" name="disable_cache" /> '.esc_html__('Bypass cache and fetch live content','quick-playground').'</p>';
    }
    else {
        $cache_notice = '';
    } 
    printf(
        '<form method="post" action=""><input type="hidden" name="clonenow" value="1" />
        <p>
            <input type="radio" name="target" value="" checked="checked" /> %s 
            <input type="radio" name="target" value="posts" /> %s 
            <input type="radio" name="target" value="taxonomy" /> %s 
            <input type="radio" name="target" value="images" /> %s
        </p>
        %s
        <p><button class="button button-primary">%s</button></p>%s</form></p>',
        esc_html__('All','quick-playground'),
        esc_html__('Posts','quick-playground'),
        esc_html__('Taxonomy and Metadata','quick-playground'),
        esc_html__('Images','quick-playground'),
        $cache_notice,
        esc_html__('Clone Now','quick-playground'),
        wp_nonce_field('quickplayground','playground',true,false)
    );
    printf(
        '<p>%s<br>%s: <a target="_blank" href="%s">%s</a><br>%s: <a target="_blank" href="%s">%s</a><br>%s: <a target="_blank" href="%s">%s</a></p>',
        esc_html__('API endpoints','quick-playground'),
        esc_html__('Posts','quick-playground'), esc_url($url), esc_html($url),
        esc_html__('Taxonomy and Metadata','quick-playground'), esc_url($taxurl), esc_html($taxurl),
        esc_html__('Images','quick-playground'), esc_url($imgurl), esc_html($imgurl)
    );

    $premium = playground_premium_enabled();
    if($premium) {
        echo '<p>'.esc_html__('Welcome to the Pro version of the Design Playground!','quick-playground').'</p>';
        do_action('quickplayground_clone_pro_form');
        return;
    } else {
        echo '<p>'.esc_html__('Upgrade to the Pro version for the ability to easily save changes to this playground environment for future sessions. You can also post selected changes from this playground back to your live website (beta).','quick-playground').'</p>';
    }
}

/**
 * Syncs post changes from the playground back to the live site.
 *
 * @param array $changes Array of post data to sync.
 * @return string        Output log of the sync process.
 */
function quickplayground_sync_changes($changes) {
    $output = '';
    foreach($changes as $post) {
        $post_id = isset($post['ID']) ? intval($post['ID']) : 0;

        if($post_id) {
            $result = wp_update_post($post, true);

            if(is_wp_error($result)) {
                $output .= '<p>' . sprintf(
                    /* translators: 1: post title, 2: error message */
                    esc_html__('Error updating post: %1$s %2$s', 'quick-playground'),
                    esc_html($post['post_title']),
                    esc_html($result->get_error_message())
                ) . '</p>';
            } else {
                $output .= '<p>' . sprintf(
                    /* translators: 1: post title, 2: post ID */
                    esc_html__('Updated post: %1$s ID %2$d', 'quick-playground'),
                    esc_html($post['post_title']),
                    intval($post_id)
                ) . '</p>';
            }
        } else {
            $result = wp_insert_post($post, true);

            if(is_wp_error($result)) {
                $output .= '<p>' . sprintf(
                    /* translators: 1: post title, 2: error message */
                    esc_html__('Error inserting post: %1$s %2$s', 'quick-playground'),
                    esc_html($post['post_title']),
                    esc_html($result->get_error_message())
                ) . '</p>';
            } else {
                $output .= '<p>' . sprintf(
                    /* translators: 1: post title, 2: post ID */
                    esc_html__('Inserted post: %1$s ID %2$d', 'quick-playground'),
                    esc_html($post['post_title']),
                    intval($result)
                ) . '</p>';
            }
        }
    }
    return $output;
}

/**
 * Displays the clone log and provides links to view JSON and logs for posts, taxonomy, and images.
 */
function quickplayground_clone_log() {
    printf('<h1>%s</h1><p>', esc_html__('Design Playground Clone Log', 'quick-playground'));
    printf(
        '<a href="%s">%s</a> | ',
        esc_url(admin_url('admin.php?page=quickplayground_clone_log&posts_json=1')),
        esc_html__('Posts JSON', 'quick-playground')
    );
    printf(
        '<a href="%s">%s</a> | ',
        esc_url(admin_url('admin.php?page=quickplayground_clone_log&tax=1')),
        esc_html__('Taxonomy and Metadata', 'quick-playground')
    );
    printf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('admin.php?page=quickplayground_clone_log&images=1')),
        esc_html__('Images', 'quick-playground')
    );
    echo '</p>';
    if(isset($_GET['posts_json'])) {
        echo '<h2>'.esc_html__('Incoming JSON','quick-playground').'</h2>';
        echo '<pre>'.esc_html(get_option('clone_posts_json')).'</pre>';
        echo '<h2>'.esc_html__('Incoming JSON modified','quick-playground').'</h2>';
        echo '<pre>'.esc_html(json_encode(json_decode(get_option('clone_posts_modified')),JSON_PRETTY_PRINT)).'</pre>';
    } 
    elseif(isset($_GET['tax'])) {
        echo '<h2>'.esc_html__('Metadata Copy','quick-playground').'</h2>';
        echo wp_kses_post(get_option('clone_tax_log'));
        echo '<h2>'.esc_html__('JSON','quick-playground').'</h2>';
        echo '<pre>'.esc_html(json_encode(json_decode(get_option('clone_tax_json')), JSON_PRETTY_PRINT)).'</pre>';
    }
    elseif(isset($_GET['images'])) {
        echo '<h2>'.esc_html__('Images','quick-playground').'</h2>';
        echo wp_kses_post(get_option('clone_images_log'));
        echo '<h2>'.esc_html__('JSON','quick-playground').'</h2>';
        echo '<pre>'.esc_html(json_encode(json_decode(get_option('clone_images_json')),JSON_PRETTY_PRINT)).'</pre>';
    }
    else {
        echo wp_kses_post(get_option('clone_posts_log'));
    }
}