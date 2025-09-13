<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_shortcode('qckply_iframe_shortcode', 'qckply_iframe_shortcode');
function qckply_iframe_shortcode($args, $echo = false) {
    $url = site_url('qpi');
    $height = (empty($args['height'])) ? '1000px' : sanitize_text_field(($args['height']));
    $width = (empty($args['width'])) ? '100%' : sanitize_text_field(($args['width']));
    foreach($args as $key => $value) {
        if('profile' == $key)
            $key = 'quick_playground';
        if('height' != $key && 'width' != $key)
            $url .= (strpos($url, '?') === false ? '?' : '&') . urlencode(sanitize_text_field($key)) . '=' . urlencode(sanitize_text_field($value));
    }
    ob_start();
    echo '<div style="width: '.esc_attr($width).'; height: '.esc_attr($height).';"><iframe src="'.esc_url($url).'" height="100%" width="100%"></iframe></div>';
    if($echo)
        ob_flush();
    return ob_get_clean();
}

function qckply_sidebar_default() {
    return '<!-- wp:heading -->
<h2 class="wp-block-heading">Quick Playground</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>This is a sandbox WordPress environment for testing, education, and demos, created using WordPress Playground and the <a href="https://quickplayground.com" target="_blank" rel="noopener">Quick Playground plugin</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>It takes a moment to load, but when it does you will become the administrator of this virtual website.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>For more information on what Quick Playground can do, visit <a href="https://quickplayground.com" target="_blank" rel="noopener">quickplayground.com</a>.</p>
<!-- /wp:paragraph -->';
}

add_action('init', 'qckply_iframe');
function qckply_iframe() {
    global $post;
    $input = '';
    //get variable not checked by nonce, can be referenced from a static link
    if (is_admin() || !isset($_GET['quick_playground']) || !isset($_GET['domain'])) {
        return;
    }
        $sidebar = '';
        $src = get_option('use_playground', 'https://playground.wordpress.net');
        $blueprint_domain = sanitize_text_field(wp_unslash($_GET['domain']));
        $blueprint_profile =  sanitize_text_field(wp_unslash($_GET['quick_playground']));
        //as noted in readme.txt, users may configure Quick Playground to display content from other websites that also run Quick Playground
        $blueprint_url = 'https://'.$blueprint_domain.'/wp-json/quickplayground/v1/blueprint/'.$blueprint_profile.'?t='.time();
        $display = get_option('qckply_display_'.$blueprint_profile,[]);
        $title = empty($display['iframe_title']) ? 'Quick Playground' : sanitize_text_field($display['iframe_title']);
        $sidebar_id = isset($_GET['sidebar']) ? intval($_GET['sidebar']) : 0;
        foreach($_GET as $key => $value) {
            if(('domain' != $key) && ('quick_playground' != $key) )
            $blueprint_url .= (strpos($blueprint_url, '?') === false ? '?' : '&') . urlencode(sanitize_text_field($key)) . '=' . urlencode(sanitize_text_field($value));
        }
        $sidebar = '';
        if(!$sidebar_id)
        {
            $sidebar = qckply_sidebar_default();
        }
        if(isset($_GET['no_sidebar']) || (isset($display['iframe']) && 'no_sidebar' == $display['iframe'])) {
            $sidebar = $false;
        }
        elseif($sidebar_id) {
            $post = get_post($sidebar_id);
            $sidebar = do_blocks($post->post_content);
            if(current_user_can('edit_post',$sidebar_id))
                $sidebar .= sprintf('<p><a target="_blank" href="%s">Edit</a></p>',admin_url('post.php?action=edit&post='.$sidebar_id));
        }

        $src .= '/?blueprint-url='.urlencode($blueprint_url).'&now='.time();
        $social_img = qckply_get_social_image($sidebar_id);
        $excerpt = $sidebar_id ? get_the_excerpt($sidebar_id) : '';
        $description = !empty($excerpt) ? $excerpt : 'This is a sandbox WordPress environment for testing, education, and demos, created using WordPress Playground and the Quick Playground plugin.';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo esc_html($title); ?> (Quick Playground)</title>
    <?php 
    wp_enqueue_style( 'qckply_style', plugin_dir_url( __FILE__ ) . 'quickplayground.css', array(), '1.2' );
    @wp_print_styles(); ?>
<!-- OG Meta Tags -->
<meta property="og:title" content="<?php echo esc_attr($title); ?>" />
<meta property="og:description" content="<?php echo esc_attr($description); ?>" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?php echo esc_url( site_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))) ); ?>" />
<?php
printf('<meta property="og:image" content="%s" />
<meta property="og:image:width" content="%s" />
<meta property="og:image:height" content="%s" />',esc_attr($social_img['src']),esc_attr($social_img['width']),esc_attr($social_img['height']));
?>
<meta property="og:site_name" content="<?php echo esc_attr(get_option('blogname')) ?>" />
<meta property="og:locale" content="<?php esc_attr(get_locale()); ?>" />

<!-- Twitter Card tags (optional, but recommended) -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?php echo esc_attr($title); ?>" />
<meta name="twitter:description" content="<?php echo esc_attr($description); ?>" />
<meta name="twitter:image" content="<?php if(!empty($social_img['src'])) echo esc_attr($social_img['src']); ?>" />
</head>
<body id="playground-iframe-body">
<div id="qckply-iframe-main">
<div id="qckply-iframe-container">
    <iframe src="<?php echo esc_url($src); ?>"></iframe>
<div id="qckply-iframe-footer">
    <p style="margin:0; width:100%; text-align:center;">This virtual website was created with WordPress Playground and the <a href="https://quickplayground.com">Quick Playground</a> plugin.</p>
</div>

</div>
    <?php if($sidebar) {
    printf('<div id="qckply-iframe-sidebar">
    <p id="closeline"><button id="qckply-iframe-close">&times;</button></p>
        %s
    </div>
',wp_kses_post($sidebar));
    }
    ?>

</div>

<?php
wp_print_inline_script_tag(
    "document.addEventListener('DOMContentLoaded', function() {
    var closeBtn = document.getElementById('qckply-iframe-close');
    var sidebar = document.getElementById('qckply-iframe-sidebar');
    var iframeContainer = document.getElementById('qckply-iframe-container');
    if (closeBtn && sidebar && iframeContainer) {
        closeBtn.addEventListener('click', function() {
            sidebar.style.display = 'none';
            iframeContainer.classList.add('full-width');
        });
    }
});",
    array(
        'id'    => 'hide-sidebar-js',
        'async' => true,
    )
);
?>
</body>
</html>
<?php
    die();    
}