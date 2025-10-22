/**
 * Featured Image with URL admin javascript.
 */
jQuery(document).ready(function($){
	$(document).on("click", ".dpsfiwu_pvar_preview", function(e){

		e.preventDefault();
		var id = jQuery(this).data('id');
		imgUrl = $('#dpsfiwu_pvar_url_'+id).val();
		if ( imgUrl != '' ){
			$("<img>", { // Url validation
					    src: imgUrl,
					    error: function() { alert( dpsfiwujs.invalid_image_url ); },
					    load: function() {
							$('#dpsfiwu_pvar_img_wrap_'+id).show();
							$('#dpsfiwu_pvar_img_'+id).attr('src',imgUrl);
							$('#dpsfiwu_url_wrap_'+id).hide();
					    }
			});
		}
	});

	$(document).on("click", ".dpsfiwu_pvar_remove", function(e){
		var id2 = jQuery(this).data('id');

		e.preventDefault();
		$('#dpsfiwu_pvar_url_'+id2).val("").trigger("change");
		$('#dpsfiwu_pvar_img_'+id2).attr('src',"");
		$('#dpsfiwu_pvar_img_wrap_'+id2).hide();
		$('#dpsfiwu_url_wrap_'+id2).show();
	});
});