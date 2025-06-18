<?php

        if(empty($nav_id)) {

                $args = array(

                'name'           => 'calendar',

                'post_type'      => 'post',

                'post_status'    => 'publish',

                'posts_per_page' => 1

            );

            $my_posts = get_posts($args);

            if(empty($page_on_front))

                $nav = sprintf('<!-- wp:navigation-link {"label":"%s","type":"page","url":"%s","kind":"post-type"} /-->','Home',site_url())."\n";

            else

                $nav = sprintf('<!-- wp:navigation-link {"label":"%s","type":"page","id":%d,"url":"%s","kind":"post-type"} /-->','Home',$page_on_front,get_permalink($page_on_front))."\n";

            if(!empty($my_posts)) {

                $nav .= sprintf('<!-- wp:navigation-link {"label":"%s","type":"post","id":%d,"url":"%s","kind":"post-type"} /-->','Calendar',$my_posts[0]->ID,get_permalink($my_posts[0]->ID))."\n";

            }

            if(!empty($page_for_posts))

            $nav .= sprintf('<!-- wp:navigation-link {"label":"%s","type":"page","id":%d,"url":"%s","kind":"post-type"} /-->','Blog',$page_for_posts,get_permalink($page_for_posts))."\n";



            $post = array(

                'post_content'   => $nav,

                'post_name'      => 'navigation',

                'post_title'     => 'Navigation',

                'post_status'    => 'publish',

                'post_type'      => 'wp_navigation',

                'ping_status'    => 'closed'

            );

            $nav_id = wp_insert_post($post);

        }


        
            $sql = "SELECT * FROM $wpdb->posts WHERE post_type='wp_template_part'";

            $results = $wpdb->get_results($sql);

            foreach($results as $result) {

                $result->post_content = preg_replace('/"ref":[0-9]+/','"ref":'.$nav_id,$result->post_content);

                $output .= sprintf('<p>Updating template part with new nav id %d</p><pre>%s</pre>',$nav_id,$result->post_content);

                $wpdb->update($wpdb->posts,array('post_content' => $result->post_content),array('ID' => $result->ID));

            }