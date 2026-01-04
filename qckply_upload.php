<?php

function qckply_json_upload() {
	?>
<h1>Upload Playground Sync File</h1>
<?php
    if(!empty($_POST) && (empty( $_REQUEST['playground']) || !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['playground'])), 'quickplayground' ) )) 
    {
        echo '<h2>'.esc_html__('Security Error','quick-playground').'</h2>';
        return;
    }  

	if ( ! empty( $_POST ))  {
        $directories = qckply_get_directories();
        $file = $_FILES["json_upload"]['name'];
        $ext = array_pop(explode('.',$file));
        if('json' != $ext)
        {
            echo '<h2>'.esc_html($file).' '.esc_html__('Not a Json file','quick-playground').'</h2>';
        }
        else {
        $target_file = trailingslashit($directories['site_uploads']).$file;
        if(move_uploaded_file($_FILES["json_upload"]["tmp_name"], $target_file))
            printf('<div class="notice notice-success"><p>File saved to %s</p></div>',$target_file);
        else
            printf('<div class="notice notice-error"><p>Error trying to save to %s</p></div>',$target_file);
        }
	}
?>
<p>Normally, the Sync with [your domain] screen within the playground should allow you to save your work interactively, via APIs -- but if that doesn't work, this is a backup.</p>
<p>If the save function fails, you will get a prompt to "download instead" and can upload the Json file using the form below.</p>
<form action="<?php echo admin_url( 'admin.php?page=qckply_json_upload' ); ?>" method="post" enctype="multipart/form-data">
<p>File: <input type="file" name="json_upload" id="json_upload"></p>
<p><input type="submit" value="Submit" name="submit"></p>
<?php wp_nonce_field('quickplayground','playground',true,true); ?>
</form>
	<?php
}
