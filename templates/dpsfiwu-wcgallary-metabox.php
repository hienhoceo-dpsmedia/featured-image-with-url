<?php
/**
 * Featured Image with URL metabox Template
 *
 * @package DPSFIWU/Templates
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print Gallary Slot.
 *
 * @param string $image_url Image URL.
 * @param int    $count     Count. Default 1.
 * @return void
 */
function dpsfiwu_print_gallary_slot( $image_url = '', $count = 1 ) {
	?>
	<div id="dpsfiwu_wcgallary<?php echo esc_attr( $count ); ?>" class="dpsfiwu_wcgallary">
		<div id="dpsfiwu_url_wrap<?php echo esc_attr( $count ); ?>" style="<?php echo ( ! empty( $image_url ) ? 'display:none;' : '' ); ?>">
			<input
				id="dpsfiwu_url<?php echo esc_attr( $count ); ?>"
				class="dpsfiwu_url"
				type="text"
				name="dpsfiwu_wcgallary[<?php echo esc_attr( $count ); ?>][url]"
				placeholder="<?php esc_attr_e( 'Image URL', 'dpsfiwu' ); ?>"
				data-id="<?php echo esc_attr( $count ); ?>"
				value="<?php echo esc_url( $image_url ); ?>"
			/>
			<a id="dpsfiwu_preview<?php echo esc_attr( $count ); ?>" class="dpsfiwu_preview button" data-id="<?php echo esc_attr( $count ); ?>">
				<?php esc_html_e( 'Preview', 'dpsfiwu' ); ?>
			</a>
		</div>
		<div id="dpsfiwu_img_wrap<?php echo esc_attr( $count ); ?>" class="dpsfiwu_img_wrap" style="<?php echo ( empty( $image_url ) ? 'display:none;' : '' ); ?>">
			<span href="#" class="dpsfiwu_remove" data-id="<?php echo esc_attr( $count ); ?>"></span>
			<img id="dpsfiwu_img<?php echo esc_attr( $count ); ?>" class="dpsfiwu_img" data-id="<?php echo esc_attr( $count ); ?>" src="<?php echo esc_url( $image_url ); ?>" />
		</div>
	</div>
	<?php
}
?>

<div id="dpsfiwu_wcgallary_metabox_content" >
	<?php
	global $dpsfiwu;
	$count          = 1;
	$gallary_images = $dpsfiwu->common->dpsfiwu_get_wcgallary_meta( $post->ID );
	if ( ! empty( $gallary_images ) ) {
		foreach ( $gallary_images as $gallary_image ) {
			dpsfiwu_print_gallary_slot( $gallary_image['url'], $count );
			$count++;
		}
	}
	dpsfiwu_print_gallary_slot( '', $count );
	$count++;
	?>
</div>
<template id="dpsfiwu_wcgallary_template" style="display: none;">
	<?php dpsfiwu_print_gallary_slot( '', '__COUNT__' ); ?>
</template>
<div style="clear:both"></div>

<?php
wp_nonce_field( 'dpsfiwu_wcgallary_nonce_action', 'dpsfiwu_wcgallary_nonce' );
?>
<script>
	function dpsfiwuGetGallaryTemplate(count = 1){
		const template = document.getElementById('dpsfiwu_wcgallary_template').content.cloneNode(true);
		template.getElementById('dpsfiwu_wcgallary__COUNT__').id = "dpsfiwu_wcgallary" + count;
		template.getElementById('dpsfiwu_url_wrap__COUNT__').id = "dpsfiwu_url_wrap" + count;
		template.getElementById('dpsfiwu_url__COUNT__').setAttribute('data-id', count);
		template.getElementById('dpsfiwu_url__COUNT__').name = "dpsfiwu_wcgallary[" + count + "][url]";
		template.getElementById('dpsfiwu_url__COUNT__').id = "dpsfiwu_url" + count
		template.getElementById('dpsfiwu_preview__COUNT__').setAttribute('data-id', count);
		template.getElementById('dpsfiwu_preview__COUNT__').id = "dpsfiwu_preview" + count;
		template.getElementById('dpsfiwu_img_wrap__COUNT__').id = "dpsfiwu_img_wrap" + count;
		template.querySelector('.dpsfiwu_remove').setAttribute('data-id', count);
		template.getElementById('dpsfiwu_img__COUNT__').setAttribute('data-id', count);
		template.getElementById('dpsfiwu_img__COUNT__').id = "dpsfiwu_img" + count;
		return template;
	}

	jQuery(document).ready(function($){
		var counter = <?php echo absint( $count ); ?>;
		// Preview
		$(document).on("click", ".dpsfiwu_preview", function(e){
			e.preventDefault();
			counter = counter + 1;
			var new_element_str = '';
			var id = jQuery(this).data('id');
			imgUrl = $('#dpsfiwu_url'+id).val();

			if ( imgUrl != '' ){
				$("<img>", { // Url validation
					src: imgUrl,
					error: function() {$alert( '<?php esc_attr_e( 'Error URL Image', 'dpsfiwu' ); ?>' ) },
					load: function() {
						$('#dpsfiwu_img_wrap'+id).show();
						$('#dpsfiwu_img'+id).attr('src',imgUrl);
						$('#dpsfiwu_remove'+id).show();
						$('#dpsfiwu_url'+id).hide();
						$('#dpsfiwu_preview'+id).hide();
						$('#dpsfiwu_wcgallary_metabox_content').append( dpsfiwuGetGallaryTemplate(counter) );
					}
				});
			}
		});

		$(document).on("click", ".dpsfiwu_remove", function(e){
			var id2 = jQuery(this).data('id');

			e.preventDefault();
			$('#dpsfiwu_wcgallary'+id2).remove();
		});

	});
</script>
