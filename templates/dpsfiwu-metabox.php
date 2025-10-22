<?php
/**
 * Featured Image with URL metabox Template.
 *
 * @package DPSFIWU
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$image_url = '';
$image_alt = '';
if ( isset( $image_meta['img_url'] ) && ! empty( $image_meta['img_url'] ) ) {
	$image_url = esc_url( $image_meta['img_url'] );
}
if ( isset( $image_meta['img_alt'] ) && ! empty( $image_meta['img_alt'] ) ) {
	$image_alt = esc_attr( $image_meta['img_alt'] );
}
?>

<div id="dpsfiwu_metabox_content" >
	<input
		id="dpsfiwu_url"
		type="text"
		name="dpsfiwu_url"
		placeholder="<?php esc_attr_e( 'Image URL', 'dpsfiwu' ); ?>"
		value="<?php echo esc_url( $image_url ); ?>"
	/>
	<a id="dpsfiwu_preview" class="button" >
		<?php esc_html_e( 'Preview', 'dpsfiwu' ); ?>
	</a>

	<input
		id="dpsfiwu_alt"
		type="text"
		name="dpsfiwu_alt"
		placeholder="<?php esc_attr_e( 'Alt text (Optional)', 'dpsfiwu' ); ?>"
		value="<?php echo esc_attr( $image_alt ); ?>"
	/>
	<div>
		<span id="dpsfiwu_noimg">
			<?php esc_html_e( 'No image', 'dpsfiwu' ); ?>
		</span>
		<img id="dpsfiwu_img" src="<?php echo esc_url( $image_url ); ?>" />
	</div>

	<a id="dpsfiwu_remove" class="button" style="margin-top:4px;">
		<?php esc_html_e( 'Remove Image', 'dpsfiwu' ); ?>
	</a>

	<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
		<h4 style="margin: 0 0 10px 0; font-size: 12px;"><?php esc_html_e( 'SEO & Social Media', 'dpsfiwu' ); ?></h4>

		<label for="dpsfiwu_og_title" style="display: block; margin-bottom: 5px; font-size: 12px;">
			<?php esc_html_e( 'OG Title (Optional):', 'dpsfiwu' ); ?>
		</label>
		<input
			id="dpsfiwu_og_title"
			type="text"
			name="dpsfiwu_og_title"
			placeholder="<?php esc_attr_e( 'Social media title', 'dpsfiwu' ); ?>"
			value="<?php echo isset( $image_meta['og_title'] ) ? esc_attr( $image_meta['og_title'] ) : ''; ?>"
			style="width: 100%; margin-bottom: 10px; font-size: 12px;"
		/>

		<label for="dpsfiwu_og_description" style="display: block; margin-bottom: 5px; font-size: 12px;">
			<?php esc_html_e( 'OG Description (Optional):', 'dpsfiwu' ); ?>
		</label>
		<textarea
			id="dpsfiwu_og_description"
			name="dpsfiwu_og_description"
			placeholder="<?php esc_attr_e( 'Social media description', 'dpsfiwu' ); ?>"
			style="width: 100%; height: 60px; margin-bottom: 10px; font-size: 12px; resize: vertical;"
		><?php echo isset( $image_meta['og_description'] ) ? esc_textarea( $image_meta['og_description'] ) : ''; ?></textarea>

		<?php if ( ! empty( $image_meta['img_url'] ) ) : ?>
		<a href="#" id="dpsfiwu_social_preview" class="button" style="font-size: 11px; padding: 3px 8px;">
			👁️ <?php esc_html_e( 'Preview Social Share', 'dpsfiwu' ); ?>
		</a>
		<?php endif; ?>
	</div>

	<?php wp_nonce_field( 'dpsfiwu_img_url_nonce_action', 'dpsfiwu_img_url_nonce' ); ?>
</div>

<script>
	jQuery(document).ready(function($){
		<?php if ( ! $image_meta['img_url'] ) : ?>
			$('#dpsfiwu_img').hide().attr('src','');
			$('#dpsfiwu_noimg').show();
			$('#dpsfiwu_alt').hide().val('');
			$('#dpsfiwu_remove').hide();
			$('#dpsfiwu_url').show().val('');
			$('#dpsfiwu_preview').show();
		<?php else : ?>
			$('#dpsfiwu_noimg').hide();
			$('#dpsfiwu_remove').show();
			$('#dpsfiwu_url').hide();
			$('#dpsfiwu_preview').hide();
		<?php endif; ?>

		// Preview Featured Image
		$('#dpsfiwu_preview').click(function(e){
			e.preventDefault();
			imgUrl = $('#dpsfiwu_url').val();
			if ( imgUrl != '' ){
				$("<img>", {
					src: imgUrl,
					error: function() { alert('<?php echo esc_js( __( 'Error URL Image', 'dpsfiwu' ) ); ?>') },
					load: function() {
						$('#dpsfiwu_img').show().attr('src',imgUrl);
						$('#dpsfiwu_noimg').hide();
						$('#dpsfiwu_alt').show();
						$('#dpsfiwu_remove').show();
						$('#dpsfiwu_url').hide();
						$('#dpsfiwu_preview').hide();
					}
				});
			}
		});

		// Remove Featured Image
		$('#dpsfiwu_remove').click(function(e){
			e.preventDefault();
			$('#dpsfiwu_img').hide().attr('src','');
			$('#dpsfiwu_noimg').show();
			$('#dpsfiwu_alt').hide().val('');
			$('#dpsfiwu_remove').hide();
			$('#dpsfiwu_url').show().val('');
			$('#dpsfiwu_preview').show();
		});

		// Social Share Preview
		$('#dpsfiwu_social_preview').click(function(e){
			e.preventDefault();

			var imageUrl = $('#dpsfiwu_img').attr('src');
			var ogTitle = $('#dpsfiwu_og_title').val() || $('input#title').val();
			var ogDescription = $('#dpsfiwu_og_description').val() || $('#excerpt').val();

			if (!ogDescription) {
				ogDescription = $('meta[name=description]').attr('content') || '';
			}

			// Create modal preview
			var modalHtml = '<div id="dpsfiwu-social-preview-modal" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:99999; overflow:auto;">' +
				'<div style="background:white; max-width:600px; margin:50px auto; padding:20px; border-radius:8px; position:relative;">' +
				'<span onclick="document.getElementById(\'dpsfiwu-social-preview-modal\').remove()" style="position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px; color:#999;">&times;</span>' +
				'<h3 style="margin:0 0 20px 0;"><?php esc_html_e( 'Social Media Preview', 'dpsfiwu' ); ?></h3>' +

				// Facebook Preview
				'<div style="margin-bottom:20px;">' +
				'<h4 style="margin:0 0 10px 0;"><?php esc_html_e( 'Facebook/Open Graph:', 'dpsfiwu' ); ?></h4>' +
				'<div style="border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff;">' +
				(imageUrl ? '<img src="' + imageUrl + '" style="width:100%; height:auto; max-height:300px; object-fit:cover; display:block;" alt="Preview">' : '') +
				'<div style="padding:15px;">' +
				'<div style="font-size:12px; color:#606770; margin-bottom:5px;"><?php echo esc_url( home_url() ); ?></div>' +
				'<div style="font-weight:600; font-size:16px; line-height:20px; margin-bottom:8px;">' + (ogTitle || '<?php esc_html_e( 'No title', 'dpsfiwu' ); ?>') + '</div>' +
				'<div style="font-size:14px; line-height:20px; color:#606770;">' + (ogDescription || '<?php esc_html_e( 'No description', 'dpsfiwu' ); ?>') + '</div>' +
				'</div>' +
				'</div>' +
				'</div>' +

				// Twitter Preview
				'<div style="margin-bottom:20px;">' +
				'<h4 style="margin:0 0 10px 0;"><?php esc_html_e( 'Twitter Card:', 'dpsfiwu' ); ?></h4>' +
				'<div style="border:1px solid #e1e8ed; border-radius:8px; overflow:hidden; background:#fff;">' +
				(imageUrl ? '<img src="' + imageUrl + '" style="width:100%; height:auto; max-height:280px; object-fit:cover; display:block;" alt="Preview">' : '') +
				'<div style="padding:15px;">' +
				'<div style="font-weight:700; font-size:15px; line-height:20px; margin-bottom:8px;">' + (ogTitle || '<?php esc_html_e( 'No title', 'dpsfiwu' ); ?>') + '</div>' +
				'<div style="font-size:14px; line-height:20px; color:#536471;">' + (ogDescription || '<?php esc_html_e( 'No description', 'dpsfiwu' ); ?>') + '</div>' +
				'</div>' +
				'</div>' +
				'</div>' +

				'<p><button onclick="document.getElementById(\'dpsfiwu-social-preview-modal\').remove()" style="background:#0073aa; color:white; border:none; padding:10px 20px; cursor:pointer; border-radius:4px;"><?php esc_html_e( 'Close', 'dpsfiwu' ); ?></button></p>' +
				'</div>' +
				'</div>';

			$('body').append(modalHtml);
		});
	});
</script>
