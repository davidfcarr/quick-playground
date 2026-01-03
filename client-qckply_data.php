<?php

function qckply_data() {
    echo '<h2>Quick Playground Data</h2>';
    if(isset($_GET['att'])) {
        print_r(wp_get_attachment_metadata(intval($_GET['att'])));
    }
    if(isset($_POST['filename'])) {
    $imgcontent = file_get_contents($_POST['filename']);
    $base64 = base64_encode($imgcontent);
    printf('<p>%s</p>',$base64);
    }
    printf('<form method="post" action="%s"><input name="filename" value=""><button>Submit</button></form>',admin_url('admin.php?page=qckply_data'));
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

//add_action('footer','qckply_sync_ids');
//add_action('admin_footer','qckply_sync_ids');
add_action('shutdown','qckply_sync_ids');
function qckply_sync_ids() {
    //if(!is_admin())
        //return;
    $true_top = qckply_top_ids(true);
    $starter_top = qckply_top_ids();
    $diff = array_diff_assoc($true_top,$starter_top);
    //if(empty($diff))
        //return; //nothing to sync
    $qckply_sync_code = get_option('qckply_sync_code');
    $sync_origin = get_option('qckply_sync_origin');
    $remote_url = $sync_origin.'/wp-json/quickplayground/v1/sync_ids?t='.time();
    $updata['sync_code'] = $qckply_sync_code;
    $updata['top_ids'] = $true_top;
    $request = array(
    'body'    => json_encode($updata),
    'headers' => array(
        'Content-Type' => 'application/json',
    ));
    $response = wp_remote_post( $remote_url, $request);
}
