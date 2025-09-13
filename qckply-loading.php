<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('init', 'qckply_loading');
function qckply_loading() {
    global $post;
    $title = get_option('blogname');
    //cannot be checked by nonce. This is relayed from the live server to the playground environment
    if(qckply_is_playground() && isset($_GET['qckply_clone'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo esc_html($title); ?> (Playground Loading)</title>
    <?php 
    wp_enqueue_style( 'qckply_style', plugin_dir_url( __FILE__ ) . 'quickplayground.css', array(), '1.2' );
    wp_print_styles(); ?>
</head>
<body id="playground-loading">
<svg fill="#FFFFFF" height="200px" width="200px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
	 viewBox="0 0 512.001 512.001" xml:space="preserve">
<g>
	<g>
		<path d="M501.335,170.587h-352v-21.333h10.667c4.181,0,7.979-2.453,9.728-6.251c1.728-3.819,1.067-8.299-1.685-11.435
			L93.377,46.235c-4.053-4.651-12.011-4.651-16.064,0L2.647,131.569c-2.752,3.157-3.435,7.616-1.685,11.435
			c1.728,3.797,5.525,6.251,9.707,6.251h10.667v309.333c0,5.888,4.779,10.667,10.667,10.667s10.667-4.779,10.667-10.667v-10.667
			h85.333v10.667c0,5.888,4.779,10.667,10.667,10.667s10.667-4.779,10.667-10.667V191.921h64V300.55
			c-12.395,4.416-21.333,16.149-21.333,30.037c0,17.643,14.357,32,32,32s32-14.357,32-32c0-13.888-8.939-25.621-21.333-30.037
			V191.921h85.333v87.296c-12.395,4.416-21.333,16.149-21.333,30.037c0,17.643,14.357,32,32,32c17.643,0,32-14.357,32-32
			c0-13.888-8.939-25.621-21.333-30.037v-87.296h64v266.667c0,5.888,4.779,10.667,10.667,10.667c5.888,0,10.667-4.779,10.667-10.667
			V191.921h42.667v23.573c-6.4,2.667-10.667,7.531-10.667,13.76c0,6.229,4.267,11.093,10.667,13.76v31.147
			c-6.4,2.667-10.667,7.531-10.667,13.76s4.267,11.093,10.667,13.76v31.147c-6.4,2.667-10.667,7.531-10.667,13.76
			c0,6.229,4.267,11.093,10.667,13.76v31.147c-6.4,2.667-10.667,7.531-10.667,13.76c0,6.229,4.267,11.093,10.667,13.76v18.24
			c0,5.888,4.779,10.667,10.667,10.667c5.888,0,10.667-4.779,10.667-10.667v-18.24c6.4-2.667,10.667-7.531,10.667-13.76
			c0-6.229-4.267-11.093-10.667-13.76v-31.147c6.4-2.667,10.667-7.531,10.667-13.76c0-6.229-4.267-11.093-10.667-13.76v-31.147
			c6.4-2.667,10.667-7.531,10.667-13.76c0-6.229-4.267-11.093-10.667-13.76v-31.147c6.4-2.667,10.667-7.531,10.667-13.76
			s-4.267-11.093-10.667-13.76v-23.573h10.667c5.888,0,10.667-4.779,10.667-10.667S507.223,170.587,501.335,170.587z
			 M128.001,426.587H42.668v-42.667h85.333V426.587z M128.001,362.587H42.668v-42.667h85.333V362.587z M128.001,298.587H42.668
			v-42.667h85.333V298.587z M128.001,234.587H42.668v-85.333h85.333V234.587z M34.177,127.921l51.157-58.475l51.157,58.475H34.177z
			 M224.001,341.254c-5.888,0-10.667-4.779-10.667-10.667s4.779-10.667,10.667-10.667s10.667,4.779,10.667,10.667
			S229.889,341.254,224.001,341.254z M330.668,319.921c-5.888,0-10.667-4.779-10.667-10.667s4.779-10.667,10.667-10.667
			s10.667,4.779,10.667,10.667S336.556,319.921,330.668,319.921z"/>
	</g>
</g>
</svg>
    <h1>Quick Playground for WordPress</h1>
    <h2>Loading <?php echo esc_html($title); ?></h2>
    <p>If the site does not load within a few seconds, <a href="<?php echo esc_attr(site_url()); ?>">click here</a></p>
<?php
        $target = sanitize_text_field(wp_unslash($_GET['qckply_clone']));
        $output = '';
        if('images' == $target) {
            $more = qckply_clone_images('images');
            if($more) {
                echo '<div id="qckply-overlay-message"><p>Loading '.esc_html($more).' more images ...</p></div>';
                wp_print_inline_script_tag('window.location.href="'.esc_url(qckply_link(['qckply_clone'=>'thumbnails'])).'"',
                    array(
                        'id'    => 'hide-sidebar-js',
                        'async' => true,
                    )
                );
            }
            else {
                qckply_top_ids();
                do_action('qckply_loading');
                $landingpage = qckply_link();
                printf('<div id="qckply-overlay-message"><p>Done, redirect to <a href="%s">%s</a></p></div>',esc_attr($landingpage),esc_html($landingpage));
                wp_print_inline_script_tag('window.location.href="'.esc_url($landingpage).'"',
                        array(
                            'id'    => 'hide-sidebar-js',
                            'async' => true,
                        )
                );
            }
        } 
        elseif('thumbnails' == $target) {
            $more = qckply_get_more_thumbnails();
            if($more) {
                echo '<div id="qckply-overlay-message"><p>Loading '.esc_html($more).' more images ...</p></div>';
                wp_print_inline_script_tag('window.location.href="'.esc_url(qckply_link(['qckply_clone'=>'thumbnails'])).'"',
                    array(
                        'id'    => 'hide-sidebar-js',
                        'async' => true,
                    )
                );
                return;
            }
            qckply_top_ids();
            do_action('qckply_loading');
            $landingpage = qckply_link();
            printf('<div id="qckply-overlay-message"><p>Done, redirect to <a href="%s">%s</a></p></div>',esc_attr($landingpage),esc_html($landingpage));
            wp_print_inline_script_tag('window.location.href="'.esc_url($landingpage).'"',
                    array(
                        'id'    => 'hide-sidebar-js',
                        'async' => true,
                    )
            );

        }
        else {
            qckply_clone( 'settings' );
            qckply_clone( 'taxonomy' );
            qckply_clone( 'custom' );
            qckply_clone( 'prompts' );
            $output = ob_get_clean();
            echo '<div id="qckply-overlay-message"><p>Loading images ...</p></div>';
            wp_print_inline_script_tag('window.location.href="'.esc_url(qckply_link(['qckply_clone'=>'images'])).'"',
                array(
                    'id'    => 'hide-sidebar-js',
                    'async' => true,
                )
            );
        }
?>
</body>
</html>
<?php
    die();    
    }
}