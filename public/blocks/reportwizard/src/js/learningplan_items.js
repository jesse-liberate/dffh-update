function add_learningplan_item(){
	var counter = parseInt($('#row_counter').val()) + 1;
	$('.row_'+counter).show();
	$('#row_counter').val(counter);
	$('.row_'+counter+' input.date1[type=text]').each(function(){
		$(this).prop('required',true);
	});
}
function remove_learningplan_item(order,codeid){
	if(codeid==0){
		// New one
		$('.row_'+order).hide();
		$('.row_'+order+' input[type=text]').each(function(){
			$(this).remove();
			$(this).removeAttr('required');
		});
		$('.row_'+order).remove();
	}else{
		//The existing one
		var delete_str = $('#delete_item').val() + ","+codeid;
		$('.row'+order).hide();
		$('#delete_item').val(delete_str);
		$('.row_'+order+' input[type=text]').each(function(){
			$(this).removeAttr('required');
		});
		$('.row'+order).remove();
	}
}
$(function() {
});