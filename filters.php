<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('wp_footer','qckply_clone_footer_message');
add_action('admin_footer','qckply_clone_footer_message');
function qckply_clone_footer_message() {
    if(!get_option('is_qckply_clone')) {
        return;
    }
    $url_nopath = str_replace(trim(qckply_playground_path()),'',sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
    $slug = trim(preg_replace('/[^A-Za-z0-9]/','-',$url_nopath),'-');
    if(is_home() || is_front_page())
        $slug = 'home';
    $keymessage = array('key'=>$slug,'message'=>'','welcome'=> is_admin() ? 'admin-welcome' : 'welcome');
    $keymessage = apply_filters('qckply_key_message',$keymessage);
    if(!empty($keymessage['message'])) {
    ?>
<div id="playground-overlay-message">
  <span><p><strong>Playground Prompt</strong></p>
    <?php
        echo wp_kses_post(wpautop($keymessage['message']));
  ?>
  </span>
  <button id="playground-overlay-close">&times;</button>
</div>
<?php
    }
}

