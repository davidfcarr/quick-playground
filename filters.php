<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('wp_footer','qckply_clone_footer_message');
add_action('admin_footer','qckply_clone_footer_message');
function qckply_clone_footer_message() {
    //cannot be checked by nonce. This is relayed from the live server to the playground environment
    if(isset($_GET['qckply_clone'])) {
        echo wp_kses(qckply_footer_prompt(),qckply_kses_allowed());
        return;
    }
    if(!get_option('is_qckply_clone')) {
        return;
    }
    $url_nopath = str_replace(trim(qckply_playground_path()),'',$_SERVER['REQUEST_URI']);
    $slug = trim(preg_replace('/[^A-Za-z0-9]/','-',$url_nopath),'-');
    if(is_home() || is_front_page())
        $slug = 'home';
    //elseif(('index.php' == $slug) || ('index.php' == $slug))
        //$slug = 'dashboard';
    $keymessage = array('key'=>$slug,'message'=>'','welcome'=> is_admin() ? 'admin-welcome' : 'welcome');
    $keymessage = apply_filters('qckply_key_message',$keymessage);
    if(!empty($keymessage['message'])) {
    ?>
<div id="playground-overlay-message">
  <span><p><strong>Playground Prompt</strong></p>
    <?php
        echo wpautop(wp_kses_post($keymessage['message']));
  ?>
  </span>
  <button id="playground-overlay-close">&times;</button>
</div>
<?php
    }
}

function qckply_footer_prompt() {
    //cannot be checked by nonce. This is relayed from the live server to the playground environment
    if(qckply_is_playground() && isset($_GET['qckply_clone'])) {
        $target = $_GET['qckply_clone'];
        $output = '';
        ob_start();
        if('images' == $target) {
            $more = qckply_clone_images('images');
            $output = ob_get_clean();
            if($more) {
                return '<div id="playground-overlay-message"><p>Loading '.esc_html($more).' more images ...</p></div><script>window.location.href="'.qckply_link(['qckply_clone'=>'thumbnails']).'"</script>';
            }
            else {
                $output = ob_get_clean();
                qckply_top_ids();
                do_action('qckply_loading');
                return '<div id="playground-overlay-message"><p>Done</p></div><script>window.location.href="'.qckply_link().'"</script>';
            }
        } 
        elseif('thumbnails' == $target) {
            $more = qckply_get_more_thumbnails();
            if($more) {
                return '<div id="playground-overlay-message"><p>Loading '.esc_html($more).' more images ...</p></div><script>window.location.href="'.qckply_link(['qckply_clone'=>'thumbnails']).'"</script>';
            }
            $output = ob_get_clean();
            qckply_top_ids();
            do_action('qckply_loading');
            return '<div id="playground-overlay-message"><p>Done</p></div><script>window.location.href="'.qckply_link().'"</script>';
        }
        else {
            qckply_clone( 'settings' );
            qckply_clone( 'taxonomy' );
            qckply_clone( 'custom' );
            qckply_clone( 'prompts' );
            $output = ob_get_clean();
            return '<div id="playground-overlay-message"><p>Loading images ...</p></div><script>window.location.href="'.qckply_link($args = ['qckply_clone'=>'images']).'"</script>';
        }
        //update_option('qckply_sync_date',date('Y-m-d H:i:s'));
    }
    //return $content;
}

add_filter('the_content','qckply_clone_content');
function qckply_clone_content($content) {
    if(qckply_is_playground() && isset($_GET['qckply_clone'])) {
        return '<div style="padding: 20px; background-color: #eee; border-radius: 25px; color: #111;"><h2>Loading ...</h2><p>Just a moment.</p></div>';
    }
    return $content;
}