<?php
/* 
some functions and hooks that allow for asynchronous and deferred script loading and loading as a module.
how to use:

enqueue your scripts as normal using the wp_enqueue_scripts action hook.

EXAMPLE:
this example unloads the default local jquery version and replaces it with a cdn version loaded asynchronously and deferred.
*/

add_action( 'wp_enqueue_scripts', 'dvi_enqueue_scripts' );

function dvi_enqueue_scripts(){ 
   //remove default jquery from script queue. Wow. queue is just the weirdest word to spell.
   wp_dequeue_script( 'jquery' );
   wp_deregister_script( 'jquery' );
 
   //register the desired jquery version and source.
   $version = '3.6.0';
   wp_register_script( 'jquery', "https://ajax.googleapis.com/ajax/libs/jquery/$version/jquery.min.js", '', $version );
  
    //optional: use local jquery as fallback
    wp_add_inline_script( 'jquery', 'window.jQuery||document.write(\'<script src="'.includes_url( '/js/jquery/jquery.min.js' ).'"><\/script>\')' );
    wp_enqueue_script ( 'jquery' );
   
    /*
      so far, everything has been just default wordpress functions. Now comes the interesting bit.
    */
   dvi_enqueue_script_attr('jquery', 'async', 'defer');	
   
}

/*
END EXAMPLE
*/


/**
* allows using only a single command for different script type attributes.
* @param string $handle the script handle
* @param mixed ...$atts comma separated strings for describing different attributes: accepts 'async', 'defer' and 'module.
*/
function dvi_enqueue_script_attr($handle, ...$atts){
	if(! $handle || empty($atts))
		return;
	
	foreach($atts as $attr){
		switch($attr){
			case('async'):
				dvi_update_async_script_handles($handle);
				break;	
			case('defer'):
				dvi_update_defer_script_handles($handle);
				break;
			case('module'):
				dvi_update_module_script_handles($handle);
				break;
			case('autoversion'):
				dvi_update_autoversion_script_handles($handle);
				break;	
			default:
				break;
		}		
		
	}	
}




/**
 * returns static handle array for asynchronously loading scripts. Returns empty array if none present.
 * adds handle parameter to static array if one was passed and not already in array.
 * creates it if it doesn't exist yet.
 * @param string handle
 * @return	array  
 */
function dvi_update_async_script_handles($handle=''){
	static $handle_array = array();

	if ($handle && ! in_array($handle, $handle_array) ) 
		$handle_array[] = $handle;
		
	return $handle_array;
}

/**
 * returns static handle array for deferring scripts. Returns empty array if none present.
 * adds handle parameter to static array if one was passed and not already in array.
 * creates it if it doesn't exist yet.
 * @param string handle
 * @return	array  
 */
function dvi_update_defer_script_handles($handle=''){
	static $handle_array = array();

	if ($handle && ! in_array($handle, $handle_array) ) 
		$handle_array[] = $handle;
		
	return $handle_array;
}

/**
 * returns static handle array for scripts loaded as a module. Returns empty array if none present.
 * adds handle parameter to static array if one was passed and not already in array.
 * creates it if it doesn't exist yet.
 * @param string handle
 * @return	array  
 */
function dvi_update_module_script_handles($handle=''){
	static $handle_array = array();

	if ($handle && ! in_array($handle, $handle_array) ) 
		$handle_array[] = $handle;
		
	return $handle_array;
}


/**
 * returns static handle array for scripts intended to autoversion.
 * adds handle to array if present and not in array.
 * creates it if it doesn't exist yet.. 
 * @param string script handle
 * @return	array  
 */
function dvi_update_autoversion_script_handles($handle=''){
	static $handle_array = array();

	if ($handle && ! in_array($handle, $handle_array) ) 
		$handle_array[] = $handle;
	return $handle_array;
}


/** 
* filters enqeueud script handles and passes them through add_tag_attribute functions. 
* @uses dvi_add_tag_attribute_async()
* @uses dvi_add_tag_attribute_defer()
* @uses dvi_add_tag_attribute_type_module()
* @uses dvi_add_tag_auto_version($tag, $handle, $src);
* @param string $tag the tag as generated by wp_enqueue_script
* @param string $handle the handle as passed as paramater to wp_enqueue_script.
* @return string html for the script tag.
*/
add_filter( 'script_loader_tag', 'dvi_add_tag_attributes', 10, 3 );
function dvi_add_tag_attributes( $tag, $handle, $src ) {

	$tag = dvi_add_tag_attribute_async($tag, $handle);

	$tag = dvi_add_tag_attribute_defer($tag, $handle);

	$tag = dvi_add_tag_attribute_type_module($tag, $handle);
	
	$tag = dvi_add_tag_auto_version($tag, $handle, $src);

	return $tag;	
}

/**
 * adds async tag attribute if the handle was added to the async handle array using
 * dvi_update_async_script_handles()
 * @uses dvi_update_defer_script_handles() 
 * @param string $tag the tag as generated by wordpress
 * @param string $handle the handle as defined in wp_enqueue  	
 * @return string html for the script tag.
 */
function dvi_add_tag_attribute_async($tag, $handle){
	//retrieve the array of handles created during the enqueueing phase.
	$async_handles_array = dvi_update_async_script_handles();
	
	if (! empty($async_handles_array) && in_array($handle, $async_handles_array ) )
		$tag = str_replace( ' src', ' async="async" src', $tag );
	
	return $tag;
}

/**
 * adds defer tag attribute if the handle was added to the defer handle array using
 * dvi_update_defer_script_handles()
 * @uses dvi_update_defer_script_handles() 
 * @param string $tag the tag as generated by wordpress
 * @param string $handle the handle as defined in wp_enqueue  	
 * @return string html for the script tag.
 */
function dvi_add_tag_attribute_defer($tag, $handle){
	//retrieve the array of handles created during the enqueueing phase.
	$defer_handles_array = dvi_update_defer_script_handles();
	
	if (! empty($defer_handles_array) && in_array($handle, $defer_handles_array ) )
		$tag = str_replace( ' src', ' defer="defer" src', $tag );
	
	return $tag;
}

/**
 * adds type='module' tag attribute if the handle was added to the defer handle array using
 * dvi_update_module_script_handles()
 * @uses dvi_update_module_script_handles() 
 * @param string $tag the tag as generated by wordpress
 * @param string $handle the handle as defined in wp_enqueue  	
 * @return string html for the script tag.
 */
function dvi_add_tag_attribute_type_module($tag, $handle){
	$handle_module_array = dvi_update_module_script_handles();
	
	if (! empty($handle_module_array) && in_array($handle, $handle_module_array) )
		$tag = str_replace( ' src', ' type="module" src', $tag );
	
	return $tag;
}

/**
 * replaces the 'ver' query argument with a ver key and value based on the
 * date and time the file was changed. 
 * @uses dvi_update_autoversion_script_handles() 
 * @param string $tag the tag as generated by wordpress
 * @param string $handle the handle as defined in wp_enqueue 
 * @param string $src the source value as compiled by WP_Scripts  
 */
function dvi_add_tag_auto_version($tag, $handle, $src){
	/* exit if file not on this server */
	
	$plugin_url = trailingslashit( trailingslashit( plugins_url() ) . plugin_basename( dirname( __FILE__ ) ) ) ;
	$plugin_path = trailingslashit( dirname(__FILE__, 1) ;
		
	$handle_autov_array = dvi_update_autoversion_script_handles();

	/* exit if no autoversion scripts were queued */ 
	if (empty($handle_autov_array))
		return $tag;

	/* exit if handle not in autov queue */
	if (! in_array($handle, $handle_autov_array ) )
		return $tag;
	
	/* clear version info from src */
	$file_url = explode('?', $src);
	$file_url = array_shift($file_url);
	/* retrieve file edit time from file  */
	$path = str_replace($plugin_url, $plugin_path, $file_url);

	/* maybe I should only replace the ver argument in the string in case there's more args*/
	$newsrc = remove_query_arg( 'ver', $src );
	$newsrc = add_query_arg( 'ver',  filemtime($path), $newsrc );

	$tag = str_replace( $src, $newsrc, $tag );
	
	return $tag;
}

?>
