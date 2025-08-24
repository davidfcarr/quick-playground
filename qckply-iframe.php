<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_shortcode('qckply_iframe_shortcode', 'qckply_iframe_shortcode');
function qckply_iframe_shortcode($args) {
    $url = site_url();
    $height = (empty($args['height'])) ? '1000px' : sanitize_text_field(($args['height']));
    $width = (empty($args['width'])) ? '1000px' : sanitize_text_field(($args['width']));
    foreach($args as $key => $value) {
        if('height' != $key && 'width' != $key)
            $url .= (strpos($url, '?') === false ? '?' : '&') . urlencode($key) . '=' . urlencode(sanitize_text_field($value));
    }
    $divcss = 'width: 100%; height: 1000px;';
    return '<div style="width: 100%; height: 1000px;"><iframe src="'.$url.'" height="100%" width="100%"></iframe></div>';
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
    if (is_admin() || !isset($_GET['qckply']) || !isset($_GET['domain'])) {
        return;
    }
        $sidebar = '';
        $src = get_option('use_playground', 'https://playground.wordpress.net');
        $blueprint_domain = sanitize_text_field(wp_unslash($_GET['domain']));
        $blueprint_profile =  sanitize_text_field(wp_unslash($_GET['qckply']));
        $blueprint_url = 'https://'.$blueprint_domain.'/wp-json/quickplayground/v1/blueprint/'.$blueprint_profile.'?t='.time();
        $display = get_option('qckply_display_'.$blueprint_profile,[]);
        $title = empty($display['iframe_title']) ? 'Quick Playground' : sanitize_text_field($display['iframe_title']);
        $sidebar_id = isset($_GET['sidebar']) ? intval($_GET['sidebar']) : 0;
        foreach($_GET as $key => $value) {
            if(('domain' != $key) && ('qckply' != $key) )
            $blueprint_url .= (strpos($blueprint_url, '?') === false ? '?' : '&') . urlencode($key) . '=' . urlencode(sanitize_text_field($value));
        }
        $sidebar = '';
        if(!$sidebar_id)
        {
            $sidebar = qckply_sidebar_default();
        }
        if(isset($_GET['no_sidebar']) || 'no_sidebar' == $display['iframe']) {
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
    <?php @wp_print_styles(); ?>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        #main {
            flex: 1 1 auto;
            display: flex;
            flex-direction: row;
            min-height: 0;
        }
        #qckply-iframe-container {
            flex: 1 1 0;
            min-width: 0;
            transition: width 0.3s;
            background: #f9f9f9;
            height: calc(100vh - 40px);
        }
        #qckply-iframe-container.full-width {
            width: 100% !important;
            flex: 1 1 100%;
        }
        #qckply-iframe-container iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }
        #sidebar {
            width: <?php echo isset($display['sidebar_width']) ? intval($display['sidebar_width']) : 300; ?>px;
            border-left: thick solid #222;
            padding: 0px 16px 16px 16px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            transition: width 0.3s, opacity 0.3s;
        }
        #sidebar h2 {
            margin-top: 0;
            font-size: 1.3em;
        }
        #sidebar p {
            margin-bottom: 1em;
        }
        #footer {
            height: 40px;
            background: #eee;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95em;
        }
        #closeline {
            text-align: right;
        }
        #close {
            background: #aaa;
            border: thin solid #000;
            border-radius: 6px;
            padding: 16px 16px;
            cursor: pointer;
            margin-top: 1em;
            color: red;
            font-size: large;
        }
        @media (max-width: 700px) {
            #sidebar {
                display: none !important;
            }
            #qckply-iframe-container {
                width: 100% !important;
                flex: 1 1 100%;
            }
        }
    </style>
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
<body>
<div id="main">
<div id="qckply-iframe-container">
    <iframe src="<?php echo esc_url($src); ?>"></iframe>
<div id="footer">
    <p style="margin:0; width:100%; text-align:center;">This virtual website was created with WordPress Playground and the <a href="https://quickplayground.com">Quick Playground</a> plugin.</p>
</div>

</div>
    <?php if($sidebar) {
    printf('<div id="sidebar">
    <p id="closeline"><button id="close">&times;</button></p>
        %s
    </div>
',wp_kses_post($sidebar));
    }
    ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var closeBtn = document.getElementById('close');
    var sidebar = document.getElementById('sidebar');
    var iframeContainer = document.getElementById('qckply-iframe-container');
    if (closeBtn && sidebar && iframeContainer) {
        closeBtn.addEventListener('click', function() {
            sidebar.style.display = 'none';
            iframeContainer.classList.add('full-width');
        });
    }
});
</script>
</body>
</html>
<?php
    die();    
}