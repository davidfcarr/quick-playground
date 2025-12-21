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
<div id="qckply-overlay-message">
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

add_filter('qckply_key_message','qckply_key_message');

function qckply_key_message($keymessage) {
    $landing = trim(preg_replace('/[^A-Za-z0-9]/','-',get_option('qckply_landing')),'-');
    $show = get_option('show_playground_prompt_keys');
    extract($keymessage);//key, message, welcome
    $messages = get_transient('qckply_messages');
    $welcome_shown = intval(get_transient('qckply_welcome_shown'));
    $type = get_post_type();
    if(isset($messages[$key])) {
        if(!empty($messages[$key])) {
        $keymessage['message'] .= $messages[$key];
        }
    }
    elseif(!$welcome_shown && ($landing == $key) && !empty($messages[$welcome])) {
        $keymessage['message'] .= $messages[$welcome];
        $key = $welcome;
        set_transient('qckply_welcome_shown',true,DAY_IN_SECONDS);
    }
    elseif(isset($messages['post_type:'.$type])) {
        if(!empty($messages['post_type:'.$type]))
        {
        $keymessage['message'] .= $messages['post_type:'.$type];
        }
    }
    //if(empty($keymessage['message'] ))
        //$keymessage['message'] = sprintf('No match for %s welcome %d %s',$key,$welcome_shown,var_export($messages,true)); 
    if($show) {
        $url = admin_url('admin.php?page=qckply_clone_prompts&key='.$key);
        $keymessage['message'] .= "\n\n".sprintf('Edit message for <a href="%s">%s</a>',$url,$key);
        if($key != $keymessage['key']) {
        $url = admin_url('admin.php?page=qckply_clone_prompts&key='.$keymessage['key']);
        $keymessage['message'] .= sprintf(' or <a href="%s">%s</a>',$url,$keymessage['key']);
        }
    }
    return $keymessage;
}