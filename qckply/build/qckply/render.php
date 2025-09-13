<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if(wp_is_json_request())
	return;
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php
	if(empty($attributes['domain']))
		$attributes['domain'] = sanitize_text_field($_SERVER['SERVER_NAME']);
	if(empty($attributes['type']))
		$attributes['type'] = 'button';
	$attributes['is_demo'] = true;
	
	if('button' == $attributes['type'])
		qckply_get_button($attributes,true);
	if('iframe' == $attributes['type'])
		qckply_iframe_shortcode($attributes,true);
	if('link' == $attributes['type'])
		echo wp_kses_post(qckply_get_blueprint_link($attributes));	
?>	
</div>
