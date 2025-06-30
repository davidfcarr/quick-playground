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
        echo '<h2>Security Error</h2>';
        return;
    }
    if($changes) {
        $status = 'Preview';
        if(isset($_POST['approve'])) {
            printf('<h2>%s</h2>',__('Processing Changes','design-plugin-playground'));
            $status = 'Doing';
        }
        else {
            printf('<h2>%s</h2>',__('Proposed Changes','design-plugin-playground'));
            printf('<form method="post" action="%s" ><input type="hidden" name="approve" value="1"><div><button>Approve Changes</button></div>%s %s</form>',admin_url('admin.php?page=quickplayground_sync'),wp_nonce_field('quickplayground','playground',true,false),wp_nonce_field('quickplayground','playground',true,false));
        }      
        if(isset($changes['switch_theme'])) {
            printf('<p>%s: Switch theme to <strong>%s</strong></p>',$status,$changes['switch_theme']);
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
                        printf('<p>Update result for %s %s %s</p>',$p['ID'],$p['post_title'],var_export($result,true));
                    }
                    printf('<p>%s: Update %s <strong>%s</strong></p>',$status,$p['post_type'],$p['post_title']);
                }
                else {
                    printf('<p>%s: Add %s <strong>%s</strong></p>',$status,$p['post_type'],$p['post_title']);
                    if(isset($_POST['approve'])) {
                        unset($p['ID']);
                        $post_id = wp_insert_post($p);
                        printf('<p>Insert result %s %s</p>',var_export($post_id,true),$p['post_title']);
                    }
                }
                printf('<p><a href="%s">Edit</a></p>',get_edit_post_link($post_id,false));
                foreach($meta as $key => $values)
                {
                    foreach($values as $value) {
                        echo "<p>".$status.": update_post_meta($post_id,$key,$value); </p>";
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
                        $changesoutput .= '<p>Error: termmeta '.htmlentities($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['terms']) && isset($_POST['approve'])) {
                foreach($changes['terms'] as $row) {
                    $result = $wpdb->replace($wpdb->terms,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: terms '.htmlentities($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['term_relationships']) && isset($_POST['approve'])) {
                $changesoutput .= sprintf('<p>%d term_relationships',sizeof($changes['term_relationships']));
                foreach($changes['term_relationships'] as $row) {
                    $result = $wpdb->replace($wpdb->term_relationships,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: term_relationships '.htmlentities($wpdb->last_error).'</p>';
                    }
                }
            }

            if(!empty($changes['term_taxonomy']) && isset($_POST['approve'])) {
                foreach($changes['term_taxonomy'] as $row) {
                    $result = $wpdb->replace($wpdb->term_taxonomy,$row);
                    $changesoutput .= "<p>$wpdb->last_query</p>";
                    if(!$result) {
                        $changesoutput .= '<p>Error: term_taxonomy '.htmlentities($wpdb->last_error).'</p>';
                    }
                }
            }
            if(isset($_POST['approve']))
                echo $changesoutput;
        }

        if(empty($_POST))
            printf('<pre>%s</pre>',htmlentities(var_export($changes,true)));

    }//end if changes

}