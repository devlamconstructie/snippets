
<?php

/**
* encodes email adress in rot13, and prints javascript to decode again client side.
* @param string         $email        emailadress required
* @param boolean        $link         optional, whether to add a mailto: link. default is true/
* @param string         $classname    optional, a classname for for the link. 
* 
**/
function get_obfuscated_email($email, link=true, $classname=null){
	 /*obfuscated email adress */
  $class = ($classname)? "class='$classname'" : "";
  $str = ($link)?"<a $class href='mailto:$email' rel='nofollow' >$email</a>": $email;
 
  //first we encode the adress server-side using rot13. 
	$str = str_rot13($str);
    
  //then we create some JS that decodes the string again.
	ob_start();
?>
<script>
  	var str = "<?= $str ?>"; 
  	decodedstr = str.replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26)});  
   	document.write(decodedstr);
	</script>
<?php
	return ob_get_clean();
}

function obfuscated_email($email){
	echo get_obfuscated_email($email);	
}

//example:
/*
print mailto email link
obfuscated_email('a.random@emailadr.ess', true, 'my_mailto_link');

//print only email adress
obfuscated_email($email, false)

//store mailto link string in variable. 
$mailtolink = get_obfuscated_email('a.random@emailadr.ess', true, 'my_mailto_link');

*/
?>
