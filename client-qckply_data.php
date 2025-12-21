<?php

function qckply_data() {
    echo '<h2>Quick Playground Data</h2>';
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE option_name LIKE %s OR option_name LIKE %s ORDER BY option_name",$wpdb->options,'%qckply%','%playground%'));
    foreach($results as $row) {
        printf('<h1>Option: %s</h1><pre>%s</pre>',esc_html($row->option_name),wp_kses_post(htmlentities($row->option_value)));
    }    
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE meta_key LIKE %s OR meta_key LIKE %s ORDER BY meta_key",$wpdb->postmeta,'%qckply%','%playground%'));
    foreach($results as $row) {
        printf('<h1>Post %d Meta: %s</h1><pre>%s</pre>',intval($row->post_id),esc_html($row->meta_key),wp_kses_post(htmlentities($row->meta_value)));
    }
}