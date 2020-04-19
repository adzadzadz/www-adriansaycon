<?php

$grid = array (
	"row_height" => array (
		"type"	=> 'text',
		"value"	=> '200',
		"units"	=> 'px',
		"title"	=> 'Row Height',
		"desc"	=> 'The approximate height of rows' ),

	"mobile_row_height" => array (
		"type"	=> 'text',
		"value"	=> '200',
		"units"	=> 'px',
		"title"	=> 'Mobile Row Height',
		"desc"	=> 'The approximate height of rows on mobile devices (phones, tablets). <b>Does not consider resolution, window width, or any other factors!</b>' ),

	"fixed_height" => array (
		"type"	=> 'select',
		"value"	=> 'false',
		"options"	=> array (
			"false" => 'False',
			"true" 	=> 'True' 
			),
		"title"	=> 'Fixed Height',
		"desc"	=> 'All the rows will be exactly with the specified "Row Height" if True' ),

	"last_row" => array (
		"type"	=> 'select',
		"value"	=> 'nojustify',
		"options"   => array (
			"justify" 		=> 'Justify',
			"nojustify" 	=> 'No justify',
			"hide" 			=> 'Hide'
			),
		"title"	=> 'Last Row',
		"desc"  => 'Decide if you want to "Justify" the last row or not, or to "Hide" the row if it can\'t be justified' ),
	
	"margins" => array (
		"type"  => 'text',
		"value" => '10',
		"units" => 'px',
		"title"	=> 'Margin',
		"desc"  => 'Margin between images' ),

	"randomize" => array (
		"type"  => 'select',
		"value" => 'false',
		"options"   => array (
			"false" => 'False',
			"true" 	=> 'True' 
			),
		"title"	=> 'Randomize Images',
		"desc"  => 'Set to "True" to show images in random order' ),

	"load_more" => array (
		"type"  => 'select',
		"value" => 'scroll',
		"options"   => array (
			"scroll" 	=> 'On scroll',
			"button" 	=> 'On button click' 
			),
		"title"	=> 'Load More',
		"desc"  => 'Automatically load next [gallery] shortcode on page scroll or on "Load More" button click. <br/>Only for [smart-grids] with multiple [gallery] shortcodes inside.' ),

	"button_text" => array (
		"type"  => 'text',
		"value" => 'Load More',
		"units" => '',
		"title"	=> 'Button Text',
		"desc"  => '',
		"condition" => array (
				"load_more" => "button"
			)  
		),

	"button_background" => array (
		"type"  => 'color',
		"value" => '#444444',
		"title"  => 'Background Color',
		"desc"  => 'HEX color code for button background. <br/>(Your template styles may interfere)',
		"condition" => array (
				"load_more" => "button"
			) 
		),

	"button_text_color" => array (
		"type"  => 'color',
		"value" => '#ffffff',
		"title"  => 'Text Color',
		"desc"  => 'HEX color code for button text. <br/>(Your template styles may interfere)',
		"condition" => array (
				"load_more" => "button"
			) 
	),

	"button_style" => array (
		"type"	=> 'select',
		"value"	=> 'square',
		"options"   => array (
			"square"	=> 'Square',
			"round" 	=> 'Round',
			"circle" 	=> 'Circle',
			),
		"title"  => 'Button Style',
		"desc"  => 'Choose button shape. <br/>(Your template styles may interfere)',
		"condition" => array (
				"load_more" => "button"
			) 
	),

	"button_size" => array (
		"type"	=> 'select',
		"value"	=> 'medium',
		"options"   => array (
			"small"		=> 'Small',
			"medium" 	=> 'Medium',
			"big" 		=> 'Big',
			),
		"title"  => 'Button Size',
		"desc"  => 'Choose button size. <br/>(Your template styles may interfere)',
		"condition" => array (
				"load_more" => "button"
			) 
	),

	);

$captions = array (

	"captions" => array (
		"type"  => 'select',
		"value" => 'true',
		"options"   => array (
			"false" => 'Hide',
			"true" 	=> 'Show' 
			),
		"title"  => 'Show Captions',
		"desc"  => 'Decide Show or not image captions' ),

	"style" => array (
		"type"	=> 'select',
		"value"	=> '1',
		"options"   => array (
			"1" 	=> '1 - Fade',
			"2" 	=> '2 - Fade & Image Zoom',
			"3" 	=> '3 - Slide Up',
			"4" 	=> '4 - Slide Up Full',
			"5" 	=> '5 - Slide Down Full',
			"6" 	=> '6 - Slide Right Full',
			"7" 	=> '7 - Slide Left Full',
			"8" 	=> '8 - Direction Aware',
			"9" 	=> '9 - Slide Up Full & Image Out',
			"10" 	=> '10 - Slide Down Full & Image Out',
			"11" 	=> '11 - Caption Fade Out',
			"12" 	=> '12 - Caption Always Bottom',
			"13" 	=> '13 - Caption Fade Out/Fade In (Odd/Even)',
			),
		"title"  => 'Captions Style',
		"desc"  => 'Captions Hover Style. <a target="_blank" href="http://topdevs.net/smart-grid-gallery/wordpress-gallery-hover-effects/">See examples</a>',
		"condition" => array (
				"captions" => "true"
			)  
	),

	"captions_color" => array (
		"type"  => 'color',
		"value" => '#000000',
		"title"  => 'Captions Color',
		"desc"  => 'HEX color code for captions background',
		"condition" => array (
				"captions" => "true"
			) 
	),

	"captions_opacity" => array (
		"type"  => 'text',
		"value" => '0.7',
		"title"  => 'Captions opacity',
		"desc"  => 'Captions background opacity. Use \'.\' as decimal separator',
		"condition" => array (
				"captions" => "true"
			)  
	)
);

$font = array (

	"font_type" => array (
		"type"	=> 'select',
		"value"	=> 'regular',
		"options"   => array (
			"regular"	=> 'Regular Font Family',
			"google" 	=> 'Google Web Font',
			),
		"title"  => 'Font Type',
		"desc"  => 'Choose if you want to use regular font family or <a target="_blank" href="https://www.google.com/fonts#AboutPlace:about">Google Font</a>',
	),

	"font_family" => array (
		"type"	=> 'text',
		"value"	=> 'Helvetica, Arial, sans-serif',
		"title"	=> 'Font Family',
		"desc"	=> 'Use <a target="_blank" href="http://www.w3schools.com/cssref/css_websafe_fonts.asp">Web Safe Font</a> or any font family available in your theme',
		"condition" => array (
				"font_type" => "regular"
			) 
	),

	"google_font" => array (
		"type"	=> 'text',
		"value"	=> 'Lobster',
		"title"	=> 'Google Web Font',
		"desc"	=> 'Use any <a target="_blank" href="https://www.google.com/fonts">Google Font</a> name like "Open Sans", "Roboto" or "Droid Serif"',
		"condition" => array (
				"font_type" => "google"
			) 
	),

	"font_color" => array (
		"type"  => 'color',
		"value" => '#ffffff',
		"title" => 'Font Color',
		"desc"  => 'HEX color code for captions texts' ),
	
	"font_size" => array (
		"type"	=> 'text',
		"value"	=> '1em',
		"title"	=> 'Font Size',
		"desc"	=> 'Captions font size. Can be px, em or %' ),

	"font_weight" => array (
		"type"	=> 'select',
		"value"	=> '400',
		"options"   => array (
			"normal" 	=> 'normal',
			"bold" 		=> 'bold',
			"bolder" 	=> 'bolder',
			"lighter" 	=> 'lighter',
			"100" 		=> '100',
			"200" 		=> '200',
			"300" 		=> '300',
			"400" 		=> '400',
			"500" 		=> '500',
			"600" 		=> '600',
			"700" 		=> '700',
			"800" 		=> '800',
			"900" 		=> '900',
			),
		"title"  => 'Font Weight',
		"desc"  => 'Captions font weight' ),

	);

$lightbox = array (

	"lightbox" => array (
		"type"	=> 'select',
		"value"	=> 'photo-swipe',
		"options"   => array (
			"none" 				=> 'None',
			"image"				=> 'Link to image source',
			"photo-swipe" 		=> 'PhotoSwipe',
			"magnific-popup" 	=> 'Magnific Popup',
			"swipebox" 			=> 'Swipebox',
			),
		"title"  => 'Lightbox',
		"desc"  => '' ),
	
	"title" => array (
		"type"  => 'select',
		"value" => 'true',
		"options"   => array (
			"true" 	=> 'True',
			"false" => 'False'
			),
		"title" => 'Image Title',
		"desc"  => 'Set to "False" to hide image title',
		"condition" => array (
				"lightbox" => "photo-swipe,magnific-popup"
			)
	 ),

	"share" => array (
		"type"  => 'select',
		"value" => 'true',
		"options"   => array (
			"true" 	=> 'True',
			"false" => 'False'
			),
		"title" => 'Social Share',
		"desc"  => 'Enable social sharing options',
		"condition" => array (
				"lightbox" => "photo-swipe"
			)
	 ),

	"counter" => array (
		"type"	=> 'text',
		"value"	=> '(A/B)',
		"title"	=> 'Counter',
		"desc"	=> 'Counts which piece of content (A) is being viewed, relative to the total count (B) of items',
		"condition" => array (
				"lightbox" => "magnific-popup"
			)
	 ),

	
	"hide_bars_on_mobile" => array (
		"type"  => 'select',
		"value" => 'true',
		"options"   => array (
			"true" 	=> 'Hide',
			"false" => 'Show'
			),
		"title" => 'Bars On Mobile',
		"desc"  => '\'Show\' or \'Hide\'the caption and navbar on mobile devices',
		"condition" => array (
				"lightbox" => "swipebox"
			) 
		),

	"hide_bars_delay" => array (
		"type"	=> 'text',
		"value"	=> '3000',
		"units" => 'ms',
		"title"	=> 'Hide Bars Delay',
		"desc"	=> 'Delay before hiding bars',
		"condition" => array (
				"lightbox" => "swipebox"
			) 
		),

	);

$params = array (
	"grid" => array (
		"title" 	=> '<div class="dashicons dashicons-screenoptions"></div> Grid',
		"params"	=> $grid,
		),
	"captions" => array (
		"title" 	=> '<div class="dashicons dashicons-editor-quote"></div> Captions',
		"params"	=> $captions,
		),
	"font" => array (
		"title" 	=> '<div class="dashicons dashicons-editor-paste-text"></div> Typography',
		"params"	=> $font,
		),
	"lightbox" => array (
		"title" 	=> '<div class="dashicons dashicons-external"></div> Lightbox',
		"params"	=> $lightbox,
		),
	);

// Create instance
$sgg_tinymce = new SmartGridGalleryTinyMCE( 'sgg_', $params );

?>