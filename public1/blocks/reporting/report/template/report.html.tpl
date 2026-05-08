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
</style>
	<p>Applied filters: {if $coursename != ''}{$coursename};{/if}
{php}
	if(isset($_REQUEST["completionstatus"]) && $_REQUEST["completionstatus"]!="" && $_REQUEST["completionstatus"]!= 0) { 
		switch ($_REQUEST["completionstatus"]) {
			case 1: $status = 'Not Completed'; break;
			case 2: $status = 'Completed'; break;
		}
		echo" Status - <strong>" . $status . "</strong>;";
	}
	if(isset($_REQUEST["enrolleddate_from"]) && $_REQUEST["enrolleddate_from"]!="") echo" Enrolled date from - <strong>" . $_REQUEST["enrolleddate_from"] . "</strong>;";
	if(isset($_REQUEST["enrolleddate_to"]) && $_REQUEST["enrolleddate_to"]!="") echo" Enrolled date to - <strong>" . $_REQUEST["enrolleddate_to"] . "</strong>;";
	if(isset($_REQUEST["completiondate_from"]) && $_REQUEST["completiondate_from"]!="") echo" Completion date from - <strong>" . $_REQUEST["completiondate_from"] . "</strong>;";
	if(isset($_REQUEST["completiondate_to"]) && $_REQUEST["completiondate_to"]!="") echo" Completion date to - <strong>" . $_REQUEST["completiondate_to"] . "</strong>;";


	$filters_array = $this->get_template_vars('filters_array');
	foreach ($filters_array as $filter_name=>$is_result_applied) {
		if ($is_result_applied->type == 'datetime'){
			if (!empty($_REQUEST[$filter_name.'_from'])){
				echo " " . $is_result_applied->record->name . " from - <strong>" . $_REQUEST[$filter_name.'_from'] . "</strong>;";
			}
			if (!empty($_REQUEST[$filter_name.'_to'])){
				echo " " . $is_result_applied->record->name . " to - <strong>" . $_REQUEST[$filter_name.'_to'] . "</strong>;";
			}
		}else{
			if(isset($_REQUEST[$filter_name]) && $_REQUEST[$filter_name]!="") {
				echo " " . $is_result_applied->record->name . " - <strong>" . implode(', ', $_REQUEST[$filter_name]) . "</strong>;";
			}
		}
	}
	$general_filters_array2 = $this->get_template_vars('general_filters_array2');
	foreach($general_filters_array2 as $filtername=>$is_result_applied){
		if($filtername == 'lastaccess'){
			if(!empty($_REQUEST['lastaccess_from'])){
				echo " Last access from - <strong>" . $_REQUEST['lastaccess_from'] . "</strong>;";
			}
			if (!empty($_REQUEST['lastaccess_to'])){
				echo " Last access to - <strong>" . $_REQUEST['lastaccess_to'] . "</strong>;";
			}
		}else{
			if(isset($_REQUEST[$filtername]) && $_REQUEST[$filtername]!="") {
				echo " " . $is_result_applied->filterdesc . " - <strong>" . implode(', ', $_REQUEST[$filtername]) . "</strong>;";
			}
		}
	}
{/php}
	</p>
	
{php} print_string('generalresult', 'block_reporting') {/php}
{if isset($max_limit_alert)}
    <p style='color: #ec1c24;'>{$max_limit_alert}</p>
{/if}
<p>{php} print_string('sorting_tip', 'block_reporting') {/php}</p>
<div style='float:right;margin-bottom:10px'><a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">Export Excel/CSV</a> </div>
<div id='dvData'>
    <p>Total Records {$record_count}</p>
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
	  		{foreach from=$general_filters_array2 key=filtername item=is_result_applied}
				<th>{$is_result_applied->filterdesc}</th>
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
			  		{if $result.type=='lastaccess'}
						<td style="border-width:1px;">
						{if $result.value!=0}
							{$result.value|date_format:"%d/%m/%Y"}
						{/if}
						</td>
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

$(document).ready(function () {

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
});

 (function($){
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

    $(document).ready(function(){
        $("#report").tablesorter({
            headers:
                {
                    4: { sorter: "customDate" },
                    5: { sorter: "customDate" },
                    {/literal} {$date_field_script} {literal}
                },
            widgets: ['zebra']
        });
    });   
})(jQuery)
</script>      
{/literal}
