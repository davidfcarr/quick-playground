<?php
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
    if (!is_admin() && isset($_GET['qckply'])) {
        $src = get_option('use_playground', 'https://playground.wordpress.net');
        $blueprint_domain = sanitize_text_field($_GET['domain']);
        $blueprint_profile =  sanitize_text_field($_GET['qckply']);
        $blueprint_url = 'https://'.$blueprint_domain.'/wp-json/quickplayground/v1/blueprint/'.$blueprint_profile.'?t='.time();
        $display = get_option('qckply_display_'.$blueprint_profile,[]);
        $title = empty($display['iframe_title']) ? 'Quick Playground' : sanitize_text_field($display['iframe_title']);
        foreach($_GET as $key => $value) {
            if(('domain' != $key) && ('qckply' != $key) )
            $blueprint_url .= (strpos($blueprint_url, '?') === false ? '?' : '&') . urlencode($key) . '=' . urlencode(sanitize_text_field($value));
        }
        $sidebar = '';
        if(empty($_GET['sidebar']))
        {
            $sidebar = qckply_sidebar_default();
        }
        if(isset($_GET['no_sidebar']) || 'no_sidebar' == $display['iframe']) {
            $sidebar = $false;
        }
        elseif(isset($_GET['sidebar'])) {
            $post_id = intval($_GET['sidebar']);
            $post = get_post($post_id);
            $sidebar = do_blocks($post->post_content);
            if(current_user_can('edit_post',$post_id))
                $sidebar .= sprintf('<p><a target="_blank" href="%s">Edit</a></p>',admin_url('post.php?action=edit&post='.$post_id));
        }

        $src .= '/?blueprint-url='.urlencode($blueprint_url).'&now='.time();

        $sidebar_width = (empty($display['sidebar_width'])) ? 300 : intval($display['sidebar_width']);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo esc_html($title); ?> (Quick Playground)</title>
    <?php wp_print_styles(); ?>
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
            width: <?php echo $sidebar_width; ?>px;
            /*
            background: #222;
            color: #fff;
            */
            border-left: thick solid #222;
            padding: 0px 16px 16px 16px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            transition: width 0.3s, opacity 0.3s;
        }
        /*
        #sidebar a {
            color: yellow;
        }
        */
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
',$sidebar);
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
}