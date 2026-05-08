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
		.progress_bar{
		    border: 0px solid white;
		    height: 21px;
		    display: inline-block;
		    width: 100%;
		    background:{/literal} {$bgcolor_courseoverview} {literal};  // big background
		    -webkit-border-radius: 20px;
		    -moz-border-radius: 20px;
		    border-radius: 20px;
		    padding: 0px;
			}
		.progress_text {
		    height: 20px;
		    color: #fff;
		    display: inline-block;
		    line-height: 20px;
		    width: 100%;
		    text-align: center;
		}
		.progress_percentage {
		    height: 19px;
		    margin-top: -19px;
		    background-color: {/literal} {$percentage_bgcolor_courseoverview} {literal}; // percentage
		    -webkit-border-radius: 20px;
		    -moz-border-radius: 20px;
		    border-radius: 20px;
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
			vertical-align: center;
			display: table-caption;
		}		

		#overallcompletion{
			
		}
		#coursecompletion{
			padding-right:50px;
			float:right;
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
<div id="course_completion_diagram_value_totalenrolled"style="display:none">{$course_completion_diagram_value_totalenrolled}</div>
<div id="course_completion_diagram_value" style="display:none">{$course_completion_diagram_value}</div>
<div id="course_completion_diagram_value_string"style="display:none">{$course_completion_diagram_value_string}</div>
<div id="course_completion_diagram_value_coursename"style="display:none">{$course_completion_diagram_value_coursename}</div>
<div id="total_overall_diagram_value_true" name="Completed"style="display:none">{$total_overall_diagram_value_true}</div>
<div id="total_overall_diagram_value_false"name="Not Completed"style="display:none">{$total_overall_diagram_value_false}</div>

<div id="pie_color_completed"name="pie_color_completed"style="display:none">{$pie_color_completed}</div>
<div id="pie_color_not_completed"name="pie_color_not_completed"style="display:none">{$pie_color_not_completed}</div>
<div id="pie_highlightcolor_completed"name="pie_highlightcolor_completed"style="display:none">{$pie_highlightcolor_completed}</div>
<div id="pie_highlightcolor_not_completed"name="pie_highlightcolor_not_completed"style="display:none">{$pie_highlightcolor_not_completed}</div>

<div id="canvas-holder">

	</div>
{php}
	echo"<p>Applied filters:";
	if(isset($_REQUEST["course"]) && $_REQUEST["course"]!="") echo" Course - <strong>" . $_REQUEST["course"] . "</strong>;";
	if(isset($_REQUEST["completionstatus"]) && $_REQUEST["completionstatus"]!="" && $_REQUEST["completionstatus"]!= 0) { 
		switch ($_REQUEST["completionstatus"]) {
			case 1: $status = 'Not Completed'; break;
			case 2: $status = 'Completed'; break;
		}
		echo" Status - <strong>" . $status . "</strong>;";
	}
	if(isset($_REQUEST["enrolleddate"]) && $_REQUEST["enrolleddate"]!="") echo" Enrolled date - <strong>" . $_REQUEST["enrolleddate"] . "</strong>;";
	if(isset($_REQUEST["completiondate"]) && $_REQUEST["completiondate"]!="") echo" Completion date - <strong>" . $_REQUEST["completiondate"] . "</strong>;";
	$filters_array = $this->get_template_vars('filters_array');
	foreach ($filters_array as $filter_name=>$is_result_applied) {
		$actual_filter_name = strtolower(str_replace(" ","_", $filter_name));
		if(isset($_REQUEST[$actual_filter_name]) && $_REQUEST[$actual_filter_name]!="") {
			echo " " . $filter_name . " - <strong>" . $_REQUEST[$actual_filter_name] . "</strong>;";
		}
	}
	if(isset($_REQUEST["name"]) && ($_REQUEST["name"]!="")) {
		echo" User - <strong>" . $_REQUEST["name"] . "</strong>;";
	}
	$gradecomparation = get_get('gradecomparation');
	$gradeinputs = get_get('gradeinputs');
	$general_filter_records = $this->get_template_vars('general_filter_records');
	if((isset($gradeinputs)||!empty($gradeinputs)) && $gradeinputs != ""){
		foreach($general_filter_records as $filteritem){
		echo $filteritem->filtername ." ".$gradecomparation ." ". $gradeinputs ."";
		}
	}
	
		
	

	echo"</p>";
{/php}
<h1>Individual Results</h1>
<div id="canvas-holder">
	{* <div id='coursenotcompleted'> </div> Not completed <div id='coursecompleted'> </div>Completed *}
	<div id="diagramleft">
		<div class='abc'><br>Course Overview Diagram</div>
		<canvas id="overallcompletion" width="300" height="300"></canvas>
	</div>	
	<div class="clearfix"></div>
</div>

<div style='float:left'>
<br>
<p style='display: inline-block;'>Tip: Sort multiple columns simultaneously by holding down the shift key and clicking a second, third or even fourth column header!</p>
</div>
<div style='float:right;margin-bottom:10px'><a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">Export Excel/CSV</a> </div>
<div class="clearfix"></div>
<div id='dvData'>
<table id="report" class="tablesorter">
 	<thead>
		<tr>
			<th>Full name</th>
			<th>{php}print_string('course_name', 'block_reporting'){/php}</th>
			<th>Module</th>
			<th>Completion</th>
			<th>Enrolled Date</th>
			<th>Completion Date</th>
			{foreach from=$filters_array key=filter_name item=is_result_applied}
				<th>{$is_result_applied->record->name}</th>
	  		{/foreach}
	  		{foreach from=$general_filter_records key=filtername item=is_result_applied}
				<th>{$is_result_applied->name}</th>
	  		{/foreach}
		</tr>
	</thead>
	<tbody>
	{foreach from=$userinfo_row item=user key=user_id}
		{foreach from=$user item=course_module key=course_module_id}
			<tr>
				<td style="border-width:1px;">
				<a href="../../../user/profile.php?id={$user_id}">
					{$course_module.firstname} {$course_module.lastname}
				</a>
				</td>
				<td style="border-width:1px;">
					<a href="../../../course/view.php?id={$course_module.courseid}">
						{$course_module.coursename|truncate:50:"...":true}
					</a>
				</td>
				<td style="border-width:1px;">
					{foreach from=$course_module.modulename item=name}
						{$name}
					{/foreach}
				</td>
				<td style="border-width:1px;">
						{if $course_module.completionstatus == 1}
								Completed
						{elseif $course_module.completionstatus == 2}
								Completed
						{elseif $course_module.scormstatus == 'passed'}
								Completed
						{elseif $course_module.scormstatus == 'completed'}
								Completed
						{elseif $course_module.scormstatus == 'incomplete'}
								In Progress
						{else}
							Not Completed
						{/if}

				</td>
				<td style="border-width:1px;">
					{if $course_module.enrolleddate != ''}
						{$course_module.enrolleddate|date_format:"%d/%m/%Y"}
					{/if}
				</td>
				<td style="border-width:1px;">
		        	{if $course_module.completiondate != ''}
						{$course_module.completiondate|date_format:"%d/%m/%Y"}
					{/if}
			  	</td>
			  	
			  	{foreach from = $course_module.profile_result key=abc item=result}
			  		
					{if $result.type === 'datetime'}
						<td style="border-width:1px;">{$result.value|date_format:"%d/%m/%Y"}</td>
					{else}
						<td style="border-width:1px;">{$result.value}</td>
					{/if}
				{/foreach}

				{if $course_module.grade !=''}
		  			 <td style="border-width:1px;">
					{$course_module.grade|string_format:"%.2f"}
					</td>
				{/if}
			</tr>
		{/foreach}
	{/foreach}
  </tbody>

</table>
</div>
<div style='text-align:right'><a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">Export Excel/CSV</a> </div>
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
    // 	    }
    // 	];
    var data = {
        datasets: [{
            data: [total_overall_diagram_value_true, total_overall_diagram_value_false],
            backgroundColor: [pie_color_completed, pie_color_not_completed]
        }],
        labels: ['Completed', 'Not Completed']
    };

    // var options = {
    //     //Boolean - Whether we should show a stroke on each segment
    //     segmentShowStroke : true,
    //
    //     //String - The colour of each segment stroke
    //     segmentStrokeColor : "#fff",
    //
    //     //Number - The width of each segment stroke
    //     segmentStrokeWidth : 2,
    //
    //     //Number - The percentage of the chart that we cut out of the middle
    //     cutoutPercentage : 50, // This is 0 for Pie charts
    //
    //     //Number - Amount of animation steps
    //     animationSteps : 100,
    //
    //     //String - Animation easing effect
    //     animationEasing : "easeOutBounce",
    //
    //     //Boolean - Whether we animate the rotation of the Doughnut
    //     animateRotate : true,
    //
    //     //Boolean - Whether we animate scaling the Doughnut from the centre
    //     animateScale : false,
    //
    // };

    // var ctx = document.getElementById("overallcompletion").getContext("2d");

    // Get context with jQuery - using jQuery's .get() method.
    var ctx = $("#overallcompletion").get(0).getContext("2d");
    // This will get the first returned node in the jQuery collection.
    $(window).on('load',function(){
        if (document.readyState != 'complete'){
            // chrome / safari will trigger load function before images are finished
            // check readystate in safari browser to ensure images are done loading
            setTimeout( arguments.callee, 100 );
            return;
        }
        window.myPie = new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                legend: {
                    onClick: function(e) {
						e.stopPropagation();
					}
            	}
        	}
        });
    });
</script>

{/literal}
{literal}
 <script>
 (function($){
	$(document).ready(function(){
		function exportTableToCSV($table, filename) {

		    var $rows = $table.find('tr'),
		    //var $rows = $table.find('tr:has(td)'),

		        // Temporary delimiter characters unlikely to be typed by keyboard
		        // This is to avoid accidentally splitting the actual contents
		        tmpColDelim = String.fromCharCode(11), // vertical tab character
		        tmpRowDelim = String.fromCharCode(0), // null character

		        // actual delimiter characters for CSV format
		        colDelim = '","',
		        rowDelim = '"\r\n"',

		        // Grab text from table into CSV formatted string
		        csv = '"' + $rows.map(function (i, row) {
		            var $row = $(row);
		            var $cols;
		            if($row.find('th').text()=="") $cols = $row.find('td');
		            else $cols = $row.find('th');

		            return $cols.map(function (j, col) {
		                var $col = $(col),
		                    text = $col.text();
		                    text = text.trim();

		                return text.replace(/"/g, '""'); // escape double quotes

		            }).get().join(tmpColDelim);

		        }).get().join(tmpRowDelim)
		            .split(tmpRowDelim).join(rowDelim)
		            .split(tmpColDelim).join(colDelim) + '"',
		        // Data URI
		        csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);

		    $(this)
		        .attr({
		        'download': filename,
		            'href': csvData,
		            'target': '_blank'
		    });
		}

	    // This must be a hyperlink
	    $(".export").on('click', function (event) {
	        // CSV
	        exportTableToCSV.apply(this, [$('#dvData>table'), 'report.csv']);
	        
	        // IF CSV, don't do event.preventDefault() or return false
	        // We actually need this to be a typical hyperlink
	    });

	    $("#report").tablesorter({
	        headers:
	            {
	                4: { sorter: "customDate" },
	                5: { sorter: "customDate" },
	                {/literal} {$date_field_script} {literal}
	            },
	        widgets: ['zebra']
	    });

		$.tablesorter.addParser({
	            id: "customDate",
	            is: function(s) {
	            // return s.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, [0-9]{4}|'?[0-9]{2}$/));
	                return false;
	            },
	            format: function(s) {
	                var date = s.split('/');
	                return $.tablesorter.formatFloat(new Date(date[2], date[1], date[0]).getTime());
	            },
	            type: "numeric"
	        });
	});
})(jQuery)
</script>      
{/literal}