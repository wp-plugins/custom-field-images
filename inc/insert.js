function fillCfiBox() {
	var item = jQuery(this).parents('.media-item');
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

	return false;
}

jQuery(function($) {
	$('#add_image').click(function() {										// when invoking iframe
		$('#TB_iframeContent').livequery(function() {						// after creating iframe tag
			$('#TB_iframeContent').load(function() { 						// after each tab load,
				button = $('<a>')
					.attr('href', '#')
					.css('color', '#006505')
					.text('Insert CFI')
					.addClass('insert-cfi')
					.click(fillCfiBox);

				$('.media-item :submit', $(this).contents()).livequery(function() {
					if ( $(this).find(' + .insert-cfi').length > 0 )
						return;

					button.clone(true)
						.insertAfter($(this))
						.before(' &nbsp;(')
						.after(')');
					console.log('added');
				});
			});
		});
	});
});

