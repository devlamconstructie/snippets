<?php
/*previous, next and adjacent post URL's */
function o2_get_previous_post_permalink($same_cat=false, $excl='',$tax='category'){
	 $p=get_previous_post($same_cat, $excl, $tax);
	 return $p ? get_permalink($p->ID) : null;
}
function o2_get_next_post_permalink($same_cat=false, $excl=array(),$tax='category'){
    $p=get_next_post($same_cat, $excl, $tax);
	return $p ? get_permalink($p->ID) : null;
}
/*previous, next and adjacent post titles */
function o2_get_previous_post_title($same_cat=false, $excl=array(),$tax='category'){
   $p = get_previous_post($same_cat, $excl, $tax)->post_title;	
   return $p ? apply_filters( 'the_title', $p) : null; 
}
function o2_get_next_post_title($same_cat=false, $excl=array(),$tax='category'){
   $p = get_next_post($same_cat, $excl, $tax)->post_title;	
   return $p ? apply_filters( 'the_title', $p) : null; 
}
/* some oxy conditions for checking for sibling posts */
if( function_exists('oxygen_vsb_register_condition') ) {
	oxygen_vsb_register_condition('Next post published', array('options'=>array('true', 'false'), 'custom'=>false), array('=='), 'cb_next_post_exists', 'Post');
	oxygen_vsb_register_condition('Previous post published', array('options'=>array('true', 'false'), 'custom'=>false), array('=='), 'cb_previous_post_exists', 'Post');
	oxygen_vsb_register_condition('Next in Category published', array('options'=>array('true', 'false'), 'custom'=>false), array('=='), 'cb_cat_has_next_post', 'Post');
	oxygen_vsb_register_condition('Previous in Category published', array('options'=>array('true', 'false'), 'custom'=>false), array('=='), 'cb_cat_has_prev_post', 'Post');
}
function cb_next_post_exists($v, $o){
	return ($v === 'true') === boolval(get_next_post()));
}
function cb_previous_post_exists($v, $o){
	return ($v === 'true') === boolval(get_previous_post());
}
function cb_cat_has_next_post($v, $o){
	return ($v === 'true') === boolval(get_next_post(1));
}
function cb_cat_has_previous_post($v, $o){
	return ($v === 'true') === boolval(get_previous_post(1));
}

?>
