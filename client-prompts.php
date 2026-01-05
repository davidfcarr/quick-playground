<?php

function qckply_clone_prompts() {
    global $menu, $submenu;
    echo '<h1>Edit Playground Prompts</h1>';
    echo '<p>You can add prompts to be displayed with the Playground first loads or on specific pages. Optionally, you can have a prompt to add or edit content displayed on every page.</p>';
    if(!empty($_POST) ) 
    {
        if(!wp_verify_nonce( $_POST['playground'], 'quickplayground' )) {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
        }
        update_option('show_playground_prompt_keys',intval($_POST['show']));
        set_transient('qckply_welcome_shown',false);

        $messages = [];
        $postvars = $_POST;
        foreach($postvars['qckply_messages'] as $key => $value) {
            if(!empty(trim($value)))
                $messages[$key] = wp_kses_post(stripslashes($value));
        }
        if(!empty($postvars['custom_message_page']) && !empty($postvars['custom_message'])) {
            $page = trim(sanitize_text_field($postvars['custom_message_page']));
            $content = wp_kses_post(stripslashes($postvars['custom_message']));
            printf('<p>Setting %s to %s</p>',esc_html($page),wp_kses_post($content));
            $messages[$page] = $content;
        }
        update_option('qckply_messages',$messages);
        set_transient('qckply_messages_updated', true, DAY_IN_SECONDS);
    }
    else
        $messages = qckply_get_prompt_messages();

    if(get_transient('qckply_messages_updated'))
        printf('<div class="notice notice-success"><p>%s - <a href="%s">%s</a></p></div>',esc_html__('Prompts updates','quick-playground'),admin_url('admin.php?page=qckply_save'),esc_html__('Save for future sessions','quick-playground'));

    $show = get_option('show_playground_prompt_keys',false);
    printf('<form  class="playground-form"  method="post" action="">',esc_attr($_SERVER['REQUEST_URI']));
    wp_nonce_field('quickplayground','playground',true,true);
    foreach($messages as $key => $value) {
    if('sync_prompts' == $key || 'new_ids' == $key)
        continue;
    printf('<p>Message: %s <br /><textarea name="qckply_messages[%s]"  cols="100" rows="3">%s</textarea></p>',esc_html($key),esc_attr($key),esc_html($value));
    if('welcome' == $key)
    echo '<p><em>Displayed when the Playground first loads.</em></p>';
    }
    $key = '';
    $targets = qckply_post_prompt_keys();
    $menu_keys = qckply_admin_menu_prompt_keys();
    $targets = array_merge($targets,$menu_keys);
    $key_options = '';
    if(isset($_GET['key'])) $key = sanitize_text_field($_GET['key']);
    if(!empty($key)) {
    if(!empty($targets[$key])) {
        $key_options .= sprintf('<option value="%s">%s</option>',$key,esc_html(strip_tags($targets[$key])));
    }
    else {
        $key_options .= sprintf('<option value="%s">%s</option>',$key,esc_html($key));
    }
 
    }
    foreach($targets as $target => $label) {
        if($target == $key)
            continue;
        $key_options .= sprintf('<option value="%s">%s</option>',esc_attr($target),esc_html(strip_tags($label)));
    }
    echo '<p>Custom Message target: <select name="custom_message_page">'.$key_options.'</select><br /><em>Front End Examples: "home" for the home page, "special-offers" (for mysite.com/special-offers/) or post_type:rsvpmaker (any example of the specified post type)<br >Admin Examples: "dashboard" (/wp-admin/) or "qckply_clone_page" (mysite.com/wp-admin/admin.php?page=qckply_clone_page)</em><br /><textarea cols="100" rows="3" name="custom_message"></textarea></p>';
    printf('<p><input type="radio" name="show" value="1" %s /> Show ',$show ? ' checked="checked" ': '');
    printf('<input type="radio" name="show" value="0" %s /> Do NOT Show prompt targets as I browse</p>',!$show ? ' checked="checked" ': '');
    echo "<p><button>Update</button></p></form>";

}

function qckply_post_prompt_keys() {
    global $wpdb;
    $site_url = site_url();
    $keys = [];
    $page_on_front = get_option('page_on_front');
    if($page_on_front) {
        $frontpage = get_post($page_on_front);
        $target = 'home';
        $keys[$target] = 'FRONTPAGE: '.substr($frontpage->post_title,0,80);
    }
    $posts = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'");
    $pages = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND ID != ".intval($page_on_front));
    $other = $wpdb->get_results("SELECT ID, post_type, post_title FROM {$wpdb->posts} WHERE post_type NOT IN ('post','page') AND post_status = 'publish'");
    foreach($pages as $page) {
        $target = trim(preg_replace( '/[^A-Za-z0-9]/', '-', str_replace($site_url,'',get_permalink($page->ID))),'-');
        if(empty($target))
            $target = 'home';
        $keys[$target] = 'PAGE: '.substr($page->post_title,0,80);
    }
    foreach($posts as $post) {
        $target = trim(preg_replace( '/[^A-Za-z0-9]/', '-', str_replace($site_url,'',get_permalink($post->ID))),'-');
        $keys[$target] = 'POST: '.substr($post->post_title,0,80);
    }
    foreach($other as $item) {
        $target = trim(preg_replace( '/[^A-Za-z0-9]/', '-', str_replace($site_url,'',get_permalink($item->ID))),'-');
        $keys[$target] = strtoupper($item->post_type).': '.substr($item->post_title,0,80);
    }
    return $keys;
}

function qckply_admin_menu_prompt_keys() {
    global $menu, $submenu;
    $admin_menu_keys = array();

    // Process top-level menus
    foreach ( $menu as $menu_item ) {
        // $menu_item structure: 0: Name, 1: Capability, 2: Menu Slug/File, ...
        $name = $menu_item[0];
        $slug = $menu_item[2];
        $target = 'wp-admin-' . preg_replace( '/[^A-Za-z0-9]/', '-', $slug );
        $admin_menu_keys[ $target ] = 'Menu: '. $name;
    }

    // Process submenus
    foreach ( $submenu as $parent_slug => $submenu_items ) {
        foreach ( $submenu_items as $submenu_item ) {
            // $submenu_item structure: 0: Name, 1: Capability, 2: Menu Slug/File, ...
            $name = $submenu_item[0];
            $slug = $submenu_item[2];

            // For submenus, the URL needs to be constructed carefully
            if ( false !== strpos( $slug, '.php' ) ) {
                // If the slug contains .php, it's an existing admin file (e.g., edit.php)
                $target = 'wp-admin-' . preg_replace( '/[^A-Za-z0-9]/', '-', $slug );
            } else {
                // Otherwise, it's likely a custom page registered with a 'page' query argument
                $target = 'wp-admin-admin-php-page-' . preg_replace( '/[^A-Za-z0-9]/', '-', $slug );
            }
            
            if('index.php' == $parent_slug)
                $parent_slug = 'dashboard';
            // Add to the list, potentially with parent context if needed
            $admin_menu_keys[ $target ] = $name . ' (submenu of ' . $parent_slug . ')';
        }
    }

    return $admin_menu_keys;
}