// Initialise plugins
$(function(){
	$('a.settings').live("click", (function() { // Initialize grid settings button handler
		$(this).parent().siblings('.row_controls').toggle(300);
	}));
});
