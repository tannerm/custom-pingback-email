jQuery(document).ready(function($) {
	"use strict";

	var $pingEmailCont = $( document.getElementById( 'cpe_pingback_email' )).parent().parent(),
	    $adminEmailCont = $( document.getElementById( 'admin_email' ) ).parent().parent();

	$pingEmailCont.insertAfter( $adminEmailCont );
});