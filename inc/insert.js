jQuery(function($) {
	$('#add_image').click(function(){ 										// when invoking iframe
		$('#TB_iframeContent').load(function() { 							// after each tab load,
			button = ' <a class="insert-cfi" href="#" title="Insert into the Custom Field Image box" style="color:#006505;">Insert CFI</a>';			
			frame = $(this).contents();

			frame.find('.media-item :submit').after(button);				// add button for each item
			frame.find('.insert-cfi').bind('click', insertCfi);				// bind function to button click
		});
	});

	insertCfi = function() {
		item = $(this).parents('.media-item');

		url = item.find('.urlfile').attr('title');
		$(document).find('#cfi-url').val(url);

		alt = item.find('.post_title :text').val();
		$(document).find('#cfi-alt').val(alt);

		link = item.find('.url :text').val();
		$(document).find('#cfi-link').val(link);

		align = item.find('.align :checked').val();
		$(document).find('#cfi-align [value="'+align+'"]').attr('checked',true);

		$(document).find('#TB_closeWindowButton').click();		// close iframe
	}
});
