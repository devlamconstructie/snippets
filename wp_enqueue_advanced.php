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
    dvi_update_async_script_handles('jquery'); //adds the jquery script to the list of scripts the async attribute needs to be added to.
    dvi_update_defer_script_handles('jquery'); //adds the jquery script to the list of scripts the defer attribute needs to be added to.
}


/*
END EXAMPLE
*/

/**
 * returns static handle array for asynchronously loading scripts. Returns empty array if none present.
 * adds handle to array if present and not in array.
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
 * returns static handle array for deferring scripts.
 * adds handle to array if present and not in array.
 * creates it if it doesn't exist yet.. 
 * @param string script handle
 * @return	array  
 */
function dvi_update_defer_script_handles($handle=''){
	static $handle_array = array();

	if ($handle && ! in_array($handle, $handle_array) ) 
		$handle_array[] = $handle;
		
	return $handle_array;
}

/**
 * returns static handle array for scripts loaded as a module.
 * adds handle to array if present and not in array.
 * creates it if it doesn't exist yet.. 
 * @param string script handle
 * @return	array  
 */
function dvi_update_module_script_handles($handle=''){
	static $handle_array = array();

	if ($handle && ! in_array($handle, $handle_array) ) 
		$handle_array[] = $handle;
		
	return $handle_array;
}

/** 
* filters enqeueud script handles and passes them through add_tag_attribute functions. 
* @uses dvi_add_tag_attribute_async()
* @uses dvi_add_tag_attribute_defer()
* @param string $tag the tag as generated by wp_enqueue_script
* @param string $handle the handle as passed as paramater to wp_enqueue_script.
*/
add_filter( 'script_loader_tag', 'dvi_add_tag_attributes', 10, 2 );
function dvi_add_tag_attributes( $tag, $handle ) {

	$tag = dvi_add_tag_attribute_async($tag, $handle);

	$tag = dvi_add_tag_attribute_defer($tag, $handle);
	
	$tag = dvi_add_tag_attribute_type_module($tag, $handle);
		
	return $tag;	
}

/**
 * adds async tag attribute if the handle was added to the async handle array using
 * dvi_update_async_script_handles()
 * @uses dvi_update_defer_script_handles() 
 * @param string $tag the tag as generated by wordpress
 * @param string $handle the handle as defined in wp_enqueue  	
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
 */
function dvi_add_tag_attribute_type_module($tag, $handle){
	$handle_module_array = dvi_update_module_script_handles();
	
	if (! empty($handle_module_array) && in_array($handle, $handle_module_array) )
		$tag = str_replace( ' src', ' type="module" src', $tag );
	
	return $tag;
}


?>
