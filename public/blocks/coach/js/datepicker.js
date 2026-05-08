
$(function(){
	
	// General Reports
	//if ($(".datepicker")[0]){
    $('head').append('<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/base/jquery-ui.css" type="text/css" media="all">');
	//}
	$(".datepicker").datepicker({ dateFormat: 'dd/mm/yy' });

	//Individual Reports
	//if ($(".autocomplete")[0]){
		$('head').append('<link rel=\"stylesheet\" href=\"http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css\" />');
		$('head').append('<style>.ui-autocomplete { max-height: 100px; overflow-y: auto; overflow-x: hidden; padding-right: 20px; } * html .ui-autocomplete { height: 100px; }</style>');
	//}
	$(".autocomplete#name").autocomplete({ source:'get_names_list.php' }); // /blocks/reporting/
	
	
	$("#tabs").tabs();

	$(".chzn-select").chosen({search_contains: true});

});