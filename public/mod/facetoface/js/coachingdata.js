$(document).ready(function() {
    // listen for changes to any form fields
    $(document).on('change','select, input[type="checkbox"], input[type="radio"]', function(e) {
      e.preventDefault();
      // show only the fields that are dependent on the selected options
      $('select, input[type="checkbox"], input[type="radio"]').each(function() {
        var formGroup = $(this).closest('.form-group');
        var fieldId = formGroup.attr('data-id');
  
        if ($(this).is(':checkbox') || $(this).is(':radio')) {
          if ($(this).is(':checked')) {
            var selectedOption = $(this).val();
            $('[data-dependency="' + fieldId + '-' + selectedOption + '"]').removeClass('d-none');
          } else {
            var selectedOption = $(this).val();
            $('[data-dependency="' + fieldId + '-' + selectedOption + '"]').addClass('d-none');
          }
        } else if ($(this).is('select')) {
          var selectedOption = $(this).val();
          $('[data-dependency^="' + fieldId + '-"]').addClass('d-none');
          if (selectedOption !== '') {
            $('[data-dependency="' + fieldId + '-' + selectedOption + '"]').removeClass('d-none');
          }
        }
      });
    });
  });
  