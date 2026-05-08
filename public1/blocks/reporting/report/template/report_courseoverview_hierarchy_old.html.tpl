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
      padding-right:50px;
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
      width: 400px;
      float: left;
      vertical-align: center;
    }		
    #diagramright{
      width: 400px;
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
        padding-right:50px;
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
{*<div id='coursenotcompleted'> </div> {php} print_string('not_completed', 'block_reporting') {/php} <div id='coursecompleted'> </div>{php} print_string('completed', 'block_reporting') {/php}*}

<div id="canvas-holder">

  <div id="diagramleft">
    <div class='abc'>{php} print_string('overall_progress', 'block_reporting') {/php}</div>
    <canvas id="overallcompletion" width="300" height="300"></canvas>
  </div>	
  <div id="diagramright">
    <div class='abc'>{php} print_string('course_progress', 'block_reporting') {/php}s</div>
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

{if isset($max_limit_alert)}
  <p style='color: #ec1c24;'>{$max_limit_alert}</p>
{/if}

<div id='dvData'>
{if $record_count == 0}
  No records found.
{else}
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
      {foreach from=$rows item=row}
      <tr>
        <td style="border-width:1px;">
          {$row[0]}
        </td>
        <td style="border-width:1px;">
          <div class="report_progress_bar">
            <div class="report_progress_text" name="{$row[2]}">
              {$row[1]} <b>({$row[2]}%)</b>
            </div>
            <div class="report_progress_percentage" role="report_progress_bar" style="width:{$row[2]}">

            </div>
          </div>
        </td>
        <td style="border-width:1px;">
          {$row[3]}
        </td>
        <td style="border-width:1px;">
          {$row[4]}
        </td>

        {section name=i start=5 loop=$row step=1}
          <td style="border-width:1px;">
            {$row[i]}
          </td>
        {/section}
      </tr>	
      {/foreach}
    </tbody>
  </table>
  <div style='text-align:right'><a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">{php} print_string('export_exel_csv', 'block_reporting') {/php}</a> </div>
{/if}
</div>


{literal}
  <script>
    var total_overall_diagram_value_true = document.getElementById("total_overall_diagram_value_true").innerHTML;
    var total_overall_diagram_value_false = document.getElementById("total_overall_diagram_value_false").innerHTML;
    var pie_color_completed = document.getElementById("pie_color_completed").innerHTML;
    var pie_highlightcolor_completed = document.getElementById("pie_highlightcolor_completed").innerHTML;
    var pie_color_not_completed = document.getElementById("pie_color_not_completed").innerHTML;
    var pie_highlightcolor_not_completed = document.getElementById("pie_highlightcolor_not_completed").innerHTML;

    // var data = [
    // 	    {
    // 	        value: total_overall_diagram_value_true,
    // 	        color: pie_color_completed,
    // 	        highlight: pie_highlightcolor_completed,
    // 	        label: "Completed"
    // 	    },
    // 	    {
    // 	        value: total_overall_diagram_value_false,
    // 	        color: pie_color_not_completed,
    // 	        highlight: pie_highlightcolor_not_completed,
    // 	        label: "Not Completed"
    // 	    },
    // 	];
    var data = {
      datasets: [{
          data: [total_overall_diagram_value_true, total_overall_diagram_value_false],
          backgroundColor: [pie_color_completed, pie_color_not_completed]
        }],
      labels: ['Completed', 'Not Completed']
    };
    // var options = [{
    // 	//Boolean - Whether we should show a stroke on each segment
    // }];

    // var ctx = document.getElementById("overallcompletion").getContext("2d");

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


      // all courses bar chart ==================
      // var bardata = {
      //    labels: course_completion_diagram_value_coursename.split(","),
      //    datasets : [
      //        {
      //            fillColor : bar_color_completed,
      //            strokeColor : bar_color_completed,
      //            highlightFill : bar_color_completed,
      //            highlightStroke : bar_color_completed,
      //            data : course_completion_diagram_value_string.split(",")
      //        }
      //    ]
      // };
      var bardata = {
        labels: course_completion_diagram_value_coursename.split(","),
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
            scales: {
              yAxes: [{
                  ticks: {
                    beginAtZero: true
                  }
                }],
              xAxes: [{
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

      // Selected nodes bar chart =================
      // var branddata = {
      //     labels: course_brand_completion_diagram_value_names.split(","),
      //     datasets : [
      //         {
      //             fillColor : bar_color_completed,
      //             strokeColor : bar_color_completed,
      //             highlightFill : bar_color_completed,
      //             highlightStroke : bar_color_completed,
      //             data : course_brand_completion_diagram_value_string.split(",")
      //         }
      //     ]
      // };
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
            scales: {
              yAxes: [{
                  ticks: {
                    beginAtZero: true
                  }
                }],
              xAxes: [{
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
    $(".export").on('click', function (event) {
      var url = location.href;
      url = url.replace('type=HTML', 'type=CSV');
      // alert(unescape(location.href));
      window.open(url);
    });

    $.tablesorter.addParser({
      id: "customDate",
      is: function (s) {
        // return s.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, [0-9]{4}|'?[0-9]{2}$/));
        return false;
      },
      format: function (s) {
        var date = s.split('/');
        return $.tablesorter.formatFloat(new Date(date[2], date[1], date[0]).getTime());
      },
      type: "numeric"
    });

    $(document).ready(function () {
      $("#report").tablesorter({
        headers:
                {
                  2: {sorter: "customDate"},
                  3: {sorter: "customDate"},
  {/literal} {$date_field_script}{literal}
                },
        sortList: [[0,0],[1,0]],
        widgets: ['zebra']
      });
    });

  </script>        
{/literal}