<style id="css">

  {literal}
    /* tables */
    table.tablesorter {
      font-family:arial;
      background-color: #CDCDCD;
      margin:10px 0pt 15px;
      font-size: 8pt;
      width: 100%;
      text-align: left;
    }
    table.tablesorter thead tr th, table.tablesorter tfoot tr th {
      background-color: #e6EEEE;
      border: 1px solid #000;
      font-size: 8pt;
      padding: 4px;
    }
    table.tablesorter thead tr .header {
      background-image: url(css/bg.gif);
      background-repeat: no-repeat;
      background-position: center right;
      cursor: pointer;
    }
    table.tablesorter tbody td {
      color: #3D3D3D;
      padding: 4px;
      background-color: #FFF;
      vertical-align: top;
    }
    table.tablesorter tbody tr.odd td {
      background-color:#F0F0F6;
    }
    table.tablesorter thead tr .headerSortUp {
      background-image: url(css/asc.gif);
    }
    table.tablesorter thead tr .headerSortDown {
      background-image: url(css/desc.gif);
    }
    table.tablesorter thead tr .headerSortDown, table.tablesorter thead tr .headerSortUp {
      background-color: #8dbdd8;

    }
  {/literal}

  {literal}
    .report_progress_text {
      height: 20px;
      color: #fff;
      /* display: inline-block; */ /* <-- remove this from the code */
      line-height: 20px;
      width: 100%;
      text-shadow: 0px 0px 3px #000;
      text-shadow: 0px 0px 3px rgba(0,0,0,0.5);
      text-align: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 200px;
    }

    .report_progress_bar {
      /*border: 0px solid white; */ /* <-- remove this from the code */
      height: 20px;
      /*display: inline-block;*/ /* <-- remove this from the code */
      width: 100%;
      background:{/literal} {$bgcolor_courseoverview} {literal};  /* big background*/
      -webkit-border-radius: 4px;
      -moz-border-radius: 4px;
      border-radius: 4px;
      padding: 0px 1px;
    }

    .report_progress_percentage {
      height: 18px;
      margin-top: -19px;
      background-color: {/literal} {$percentage_bgcolor_courseoverview} {literal}; /* percentage*/
      -webkit-border-radius: 3px;
      -moz-border-radius: 3px;
      border-radius: 3px;
    }
    #diagrambottom{
      display: table-caption;
    }
    #coursecompletion{
      float:right;
    }
    #canvas-holder{
      position:relative;
      width: 100%;
    }
    #canvas-holder canvas{
      float: left;
      clear: right;
    }
    .abc{
      float: left;
      width: 300px;
      text-align: center;
      clear: left;
    }
    #diagramleft{
      width: 300px;
      float: left;
      vertical-align: center;
    }		
    #diagramright{
      width: {/literal}{$bar_graph_width}{literal};
      height: 300px;
      float: left;
      vertical-align: center;
    }
    #coursecompleted{
      background-color: {/literal} {$pie_color_completed} {literal};
      width: 20px;
      height: 20px;
      display: inline-block;
      margin-left: 10px;
      margin-right: 2px;
    }
    #coursenotcompleted{
      background-color: {/literal} {$pie_color_not_completed} {literal};
      width: 20px;
      height: 20px;
      display: inline-block;
      margin-left: 10px;
      margin-right: 2px;
    }
    @media(max-width: 957px){
      #coursecompletion{
        float:inherit;
      }
    }
  {/literal}
</style>
<!-- <div id="course_completion_diagram_value_totalenrolled"style="display:none">{$course_completion_diagram_value_totalenrolled}</div> -->

<div id="course_completion_diagram_value" style="display:none">{$course_completion_diagram_value}</div>
<div id="course_completion_diagram_value_string"style="display:none">{$course_completion_diagram_value_string}</div>
<div id="course_not_completion_diagram_value_string"style="display:none">{$course_not_completion_diagram_value_string}</div>
<div id="course_completion_diagram_value_coursename"style="display:none">{$course_completion_diagram_value_coursename}</div>
<div id="total_overall_diagram_value_true" name="Completed"style="display:none">{$total_overall_diagram_value_true}</div>
<div id="total_overall_diagram_value_false"name="Not Completed"style="display:none">{$total_overall_diagram_value_false}</div>


<div id="course_completion_diagram_value" style="display:none">{$course_completion_diagram_value}</div>
<div id="course_brand_completion_diagram_value_string"style="display:none">{$course_brand_completion_diagram_value_string}</div>

<div id="course_brand_completion_diagram_value_names"style="display:none">{$course_brand_completion_diagram_value_names}</div>

<div id="total_overall_diagram_value_true" name="Completed"style="display:none">{$total_overall_diagram_value_true}</div>
<div id="total_overall_diagram_value_false"name="Not Completed"style="display:none">{$total_overall_diagram_value_false}</div>

<div id="pie_color_completed"name="pie_color_completed"style="display:none">{$pie_color_completed}</div>
<div id="pie_color_not_completed"name="pie_color_not_completed"style="display:none">{$pie_color_not_completed}</div>
<div id="pie_highlightcolor_completed"name="pie_highlightcolor_completed"style="display:none">{$pie_highlightcolor_completed}</div>
<div id="pie_highlightcolor_not_completed"name="pie_highlightcolor_not_completed"style="display:none">{$pie_highlightcolor_not_completed}</div>
<div id="bar_color_completed"name="bar_color_completed"style="display:none">{$bar_color_completed}</div>
<div id="bar_color_not_completed"name="bar_color_not_completed"style="display:none">{$bar_color_not_completed}</div>
<div id="canvas-holder">


</div>

<p>
  Applied filters: {$filters}
</p>

{php} print_string('courseoverviewresult', 'block_reporting') {/php}
{* <div id='coursenotcompleted'> </div> {php} print_string('not_completed', 'block_reporting') {/php} <div id='coursecompleted'> </div>{php} print_string('completed', 'block_reporting') {/php} *}

<div id="canvas-holder">

  <div id="diagramleft">
    <div class='abc'>{php} print_string('overall_progress', 'block_reporting') {/php}</div>
    <canvas id="overallcompletion" width="300" height="300"></canvas>
  </div>	
  <div id="diagramright">
    <div class='abc'>{php} print_string('course_progress', 'block_reporting') {/php}</div>
    <canvas id="coursecompletion" width="350" height="350"></canvas>

  </div>
  <div id="diagrambottom">
    <div class='abc'>{php} print_string('selected_hierarchy_progress', 'block_reporting') {/php}</div>
    <canvas id="coursebrandcompletion" width="350" height="350"></canvas>
  </div>
  <div class="clearfix"></div>
</div>

<div style='float:left'>
  <br>
  <p>{php} print_string('sorting_tip', 'block_reporting') {/php}</p>
</div>

<div class="clearfix"></div>

{* {if isset($max_limit_alert)}
  <p style='color: #ec1c24;'>{$max_limit_alert}</p>
{/if} *}

<div id='dvData'>
{* {if $record_count == 0}
  No records found.
{else} *}
  <div style='float:right;margin-bottom:10px'><a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">{php} print_string('export_exel_csv', 'block_reporting') {/php}</a> </div>
  
   <p>Total Records: {$record_count}</p>
  <table id="report" class="tablesorter">
    <thead>
      <tr>
        {section name=th loop=$headers} 
          <th><strong>{$headers[th]}</strong></th>
        {/section}
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>
  <div style='text-align:right'><a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">{php} print_string('export_exel_csv', 'block_reporting') {/php}</a> </div>
{* {/if} *}
</div>


{literal}
  <script>
    var total_overall_diagram_value_true = document.getElementById("total_overall_diagram_value_true").innerHTML;
    var total_overall_diagram_value_false = document.getElementById("total_overall_diagram_value_false").innerHTML;
    var pie_color_completed = document.getElementById("pie_color_completed").innerHTML;
    var pie_highlightcolor_completed = document.getElementById("pie_highlightcolor_completed").innerHTML;
    var pie_color_not_completed = document.getElementById("pie_color_not_completed").innerHTML;
    var pie_highlightcolor_not_completed = document.getElementById("pie_highlightcolor_not_completed").innerHTML;

    
    var data = {
      datasets: [{
          data: [total_overall_diagram_value_true, total_overall_diagram_value_false],
          backgroundColor: [pie_color_completed, pie_color_not_completed]
        }],
      labels: ['Completed', 'Not Completed']
    };

    // Get context with jQuery - using jQuery's .get() method.
    var ctx = $("#overallcompletion").get(0).getContext("2d");
    // This will get the first returned node in the jQuery collection.
    $(window).on('load', function () {
      if (document.readyState != 'complete') {
        // chrome / safari will trigger load function before images are finished
        // check readystate in safari browser to ensure images are done loading
        setTimeout(arguments.callee, 100);
        return;
      }
      window.myPie = new Chart(ctx, {
        type: 'pie',
        data: data,
        options: {
          legend: {
            onClick: (e) => e.stopPropagation()
          }
        }
      });
    });

  </script>
{/literal}
{literal}
  <script>
    $(document).ready(function () {
      var course_completion_diagram_value_string = document.getElementById("course_completion_diagram_value_string").innerHTML;
      var course_not_completion_diagram_value_string = document.getElementById("course_not_completion_diagram_value_string").innerHTML;
      var course_completion_diagram_value_coursename = document.getElementById("course_completion_diagram_value_coursename").innerHTML;
      course_completion_diagram_value_coursename = course_completion_diagram_value_coursename.slice(1,-1);

      // var course_completion_diagram_value = document.getElementById("course_completion_diagram_value").innerHTML;
      var bar_color_completed = document.getElementById("bar_color_completed").innerHTML;
      var bar_color_not_completed = document.getElementById("bar_color_not_completed").innerHTML;
      // console.log(course_completion_diagram_value);

      // For brand
      var course_brand_completion_diagram_value_string = document.getElementById("course_brand_completion_diagram_value_string").innerHTML;
      var course_brand_completion_diagram_value_names = document.getElementById("course_brand_completion_diagram_value_names").innerHTML;
      // var course_brand_completion_diagram_value = document.getElementById("course_completion_diagram_value").innerHTML;
      if (course_brand_completion_diagram_value_string == "")
        document.getElementById('diagrambottom').style.display = "none";

      var bardata = {
        labels: course_completion_diagram_value_coursename.split('","'),
        datasets: [
          {
            label: 'Course Completed',
            backgroundColor: bar_color_completed,
            data: course_completion_diagram_value_string.split(",")
          }
        ]
      };
      
      var abc = document.getElementById("coursecompletion").getContext("2d");
      $(window).on('load', function () {
        if (document.readyState != 'complete') {
          // chrome / safari will trigger load function before images are finished
          // check readystate in safari browser to ensure images are done loading
          setTimeout(arguments.callee, 100);
          return;
        }
        window.myBarChart = new Chart(abc, {
          type: 'bar',
          data: bardata,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              yAxes: [{
                  ticks: {
                    beginAtZero: true,
                    suggestedMax: 100
                  }
                }],
              xAxes: [{
                  maxBarThickness: 20,
                  ticks: {
                    autoSkip: false
                  },
                }]
            },
            legend: {
              onClick: (e) => e.stopPropagation()
            }
          }
        });
      });

      var branddata = {
        labels: course_brand_completion_diagram_value_names.split(","),
        datasets: [
          {
            label: 'Node Completed',
            backgroundColor: bar_color_completed,
            data: course_brand_completion_diagram_value_string.split(",")
          }
        ]
      };
      var brands = document.getElementById("coursebrandcompletion").getContext("2d");
      $(window).on('load', function () {
        if (document.readyState != 'complete') {
          // chrome / safari will trigger load function before images are finished
          // check readystate in safari browser to ensure images are done loading
          setTimeout(arguments.callee, 100);
          return;
        }
        window.myBarChart = new Chart(brands, {
          type: 'bar',
          data: branddata,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              yAxes: [{
                  ticks: {
                    beginAtZero: true,
                    suggestedMax: 100
                  }
                }],
              xAxes: [{
                  maxBarThickness: 20,
                  ticks: {
                    autoSkip: false
                  }
                }]
            },
            legend: {
              onClick: (e) => e.stopPropagation()
            }
          }
        });
      });

    });


  </script>
{/literal}

{literal}
  <script>

    var url = "{/literal}{$ajax_url}{literal}" + location.search;
      console.log(url);
      
      var table = $('#report').DataTable({
        //"iDisplayLength": 10,
        "processing": true,
        "serverSide": true,
        "paging": true,
        "ordering": true,
        "pageLength": 500,
        "language": {
           "processing": "{/literal}{$pre_loading_img}{literal}",
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
        var url = location.href;
        url = url.replace('type=HTML', 'type=CSV');
        window.open(url);
      });

  </script>        
{/literal}