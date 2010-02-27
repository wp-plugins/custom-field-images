jQuery(document).ready(function($){
	var $box = $(document).find('#cfi-box'),
		$id_row = $box.find('tr:eq(0)'),
		$url_row = $box.find('tr:eq(1)');

	if ( $id_row.find(':text').val() == '' )
		$id_row.hide();
	else
		$url_row.hide();

	var fillCfiBox = function()
	{
		var $item = $(this).parents('.media-item');

		$id_row.show();
		$url_row.hide();

		// Set cfi-id
		var id = $item.find('input:submit').attr('name').replace(/^.*?(\d+).*?$/, '$1');
		$box.find('[name=cfi-id]').val(id);

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

	var addButton = function()
	{
		$('.media-item input:submit', $(this).contents()).livequery(function() {
			if ( $(this).find(' + .insert-cfi').length > 0 )
				return;

			$(this).after(' &nbsp;(<a class="insert-cfi" href="#">' + cfiL10n.insert_text + '</a>)');

			$(this).find(' + .insert-cfi')
				.css('color', '#006505')
				.click(fillCfiBox);
		});
	}

	$('#add_image').click(function() {										// when invoking iframe
		$('#TB_iframeContent').livequery(function() {						// after creating iframe tag
			$('#TB_iframeContent').load(addButton); 						// after each tab load
		});
	});
});

