jQuery(function($) {
	button = ' <a class="insert-cfi" href="#">Insert CFI</a>';
	$('.media-item :submit').after(button);

	strpos = function ( haystack, needle, offset) {
		var i = (haystack+'').indexOf( needle, offset ); 
		return i===-1 ? false : i;
	}
	tk = '[cfi]';

	$('.insert-cfi').each(function(){
		$(this).click(function(){
			parent = $(this).parents('.media-item');

			title = parent.find('.post_title :text');

			if ( strpos(title.val(),tk) == false )
				title.val(title.val() + tk);

			parent.find(':submit').click();
		});
	});
});
