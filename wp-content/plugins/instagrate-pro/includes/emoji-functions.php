<?php

require_once dirname( __FILE__ ) . '/emoji.php';

$GLOBALS['emoji_maps']['html_to_unified'] = array_flip( $GLOBALS['emoji_maps']['unified_to_html'] );

#
# functions to convert incoming data into the unified format
#

function igp_emoji_docomo_to_unified( $text ) {
	return igp_emoji_convert( $text, 'docomo_to_unified' );
}

function igp_emoji_kddi_to_unified( $text ) {
	return igp_emoji_convert( $text, 'kddi_to_unified' );
}

function igp_emoji_softbank_to_unified( $text ) {
	return igp_emoji_convert( $text, 'softbank_to_unified' );
}

function igp_emoji_google_to_unified( $text ) {
	return igp_emoji_convert( $text, 'google_to_unified' );
}


#
# functions to convert unified data into an outgoing format
#

function igp_emoji_unified_to_docomo( $text ) {
	return igp_emoji_convert( $text, 'unified_to_docomo' );
}

function igp_emoji_unified_to_kddi( $text ) {
	return igp_emoji_convert( $text, 'unified_to_kddi' );
}

function igp_emoji_unified_to_softbank( $text ) {
	return igp_emoji_convert( $text, 'unified_to_softbank' );
}

function igp_emoji_unified_to_google( $text ) {
	return igp_emoji_convert( $text, 'unified_to_google' );
}

function igp_emoji_unified_to_html( $text ) {
	return igp_emoji_convert( $text, 'unified_to_html' );
}

function igp_emoji_html_to_unified( $text ) {
	return igp_emoji_convert( $text, 'html_to_unified' );
}

function igp_emoji_html_stripped( $text ) {
	return igp_emoji_convert( $text, 'emoji_html_stripped' );
}


function igp_emoji_convert( $text, $map ) {
	$emojis = array_keys( $GLOBALS['emoji_maps'][ $map ] );

	if ( 'emoji_html_stripped' == $map ) {
		$replace = '';
	} else {
		$replace = $GLOBALS['emoji_maps'][ $map ];
	}

	return str_replace( $emojis, $replace, $text );
}

function igp_emoji_get_name( $unified_cp ) {

	return $GLOBALS['emoji_maps']['names'][ $unified_cp ] ? $GLOBALS['emoji_maps']['names'][ $unified_cp ] : '';
}
