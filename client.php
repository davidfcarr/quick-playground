<?php
add_action ('qckply_clone_pro_form','qckply_clone_pro_form');
function qckply_clone_pro_form() {
    printf('<p><a href="%s">Save Posts</a></p>',esc_attr(admin_url('admin.php?page=qckply_save')));
}
