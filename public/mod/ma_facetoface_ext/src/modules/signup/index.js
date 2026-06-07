console.log('module/signup/index.js loaded');

$('#id_profile_field_CollectionStatement').parent().parent().parent().find('.col-md-3').addClass('col-form-label');
$('#id_profile_field_CollectionStatement').parent().parent().addClass('felement');

function showCollectionStatementModal() {
  document.getElementById('id_profile_field_CollectionStatement').disabled = false;
  $('#collection_statement').modal('show');
}
var str =
  "I have read and understood the <a style='text-decoration: underline;' href='#' class='collection_statement'>Collection Statement</a> relating to the use of the Learning Management System and how DFFH will collect and use my personal information";
$('label[for="id_profile_field_CollectionStatement"]').html(str);
document.getElementById('id_profile_field_CollectionStatement').disabled = false;
document
  .querySelector('label[for="id_profile_field_CollectionStatement"] .collection_statement')
  .addEventListener('click', showCollectionStatementModal);
