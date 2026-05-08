{php} print_string('generalresult', 'block_reporting') {/php}
<p>
  Applied filters: {$filters}
</p>
<p>{php} print_string('sorting_tip', 'block_reporting') {/php}</p>

<div style='text-align:right;margin-bottom:10px'>
  <a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">{php} print_string('export_exel_csv', 'block_reporting') {/php}</a>
</div>
<table id="report" class="tablesorter">
  <thead>
    <tr>
      {section name=th loop=$headers} 
        <th><strong>{$headers[th]}</strong></th>
      {/section}
    </tr>
  </thead>
</table>
<div style='text-align:right'>
  <a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">{php} print_string('export_exel_csv', 'block_reporting') {/php}</a>
</div>


{literal}
  <script>
    $(document).ready(function () {
      
      var url = "{/literal}{$ajax_url}{literal}" + location.search;
      console.log(url);
      
      var table = $('#report').DataTable({
        //"iDisplayLength": 10,
        "processing": true,
        "serverSide": true,
        "paging": true,
        "ordering": true,
        "pageLength": 100,
        "language": {
           "processing": "Loading ...",
           "info": "Showing _START_ to _END_ of _TOTAL_ records",
           "lengthMenu": "Show _MENU_ records",
           paginate: {
             previous: '<',
             next: '>',
           },
        },
        //"oLanguage": {
        //  "sLengthMenu": "Show _MENU_ records"
        //},
        "ajax": {
          "url": url,
          "dataSrc": function(json) {
            console.log('make ajax call');
            //console.log(json);
            return json.data;
          }
        },
        //"deferLoading": 500,
        //"deferRender": true,
        "lengthMenu": [ [100, 500, 1000, 5000], [100, 500, 1000, 5000] ],
        //"dom": '<lpf<t>lpf>'
        //"dom": '<"info"i><"top"flp>rt<"bottom"flp><"info"i>'
        "dom": '<"dataTables-top"<"clearfix"<"pull-left"i>><"clearfix"<"pull-left"l><"pull-right"p>>>rt' +
               '<"dataTables-bottom"<"clearfix"<"pull-left"l><"pull-right"p>><"clearfix"<"pull-left"i>>>'
        
        //"dom": '<i<"top"flp<"clear">>>rt<<"bottom"flp>i<"clear">>>'
      });
      
      $('#report').on('page.dt', function() {
        var info = table.page.info();
        console.log('Showing page: '+info.page+' of '+info.pages);
      });

      // This must be a hyperlink
      $(".export").on('click', function (event) {
        // CSV
        //exportTableToCSV.apply(this, [$('#report'), 'report.csv']);

        var url = location.href;
        url = url.replace('type=HTML', 'type=CSV');
        window.open(url);
        // IF CSV, don't do event.preventDefault() or return false
        // We actually need this to be a typical hyperlink
      });  
    });

  </script>      
{/literal}
