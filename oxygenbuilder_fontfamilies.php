<?php
/*
* this function requires that Elegant Custom fonts is NOT installed. 
* the point is to declare @font-face declarations *manually* in css. 
* all this does is piggy-back on ECF plugin class being compatible with Oxygen Builder, in order to inject custom font declarations into Oxygen Builder's font selector.
* again: you have to remember to declare the font family names, url's and weights *manually* in separate CSS files
* this code was directly inspired by Matthias Altman's snippet found here: https://www.altmann.de/en/blog-en/code-snippet-integrate-custom-fonts-into-oxygen-en/
*/


if (!class_exists('ECF_Plugin')) :
class ECF_Plugin {

    // this function is apparently called within Oxygenbuilder if the ECF_Plugin class is active.
    static function get_font_families() {
       $font_family_list[] = "your font family name"; 
       // $font_family_list[] = "Add, Font-Family-Names, As, Needed";
		
	   return $font_family_list;	
    }
};
endif;
?>
