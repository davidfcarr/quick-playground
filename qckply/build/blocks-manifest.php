<?php
// This file is generated. Do not modify it manually.
return array(
	'qckply' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'qckply/qckply',
		'version' => '0.1.0',
		'title' => 'Quick Playground',
		'category' => 'widgets',
		'icon' => 'smiley',
		'description' => 'Configures and outputs a Quick Playground button, link, or iframe.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'attributes' => array(
			'domain' => array(
				'type' => 'string',
				'default' => ''
			),
			'label' => array(
				'type' => 'string',
				'default' => 'Go to Playground'
			),
			'profile' => array(
				'type' => 'string',
				'default' => 'default'
			),
			'type' => array(
				'type' => 'string',
				'default' => 'button'
			),
			'iframeWidth' => array(
				'type' => 'string',
				'default' => '100%'
			),
			'iframeHeight' => array(
				'type' => 'string',
				'default' => '1000px'
			)
		),
		'textdomain' => 'quick_playground',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScript' => 'file:./view.js'
	)
);
