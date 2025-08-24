import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RadioControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	    let { label,domain,profile,type,iframeWidth,iframeHeight } = attributes;
        if(!domain)
            domain = window.location.hostname;

	    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Block Settings', 'qckply' ) }>
					<RadioControl 
					label="Display Type"
            help="Choose button, link, or iframe"
            selected={ type }
            options={ [
                { label: __('Button','quick-playground'), value: 'button' },
                { label: __('Link','quick-playground'), value: 'link' },
                { label: __('iFrame','quick-playground'), value: 'iframe' },
            ] }
            onChange={ ( value ) => setAttributes( {type:value} ) } />
                    <TextControl
                        label={ __( 'Label', 'qckply' ) }
                        value={ label }
                        onChange={ ( value ) => setAttributes( { label: value } ) }
                    />
                    <TextControl
                        label={ __( 'Domain', 'qckply' ) }
                        value={ domain || window.location.hostname }
                        onChange={ ( value ) => setAttributes( { domain: value } ) }
                    />
                    <TextControl
                        label={ __( 'Profile', 'qckply' ) }
                        value={ profile }
                        onChange={ ( value ) => setAttributes( { profile: value } ) }
                    />
                    {
                    ('iframe' == type) ? 
                    <div>
                    <TextControl
                        label={ __( 'iframe Height', 'qckply' ) }
                        value={ iframeHeight }
                        onChange={ ( value ) => setAttributes( { iframeHeight: value } ) }
                    />
                    <TextControl
                        label={ __( 'iframe Width', 'qckply' ) }
                        value={ iframeWidth }
                        onChange={ ( value ) => setAttributes( { iframeWidth: value } ) }
                    />
                    </div> : null
                    }
                </PanelBody>
            </InspectorControls>
            <div { ...useBlockProps() }>
				{'button' == type && 
				<div><a target="_blank" style={{
  color: '#fff',
  fontSize: '16px',
  fontFamily: "\'Comic Sans MS\', cursive, sans-serif",
  border: "none",
  borderRadius: '12px',
  padding: '12px 20px',
  display: 'inline-flex',
  alignItems: 'center',
  textDecoration: 'none',
  backgroundColor: '#004165'}}>
<svg fill="#FFFFFF" height="50px" width="50px" version="1.1" 
	 viewBox="0 0 512.001 512.001" >
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
&nbsp;&nbsp;&nbsp;  { label || __( 'Go to Playground', 'qckply' ) }
</a></div>}
				{'link' == type && 
				<p><a href="#">{ label || __( 'Go to Playground', 'qckply' ) }</a></p>}
				{'iframe' == type && 
				<div style={{backgroundColor:'#111',color:'#fff',width:iframeWidth,height:iframeHeight}}><p> iFrame version will be displayed here<br />
				Domain: {domain} Profile: {profile}	
				</p>
                </div>}
            </div>
        </>
    );
}
