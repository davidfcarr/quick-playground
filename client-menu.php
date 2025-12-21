<?php

add_action('admin_menu', 'qckply_clone_menus',50);
function qckply_clone_menus() {
    if(get_option('qckply_sync_code')) {
    add_submenu_page('quickplayground','Save Playground', 'Save Playground', 'manage_options', 'qckply_save', 'qckply_save');
    add_submenu_page('quickplayground','Save Images', 'Save Images', 'manage_options', 'qckply_upload_images', 'qckply_upload_images');
    add_submenu_page('quickplayground','Edit Playground Prompts', 'Edit Playground Prompts', 'manage_options', 'qckply_clone_prompts', 'qckply_clone_prompts');
    }
    add_submenu_page('quickplayground','Quick Data', 'Quick Data', 'manage_options', 'qckply_data', 'qckply_data');
}
