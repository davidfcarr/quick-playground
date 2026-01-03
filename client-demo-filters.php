<?php

function qckply_upload_resized_image($choice) {
    $temp = wp_upload_dir();
    $file_path = $temp['path'].'/'.$choice['choice'];
    $profile = get_option("qckply_profile");
    $qckply_sync_code = qckply_get_sync_code();
    $sync_origin = get_option('qckply_sync_origin');
    $remote_url = $sync_origin.'/wp-json/quickplayground/v1/upload_image/'.$profile.'?t='.time();
    update_option('qckply_upload_image_path',$file_path);
    update_option('qckply_upload_image_url',$remote_url);
    $data = $choice;
    $data['sync_code'] = $qckply_sync_code;
    $data['base64'] = base64_encode(file_get_contents($file_path));
    update_option('qckply_upload_image_path_64',substr($data['base64'],0,10));
    $data['filename'] = $choice['basename'];
    $request = array(
    'body'    => json_encode($data),
    'headers' => array(
        'Content-Type' => 'application/json',
    ));
    $response = wp_remote_post( $remote_url, $request);
    if ( ! is_wp_error( $response ) ) {
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    update_option('qckply_upload_image_resized_result',var_export($body,true));
    add_post_meta($choice['playground_attachment_id'],'qckply_origin_sideload_metadata',$body['sideload_meta']);
    }
    else {
        update_option('qckply_upload_image_resized_error',var_export($result,true));
    }    
}

$qckply_just_uploaded = '';
//add_action('updated_post_meta', 'qckply_wp_attachment_metadata',10,4);
function qckply_wp_attachment_metadata($mid, $attachment_id, $meta_key, $meta_value) {
    global $qckply_just_uploaded;
    $limit = 750000;
    if('_wp_attachment_metadata' == $meta_key && !empty($meta_value['sizes'])) {
        if(get_option('qckply_disable_image_upload',false))
            return; //
        if($qckply_just_uploaded == $meta_value['file'])
            return;
        if(!get_option('qckply_top_ids',false))
            return; //set at end of import process
        if(($meta_value['filesize'] > $limit) && (1 != qckply_extra_large_images($meta_value['sizes']) ))
            return; // if it's a large image, capture 1 extra large image
        $guid = get_the_guid($attachment_id);
        $choice = qckply_image_to_upload($meta_value,$guid);
        $choice['playground_attachment_id'] = $attachment_id;
        qckply_upload_resized_image($choice);
        $qckply_just_uploaded = $meta_value['file'];
        set_transient('qckply_image_to_upload',json_encode($choice));
    }
}

function qckply_extra_large_image($sizes) {
    $count = 0;
    foreach($sizes as $key => $data) {
        if(preg_match('/[0-9]/',$key))
            $count++;
    }
    return $count;
}

$qckply_existing_uploads = [];

function qckply_image_to_upload($image_meta, $guid) {
global $qckply_existing_uploads;

//use global if not empty to avoid issues with database write delay
if(empty($qckply_existing_uploads))
    $qckply_existing_uploads = get_option('qckply_image_uploads',[]);

$limit = 750000;
$pathinfo = pathinfo($image_meta['file']);
$basename = basename($guid);
if(in_array($basname,$qckply_existing_uploads))
    return;
$choice = '';
set_transient('qckply_image_to_upload resize meta sizes '.time(),var_export($image_meta['sizes'],true));
if($image_meta['filesize'] > $limit) {
    if(empty($image_meta['sizes']['medium_large']))
        return; // make sure we have more than just a thumbnail of the large image
    $sizes = qckply_image_largest_smallest($image_meta['sizes']);
    set_transient('qckply_image_to_upload resize check',var_export($sizes,true));
    foreach($sizes as $index => $size) {
        if($size['filesize'] < $limit) {
            $choice = basename($size['file']);
            set_transient('qckply_image_to_upload resized',$choice);
            break;
        }
    }
}
else {
    $choice = basename($image_meta['file']);//may be "-scaled" version
    set_transient('qckply_image_to_upload full size or scaled',$choice);
}
$qckply_existing_uploads[] = $basename;
update_option('qckply_image_uploads',$qckply_existing_uploads);
$return_array = ['basename'=>$basename,'path'=>$pathinfo['dirname'],'choice'=>$choice];
return $return_array;
}

