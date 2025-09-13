<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Displays the Design and Theme Playground admin page, handles cloning actions, and outputs API endpoints.
 */
function qckply_clone_page() {
    //if the request includes anything other than $_GET['page'], check nonce
    if(sizeof($_REQUEST) > 1 && (empty( $_REQUEST['playground']) || !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['playground'])), 'quickplayground' ) )) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }  

    echo '<h1>'.esc_html__('Quick Playground','quick-playground').'</h1>';
    echo '<h2>'.esc_html__('Design and Plugin Testing','quick-playground').'</h2>';
    echo '<p>'.esc_html__('Use this screen to manually re-import any content that may not have imported correctly','quick-playground').'</p>';
    $baseurl = get_option('qckply_sync_origin');
    $no_cache = get_option('qckply_no_cache',false);
    $qckply_profile = get_option('qckply_profile','default');
    $prompts = qckply_get_prompts_remote($qckply_profile);
    $url = $baseurl .'/wp-json/quickplayground/v1/clone_posts/'.$qckply_profile.'?t='.time();
    if($no_cache) $url .= '&nocache=1';
    $taxurl = $baseurl .'/wp-json/quickplayground/v1/clone_taxonomy/'.$qckply_profile.'?t='.time();
    if($no_cache) $taxurl .= '&nocache=1';
    $imgurl = $baseurl .'/wp-json/quickplayground/v1/clone_images/'.$qckply_profile.'?t='.time();
    if($no_cache) $imgurl .= '&nocache=1';

    $local_directories = qckply_get_directories();
    $remote_directories = get_option('qckply_origin_directories');
    printf('<p>Local directories: %s</p>', esc_html(var_export($local_directories,true)));
    printf('<p>Remote directories: %s</p>', esc_html(var_export($remote_directories,true)));

    if(isset($_REQUEST['clonenow']) && isset($_REQUEST['target'])) {
        $target = sanitize_text_field($_REQUEST['target']);
        echo '<h2>'.esc_html__('Cloning...','quick-playground').' '.esc_html($target).'</h2>';
        if(!empty($_REQUEST['toggle_cache'])) {
            if('disable' == $_REQUEST['toggle_cache']) {
                update_option('qckply_no_cache',true);
                delete_option('cache_created');
            }
            if('enable' == $_REQUEST['toggle_cache']) {
                update_option('qckply_no_cache',false);
            }
        }
        if('images' == $target) {
            $response = qckply_clone_images('images');
            if(!empty($response['message'])) {
                echo wp_kses_post($response['message']);
            }
        }

        echo wp_kses_post(qckply_clone(sanitize_text_field($_POST['target'])));
        echo '<p>'.sprintf(
            /* translators: %s: log page URL */
            esc_html__('Cloning complete. %s','quick-playground'),
            '<a href="'.esc_url(admin_url('admin.php?page=qckply_clone_log')).'">'.esc_html__('View log','quick-playground').'</a>'
        ).'</p>';
    }
    $cache_created = intval(get_option('cache_created'),0);
    if($no_cache) {
        echo "<p>Live content loaded.</p>";
        $cache_notice = sprintf('<p><input type="checkbox" name="toggle_cache" value="enable" /> '.esc_html__('Fetch cached content instead','quick-playground').'</p>');
    } 
    else
    {
        $cache_notice = '<p>'.sprintf(
            /* translators: 1: cache created time, 2: cache duration */
            esc_html__('By default, Quick Playground will fetch the cached content created on %1$s.','quick-playground'),
            esc_html(date_i18n(get_option('date_format').' '.get_option('time_format'),$cache_created))
        ).'</p><p><input type="checkbox" name="toggle_cache" value="disable" /> '.esc_html__('Bypass cache and fetch live content','quick-playground').'</p>';
    }

    printf(
        '<form  class="qckply-form"  method="post" action=""><input type="hidden" name="clonenow" value="1" />
        <p>
            <input type="radio" name="target" value="" checked="checked" /> %s 
            <input type="radio" name="target" value="posts" /> %s 
            <input type="radio" name="target" value="taxonomy" /> %s 
            <input type="radio" name="target" value="images" /> %s
            <input type="radio" name="target" value="settings" /> %s
            <input type="radio" name="target" value="custom" /> %s
            <input type="radio" name="target" value="prompts" /> %s
        </p>
        %s
        <p><button class="button button-primary">%s</button></p>',
        esc_html__('All','quick-playground'),
        esc_html__('Posts','quick-playground'),
        esc_html__('Taxonomy and Metadata','quick-playground'),
        esc_html__('Images','quick-playground'),
        esc_html__('Settings','quick-playground'),
        esc_html__('Custom','quick-playground'),
        esc_html__('Prompts','quick-playground'),
        wp_kses_post($cache_notice),
        esc_html__('Clone Now','quick-playground'),
    );
    wp_nonce_field('quickplayground','playground',true,true);
    echo '</form>';

    printf(
        '<p>%s<br>%s: <a target="_blank" href="%s">%s</a><br>%s: <a target="_blank" href="%s">%s</a><br>%s: <a target="_blank" href="%s">%s</a></p>',
        esc_html__('API endpoints','quick-playground'),
        esc_html__('Posts','quick-playground'), esc_url($url), esc_html($url),
        esc_html__('Taxonomy and Metadata','quick-playground'), esc_url($taxurl), esc_html($taxurl),
        esc_html__('Images','quick-playground'), esc_url($imgurl), esc_html($imgurl)
    );

    do_action('qckply_clone_notice');
}

/**
 * Syncs post changes from the playground back to the live site.
 *
 * @param array $changes Array of post data to sync.
 * @return string        Output log of the sync process.
 */
function qckply_sync_changes($changes) {
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
function qckply_clone_log() {
    $nonce = wp_create_nonce( 'qckply_clone_log' );

    printf('<h1>%s</h1><p>', esc_html__('Design Playground Clone Log', 'quick-playground'));
    printf(
        '<a href="%s">%s</a> | ',
        esc_url(admin_url('admin.php?page=qckply_clone_log&posts_json=1&nonce=' . $nonce)),
        esc_html__('Posts JSON', 'quick-playground')
    );
    printf(
        '<a href="%s">%s</a> | ',
        esc_url(admin_url('admin.php?page=qckply_clone_log&tax=1&nonce=' . $nonce)),
        esc_html__('Taxonomy and Metadata', 'quick-playground')
    );
    printf(
        '<a href="%s">%s</a> | ',
        esc_url(admin_url('admin.php?page=qckply_clone_log&images=1&nonce=' . $nonce)),
        esc_html__('Images', 'quick-playground')
    );
    printf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('admin.php?page=qckply_clone_log&custom=1&nonce=' . $nonce)),
        esc_html__('Custom', 'quick-playground')
    );
    echo '</p>';
    if(isset($_GET['posts_json']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'qckply_clone_log')) {
        echo '<h2>'.esc_html__('Incoming JSON','quick-playground').'</h2>';
        echo '<pre>'.esc_html(get_option('qckply_clone_posts_json')).'</pre>';
        echo '<h2>'.esc_html__('Incoming JSON modified','quick-playground').'</h2>';
        echo '<pre>'.esc_html(json_encode(json_decode(get_option('qckply_clone_posts_modified')),JSON_PRETTY_PRINT)).'</pre>';
    } 
    elseif(isset($_GET['tax']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'qckply_clone_log')) {
        echo '<h2>'.esc_html__('Metadata Copy','quick-playground').'</h2>';
        echo wp_kses_post(get_option('qckply_clone_tax_log'));
        echo '<h2>'.esc_html__('JSON','quick-playground').'</h2>';
        echo '<pre>'.esc_html(json_encode(json_decode(get_option('qckply_clone_tax_json')), JSON_PRETTY_PRINT)).'</pre>';
    }
    elseif(isset($_GET['images']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'qckply_clone_log')) {
        echo '<h2>'.esc_html__('Images','quick-playground').'</h2>';
        echo wp_kses_post(get_option('qckply_clone_images_log'));
        echo '<h2>'.esc_html__('JSON','quick-playground').'</h2>';
        echo '<pre>'.esc_html(json_encode(json_decode(get_option('qckply_clone_images_json')),JSON_PRETTY_PRINT)).'</pre>';
    }
    elseif(isset($_GET['custom']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'qckply_clone_log')) {
        echo '<h2>'.esc_html__('Custom','quick-playground').'</h2>';
        echo wp_kses_post(get_option('qckply_clone_custom_log'));
        echo '<h2>'.esc_html__('JSON','quick-playground').'</h2>';
        echo '<pre>'.esc_html(json_encode(json_decode(get_option('qckply_clone_custom_json')),JSON_PRETTY_PRINT)).'</pre>';
    }
    else {
        echo wp_kses_post(get_option('qckply_clone_posts_log'));
    }
}