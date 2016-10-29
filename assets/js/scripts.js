;(function($){
	$(document).ready(function(){
		$('.color-field').wpColorPicker();
		$('input[name="toptal_ss_color"]').on('click', function(){
			if(!$(this).is(":checked")) {
				$(this).closest('tr').nextAll().fadeIn('slow');
			}
			else {
				$(this).closest('tr').nextAll().fadeOut('slow');
			}
		});
	});
})(jQuery);