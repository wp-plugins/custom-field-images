jQuery(function($) {
	$('#add_image').click(function() {										// when invoking iframe
		$('#TB_iframeContent').load(function() { 							// after each tab load,
			button = ' (<a class="insert-cfi" href="#" title="Insert into the Custom Field Image box" style="color:#006505;">Insert CFI</a>)';
			frame = $(this).contents();

			frame.find('.media-item :submit').after(button);				// add button for each item
			frame.find('.insert-cfi').click(insertCfi);						// bind function to button click
		});
	});

	insertCfi = function() {
		item = $(this).parents('.media-item');
		box = $(document).find('#cfi-box');

		// Set cfi-url
		url = item.find('.urlfile').attr('title');

		size = item.find('.image-size :checked[value!=full]').parents('.image-size-item').find('.help').text();
		if ( size.length > 0 ) {
			size = size.replace(/^.*?(\d+).*?(\d+).*?$/, '$1x$2');
			url = url.replace(/(.*)\./, '$1-'+size+'.');
		}

		box.find('[name=cfi-url]').val(url);

		// Set cfi-align
		align = item.find('.align :checked').val();
		if ( align != 'none' )
			box.find('tr:last [value="'+align+'"]').attr('checked',true);
		else
			box.find('tr:last :checked').attr('checked', false);	// uncheck all buttons

		// Set cfi-alt
		alt = item.find('.post_title :text').val();
		box.find('[name=cfi-alt]').val(alt);

		// Set cfi-link
		link = item.find('.url :text').val();
		box.find('[name=cfi-link]').val(link);

		$(document).find('#TB_closeWindowButton').click();		// close iframe
	}
});
