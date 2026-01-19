<?php

//add_filter('wp_get_attachment_metadata','qckply_attachment_to_upload',10,2);
//qckply_attachment_to_upload($data,$attachment_id) {
//add_action('add_attachment','qckply_attachment_to_upload');
function qckply_attachment_to_upload($attachment_id) {
    global $wpdb;
    $attpost = get_post($attachment_id);
    if(!$attpost)
        return;
    $data = $meta = wp_get_attachment_metadata($attachment_id); // returns array or false
    update_option('qckply_upload_attachment_id',$attachment_id);
    update_option('qckply_upload_metadata',$data);
    $temp = wp_upload_dir();
    $uploads = trailingslashit($temp['basedir']);
    $path = trailingslashit($temp['path']);
    //sort largest to smallest by width
    $filename = $uploads.$data['file'];
    update_option('qckply_upload_image_path',$filename);
    $basename = basename(str_replace('-scaled','',$filename));
    update_option('qckply_upload_basename',$basename);
    $filesize = filesize($filename);
    $size_limit = 500000;
    printf('<p>%s size %d</p>',$filename,$filesize);
    if($filesize > $size_limit) {
        echo '<p>Checking for a version < '.$size_limit.' bytes</p>';
        update_option('qckply_upload_image_usort_start',$data['sizes']);
        usort($data['sizes'], function($a, $b) {
        if($a['width'] == $b['width']) return 0;
        return $a['width'] < $b['width'] ? 1 : -1; });
        update_option('qckply_upload_image_usort_done',$data['sizes']);
        foreach($data['sizes'] as $img) {
            update_option('qckply_upload_image_path_size_array',$img);
            $filename = $path.$img['file'];
            $filesize = filesize($filename);
            printf('<p>%s size %d</p>',$filename,$filesize);
            update_option('qckply_upload_image_path_size_test',$filename.':'.$filesize);
            if($filesize < $size_limit)
                break;
        }
    }
    update_option('qckply_upload_image_path_scaled',$filename);
    $profile = get_option("qckply_profile");
    $qckply_sync_code = get_option('qckply_sync_code');
    $sync_origin = get_option('qckply_sync_origin');
    $remote_url = $sync_origin.'/wp-json/quickplayground/v1/upload_image/'.$profile.'?t='.time();
    update_option('qckply_upload_image_url',$remote_url);
    $updata['sync_code'] = $qckply_sync_code;
    $imgcontent = file_get_contents($filename);
    $updata['base64'] = base64_encode($imgcontent);
    $updata['top_id'] = $wpdb->get_var($wpdb->prepare('select ID from %i ORDER BY ID DESC',$wpdb->posts));
    $updata['post_parent'] = $attpost->post_parent;
    update_option('qckply_upload_image_base64',$updata['base64']);
    $updata['filename'] = $basename;
    $updata['path'] = $filename;
    echo '<p>Image upload data: ';
    foreach($updata as $key => $value)
    {
        echo ' <strong>'.esc_html($key).'</strong>';
        if('base64' == $key) {
            echo 'length '.strlen($value);
        }
        else
            echo ' '.$value;
    }
    echo '</p>';
    if(empty($updata['base64']))
    {
        echo '<p>Error encoding to base64</p>';
        return;
    }
    $request = array(
    'body'    => json_encode($updata),
    'headers' => array(
        'Content-Type' => 'application/json',
    ));
    $response = wp_remote_post( $remote_url, $request);
    update_option('qckply_upload_image_raw_result',$response);
    if ( ! is_wp_error( $response ) ) {
    $response_data = json_decode( wp_remote_retrieve_body( $response ), true );
    $response_code = wp_remote_retrieve_response_code( $response );
    if(200 == $response_code) {
        printf('<p>Uploading image %s</p>',esc_html($basename));
        update_option('qckply_upload_image_result',var_export($response_data,true));
        $server_attachment_id = $response_data['sideload_meta']['attachment_id'];
        printf('<p>Setting image ID to %s</p>',esc_html($server_attachment_id));
        $wpdb->query($wpdb->prepare("update %i set ID=%d WHERE ID=%d",$wpdb->posts,$server_attachment_id,$attachment_id));
        $wpdb->query($wpdb->prepare("update %i set post_id=%d WHERE post_id=%d",$wpdb->postmeta,$server_attachment_id,$attachment_id));
        $wpdb->query($wpdb->prepare("update %i set meta_value=%d WHERE meta_key='_thumbnail_id' AND meta_value=%d",$wpdb->postmeta,$server_attachment_id,$attachment_id));
        printf('<p>Changing attachment ID from %d to %d (ID on live site)</p>',$attachment_id,$server_attachment_id);
    }
    else
        update_option('qckply_upload_image_result_error',var_export($response_data,true));
    } else {
        $error_message = $response->get_error_message();
        update_option('qckply_upload_image_error_message',$error_message);
        update_option('qckply_upload_image_result',var_export($error_message,true));
        throw new Exception( esc_html($error_message) );
    }
    update_option('qckply_save_image_responsedata',$response_data);
    error_log('responsedata '.var_export($response_data,true));
    return $response_data;
} 

function qckply_upload_images_form() {
global $wpdb;
if(isset($_POST['uploads']) && is_array($_POST['uploads']) && count($_POST['uploads'])) {
    $uploads = $_POST['uploads'];
    $upload_count = 0;
    foreach($uploads as $attachment_id) {
        $result = qckply_attachment_to_upload($attachment_id);
        printf('<p>%s</p>',var_export($result,true));
        if($result)
            $upload_count++;
    }
    printf('<p>Uploaded %d files</p>',esc_html($upload_count));
}   
$qckply_top_ids = qckply_top_ids();
$sql = $wpdb->prepare("SELECT * FROM %i posts JOIN %i meta ON posts.ID=meta.post_id WHERE meta_key='attachment_updated'",$wpdb->posts,$wpdb->postmeta);
printf('<p>%s</p>',$sql);
$images = $wpdb->get_results($sql);
if(!empty($images)) {
    echo '<h2>Save Images</h2>';
    printf('<form method="post" action="%s">',esc_attr(admin_url('admin.php?page=qckply_save')));//https://playground.wordpress.net/scope:confident-quiet-ocean/wp-admin/admin.php?page=qckply_save
    printf('<p>Found %d new images</p>',count($images));
    echo '<h2>Select images to upload:</h2>';
    foreach($images as $attachment) {
    printf('<p><input type="checkbox" name="uploads[]" value="%s" /> Upload: %s <br /><img src="%s" width="300" /></p>',esc_attr($attachment->ID),esc_html(basename($attachment->guid)),esc_attr($attachment->guid));
    }
    echo '<p><input type="submit" value="Upload Selected Images" /></p>';
    wp_nonce_field('quickplayground','playground',true,true);
    echo '</form>';
}

}
