function fillCfiBox(anchor) {
	var item = anchor.parents('.media-item');
	var box = jQuery(document).find('#cfi-box');

	// Set cfi-url
	var url = item.find('.urlfile').attr('title');

	var size = item.find('.image-size :checked[value!=full]').parents('.image-size-item').find('.help').text();
	if ( size.length > 0 ) {
		size = size.replace(/^.*?(\d+).*?(\d+).*?$/, '$1x$2');
		url = url.replace(/(.*)\./, '$1-'+size+'.');
	}

	box.find('[name=cfi-url]').val(url);

	// Set cfi-align
	var align = item.find('.align :checked').val();
	if ( align != 'none' )
		box.find('tr:last [value="'+align+'"]').attr('checked',true);
	else
		box.find('tr:last :checked').attr('checked', false);	// uncheck all buttons

	// Set cfi-alt
	var alt = item.find('.post_title :text').val();
	box.find('[name=cfi-alt]').val(alt);

	// Set cfi-link
	var link = item.find('.url :text').val();
	box.find('[name=cfi-link]').val(link);

	jQuery(document).find('#TB_closeWindowButton').click();		// close iframe
}

jQuery(function($) {
	$('#add_image').click(function() {										// when invoking iframe
		$('#TB_iframeContent').load(function() { 							// after each tab load,
			var frame = $(this).contents();

			frame.find('.media-item :submit').each(function() {
				$(this).after(' (<a href="#" class="insert-cfi" style="color:#006505;">Insert CFI</a>)');
			});

			frame.find('.insert-cfi').each(function() {
				var anchor = $(this);

				anchor.click(function () {
					fillCfiBox(anchor);
				});
			});
		});
	});
});
