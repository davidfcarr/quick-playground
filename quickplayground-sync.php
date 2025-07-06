<?php

/**
 * Handles syncing changes from the playground back to the live site.
 *
 * Displays a preview of proposed changes and, upon approval, applies changes to posts, meta, terms, taxonomies, and relationships.
 */
function quickplayground_sync() {
    global $wpdb;
    $changes = get_transient('changes_from_playground');
    print_r($_REQUEST);
    if(!empty($_POST) && !wp_verify_nonce( $_POST['playground'], 'quickplayground' ) ) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }
    if($changes) {
        $status = 'Preview';
        if(isset($_POST['approve'])) {
            printf('<h2>%s</h2>',esc_html__('Processing Changes','quick-playground'));
            $status = 'Doing';
        }
        else {
            printf('<h2>%s</h2>',esc_html__('Proposed Changes','quick-playground'));
            printf('<form method="post" class="playground-form"  action="%s" ><input type="hidden" name="approve" value="1"><div><button>Approve Changes</button></div>%s %s</form>',esc_attr(admin_url('admin.php?page=quickplayground_sync'),wp_nonce_field('quickplayground','playground',true,false),wp_nonce_field('quickplayground','playground',true,false)));
        }      
        if(isset($changes['switch_theme'])) {
            printf('<p>%s: Switch theme to <strong>%s</strong></p>',esc_attr($status),esc_html($changes['switch_theme']));
            if(isset($_POST['approve']))
                switch_theme($changes['switch_theme']);
        }
        if(isset($changes['posts'])) {
            foreach($changes['posts'] as $i  => $p) {
                $meta = (empty($changes['meta'][$i])) ? [] : $changes['meta'][$i];
                $post_id = $p['ID'];
                $exists = get_post($post_id);
                if($exists) {
                    if(isset($_POST['approve'])) {
                        $result = wp_update_post($p);
                        printf('<p>Update result for %s %s %s</p>',intval($p['ID']),esc_html($p['post_title']),esc_html(var_export($result,true)));
                    }
                    printf('<p>%s: Update %s <strong>%s</strong></p>',esc_html($status),esc_html($p['post_type']),esc_html($p['post_title']));
                }
                else {
                    printf('<p>%s: Add %s <strong>%s</strong></p>',esc_html($status),esc_html($p['post_type']),esc_html($p['post_title']));
                    if(isset($_POST['approve'])) {
                        unset($p['ID']);
                        $post_id = wp_insert_post($p);
                        printf('<p>Insert result %s %s</p>',esc_html(var_export($post_id,true)),esc_html($p['post_title']));
                    }
                }
                printf('<p><a href="%s">Edit</a></p>',esc_attr(get_edit_post_link($post_id,false)));
                foreach($meta as $key => $values)
                {
                    foreach($values as $value) {
                        echo "<p>".esc_html($status).": update_post_meta($post_id,$key,$value); </p>";
                        if(isset($_POST['approve']))    
                            update_post_meta($post_id,$key,$value);
                    }
                }
                if($p['post_type'] == 'rsvpmaker') {
                    $event = $changes['rsvpmakers'][$i];
                    if(isset($_POST['approve']))
                        $wpdb->replace(get_rsvpmaker_event_table(),$event);
                }
            }
            $changesoutput = '';

            if(!empty($changes['termmmeta']) && isset($_POST['approve'])) {
                foreach($changes['termmeta'] as $meta) {
                    $result = $wpdb->replace($wpdb->termmeta,$meta);
                    if(!$result) {
                        $changesoutput .= '<p>Error: termmeta '.esc_html($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['terms']) && isset($_POST['approve'])) {
                foreach($changes['terms'] as $row) {
                    $result = $wpdb->replace($wpdb->terms,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: terms '.esc_html($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['term_relationships']) && isset($_POST['approve'])) {
                $changesoutput .= sprintf('<p>%d term_relationships',sizeof($changes['term_relationships']));
                foreach($changes['term_relationships'] as $row) {
                    $result = $wpdb->replace($wpdb->term_relationships,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: term_relationships '.esc_html($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['term_taxonomy']) && isset($_POST['approve'])) {
                foreach($changes['term_taxonomy'] as $row) {
                    $result = $wpdb->replace($wpdb->term_taxonomy,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: term_taxonomy '.esc_html($wpdb->last_error).'</p>';
                    }
                }
            }
            if(isset($_POST['approve']))
                echo $changesoutput;
        }

        if(empty($_POST))
            printf('<pre>%s</pre>',esc_html(var_export($changes,true)));

    }//end if changes

}