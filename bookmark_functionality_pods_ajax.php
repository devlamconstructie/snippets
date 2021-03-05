<?php
//registering the action hook for catching the AJAX request
add_action('wp_ajax_sndt_bookmark_toggle', 'sndt_bookmark_toggle' );

/* the catching function */
function sndt_bookmark_toggle() {
    // collect the posted data 
	$in =  isset( $_POST ) ? $_POST : array();
	// set the variable that will contain the return data
	$out = array();
	if (!empty($in)){ //test if we have data
	    $uid = get_current_user_id();  // when moving this script to a plugin, we may need to localize it, or obfucate it.
		$corr= $in['corr']; 
		$p =  pods( 'briefwisseling', $corr ); 
		if (!empty($p)){ //check if pod has data
			//get array of id's from the corr field. 
			$f = in_array(
				$uid, 
				$p->field(array('name' => 'fav-bw-of', 'output'=>'ids'))
			);  
			if ( $f ){ 
				$p->remove_from( 'fav-bw-of', $uid );
				$out['message'] = 'removed';
			} else {
				$p->add_to( 'fav-bw-of', $uid );
				$out['message'] = 'added';
			};
 			$out['response'] = "SUCCESS";
			$out['svg'] = get_bookmark_svg(!$f);
			$out['elid'] = $in['elid'];
		} else {
			$out['id'] = $corr;
			$out['message'] = 'unable to find pod data.';
		}	
	} else {
		$out['response'] = "ERROR";
		$out['message'] = "looks like there's no data";
	}
	
	echo json_encode( $out );
	exit();
}

//print a uniform bookmark button, on or off based on whether user id is in the array
function print_bookmark_toggle_button($corr=null, $i=1, $usr_array = array()){
	// if no correspondence id was passed, use the one currently in the loop.
	if (!$corr){
		global $post;
		$corr = $post->ID;
	}
	if (empty($usr_array)){ //if no user array was passed, attempt to get one.
		$usr_array = pods_field('briefwisseling', $corr, array('name' => 'fav-bw-of', 'output'=>'ids'));
	} else {
		// if an array was passed, check if it needs unpacking. We just want an array of fields.
		$usr_array = (array_key_exists('ID', $usr_array))? array_column($usr_array, 'ID'): $usr_array;
	}
	
	$on = (!empty($usr_array) && in_array(get_current_user_id(), $usr_array))? true:false;
	/*troubleshooting*/
	return "<div class='briefwisseling_bookmark' data-switch='" . $on .  "' data-corr='" .  $corr . "' id='bw_bookmark_" . $i ."'>" . get_bookmark_svg($on) . "</div>"  .  $comments;
}

function get_bookmark_svg($on=false){
	ob_start(); //object buffer init, allows writing HTML with syntax highlighting	
?>
	<div class="svg_bookmark_wrapper icon-small_wrapper" ontouchstart="this.classList.toggle('hover');">
		<div class="bookmark-svg-flipper">
		 <svg x="0px" y="0px" viewBox="0 0 512 512" class="svg_bookmark svg_bookmark_front icon-small"  xml:space="preserve">
			 <?= ($on)? "<polygon points='66.783,0 66.783,512 256,322.783 445.217,512 445.217,0'/>" : "<path d='M70.715,0v512L256,326.715L441.285,512V0H70.715z M411.239,439.462L256,284.224L100.761,439.462V30.046h310.477V439.462z'/></path>"  ?>			
		</svg>
		<svg x="0px" y="0px" viewBox="0 0 512 512" class="svg_bookmark svg_bookmark_back icon-small" xml:space="preserve">
			 <?= (!$on)? "<polygon points='66.783,0 66.783,512 256,322.783 445.217,512 445.217,0'/>" : "<path d='M70.715,0v512L256,326.715L441.285,512V0H70.715z M411.239,439.462L256,284.224L100.761,439.462V30.046h310.477V439.462z'/></path>"  ?>			
		</svg>
		</div>	
	</div>
<style>/*making sure the icon doesn't flash while the footer loads. */
	.svg_bookmark_back {display: none;}	
</style>
	
<?php	
	return ob_get_clean();
}


function get_bookmark_script(){	
		// run this once on pages where bookmarks are placed.
  /* 
    e.g. by placing: 
      add_action( 'wp_footer', 'get_bookmark_script' ); 
   somewhere on the page 
  */
		ob_start(); //object buffer init, allows writing JS with syntax highlighting
		?>
		<script type='text/javascript'> 
		(function($) {
			
			$('.briefwisseling_bookmark').off('click').on('click', function(event){
			  var fd = new FormData();
			  var el = $(this);	
			  el.children('.svg_bookmark').addClass('svg-ajax-sending');	
			  fd.append("corr", el.data('corr')); // set by adding 'data' attribute to element, e.g. data-corr='21';
			  fd.append("elid", el.attr('id'));
			  fd.append("action", "sndt_bookmark_toggle");		 
			  sendAJAXBookmarkData(fd); /// Calling my ajax function 
			});

			function sendAJAXBookmarkData(bmd){
				$.ajax({
					url: '<?= admin_url( 'admin-ajax.php' ) ?>',
					type: 'POST',
					data: bmd,
					cache: false,
					dataType: 'json',
					processData: false, // necessary for some reason.  Don't process the files
					contentType: false, // also necessary. Set content type to false as jQuery will tell the server its a query string request
					success: function(data, textStatus, jqXHR) {
						if (data.response == 'SUCCESS') {
		  				  	console.log(data.message);
						  	$('#' + data.elid).empty().append(data.svg);
							$('#' + data.elid).children('.svg_bookmark').removeClass('svg-ajax-sending');
						 } else {
						  console.log(data.message);
						}           
					},
					error: function(jqXhr, textStatus, errorMessage){
					  console.log('er is iets mis gegaan.');
					  console.log(errorMessage);		
					},
					complete: function(){
					}
				});
			}
		// end jQuery 	
		})(jQuery);

		</script>
	<style>
	.svg_bookmark_wrapper {	
			perspective: 1000px; 
	}
		/* flip the pane when hovered */
		.svg_bookmark_wrapper:hover .bookmark-svg-flipper, .flip-container.hover .bookmark-svg-flipper {
			transform: rotateY(180deg);
		}

		/* flip speed goes here */
		.bookmark-svg-flipper {
			transition: 0.3s;
			transform-style: preserve-3d;
			position: relative;
		}

		/* hide back of pane during swap */
		.svg_bookmark_front, .svg_bookmark_back {
			backface-visibility: hidden;
			position: absolute;
			top: 0;
			left: 0;
		}

		/* front pane, placed above back */
		.svg_bookmark_front {
			z-index: 2;
			/* for firefox 31 */
			transform: rotateY(0deg);
		}

		/* back, initially hidden pane */
		.svg_bookmark_back {
			display: inherit; /*undo earlier declaration*/
			transform: rotateY(180deg);
		}
		
	</style>

		<?php
		// return the above script and clean the object.	
		echo ob_get_clean();
};


?>
