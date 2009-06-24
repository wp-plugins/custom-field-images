jQuery(function($) 
{
	var $box = $(document).find('#cfi-box');
	var $special_row = $box.find('tr:first');

	$(document).ready(function() {
		$special_row.hide();
	});

	function fillCfiBox() 
	{
		var $item = $(this).parents('.media-item');

		$special_row.show();
		$box.find('tr:eq(1)').hide();

		// Set cfi-id
		var id = $item.find(':submit').attr('name').replace(/^.*?(\d+).*?$/, '$1');
		$box.find('[name=cfi-id]').val(id);

		console.log($item.find(':submit').attr('name'));

		// Set cfi-size
		var size = $item.find('.image-size :checked').val();
		$box.find('[name=cfi-size]').val(size);

		// Set cfi-align
		var align = $item.find('.align :checked').val();
		if ( align != 'none' )
			$box.find('tr:last [value="'+align+'"]').attr('checked',true);
		else
			$box.find('tr:last :checked').attr('checked', false);	// uncheck all buttons

		// Set cfi-alt
		var alt = $item.find('.post_title :text').val();
		$box.find('[name=cfi-alt]').val(alt);

		// Set cfi-link
		var link = $item.find('.url :text').val();
		$box.find('[name=cfi-link]').val(link);

		$(document).find('#TB_closeWindowButton').click();		// close iframe

		return false;
	}

	function addButton() 
	{
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
		});
	}

	$('#add_image').click(function() {										// when invoking iframe
		$('#TB_iframeContent').livequery(function() {						// after creating iframe tag
			$('#TB_iframeContent').load(addButton); 						// after each tab load
		});
	});
});

