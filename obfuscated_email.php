
<?php

/*
* encodes a mailto-link into a string and prints a span with an encoded email link in the data set, and a javascript snippet which
* fill the container with a decoded version of the string.
* @param string emailadress || the name of an option setting containing an adress.
* @param string (optional) id for the span container.
* @param string (optional) classname for the mailto link
*/

function get_obfuscated_email($eml_option, $id = 'mysterious_wrapper', $class='mailto-link'){
	
	$eml = (filter_var($eml_option, FILTER_VALIDATE_EMAIL))? $eml_option : get_option($eml_option);
	
	if(!filter_var($eml, FILTER_VALIDATE_EMAIL))
		return "no valid email found";
	
	$obfeml = str_rot13("<a class='$class' style='color: inherit;' href='mailto:$eml' rel='nofollow' >$eml</a>" );

	$container = sprintf('<span id="%s" class="scrambled-wrapper" data-scrambled="%s"></span>',$id,$obfeml);

	return $container;

}


add_action('wp_footer','print_obfuscated_email_js');

function print_obfuscated_email_js(){
	static $hasrun = false;

	if ($hasrun == true)
		return;

	$hasrun = true;	
	
	ob_start();
?><script>(()=>{ const wr = document.querySelectorAll('.scrambled-wrapper');for (const s of wr) {s.innerHTML = s.dataset.scrambled.replace(/[a-zA-Z]/g,c=>String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26))}})()</script><?php
	echo ob_get_clean();

}

function obfuscated_email($email_option, $id=null){
	echo get_obfuscated_email($email_option, $id);	
}

?>
