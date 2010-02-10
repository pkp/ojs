// Grid settings button handler
$(function(){
	$('a.settings').live("click", (function() {
		$(this).parent().siblings('.row_controls').toggle(300);
	}));
});
