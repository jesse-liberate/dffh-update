$( document ).ready(function() {
    $(document).on('change','.coach-test', function(e) {
      e.preventDefault();
        if($(this).is(':checked')){
          
          console.log('checked');
          $("#coach").removeAttr("disabled");
        } else if(document.querySelectorAll('.coach-test:checked').length == 0) {
          $("#coach").attr("disabled",true);
        }
  });
});
