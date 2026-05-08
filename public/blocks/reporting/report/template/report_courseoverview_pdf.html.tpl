<style id="css">
	
	{literal}
		/* tables */
		table.tablesorter {
			font-family:arial;
			margin:10px 0pt 15px;
			font-size: 8pt;
			width: 100%;
			text-align: left;
			border: 1px solid #000;
    		border-collapse: collapse;
		}
		table.tablesorter thead tr th, table.tablesorter tfoot tr th {
			background-color: #ccc;
			border: 1px solid #000;
			font-size: 8pt;
			padding: 4px;
		}
		table.tablesorter thead tr .header {
			background-repeat: no-repeat;
			background-position: center right;
			cursor: pointer;
		}
		table.tablesorter tbody td {
			color: #3D3D3D;
			padding: 4px;
			vertical-align: top;
			border: 1px solid #000;
		}
		table.tablesorter tbody tr.odd td {
			/*background-color:#F0F0F6;*/
		}
		table.tablesorter thead tr .headerSortDown, table.tablesorter thead tr .headerSortUp {
			background-color: #8dbdd8;
		}
		thead { display: table-header-group }
		tfoot { display: table-row-group }
		tr { page-break-inside: avoid }
	{/literal}
</style>

{php} print_string('courseoverviewresult', 'block_reporting') {/php}
	<p>Applied filters: {$coursename};
{php}
	if(isset($_REQUEST["completionstatus"]) && $_REQUEST["completionstatus"]!="" && $_REQUEST["completionstatus"]!= 0) { 
		switch ($_REQUEST["completionstatus"]) {
			case 1: $status = get_string('not_completed', 'block_reporting'); break;
			case 2: $status = get_string('completed', 'block_reporting'); break;
		}
		echo ' ' . get_string('status', 'block_reporting') . " - <strong>" . $status . "</strong>;";
	}
	if(isset($_REQUEST["enrolleddate"]) && $_REQUEST["enrolleddate"]!="")
		echo " " . get_string('enrolled_date', 'block_reporting') . " - <strong>" . $_REQUEST["enrolleddate"] . "</strong>;";
	if(isset($_REQUEST["completiondate"]) && $_REQUEST["completiondate"]!="")
		echo " " . get_string('completion_date', 'block_reporting') . " - <strong>" . $_REQUEST["completiondate"] . "</strong>;";


	if(isset($_REQUEST["selectednodenames"]) && ($_REQUEST["selectednodenames"]!="")) {
		echo" Hierarchy - <strong>" . $_REQUEST["selectednodenames"] . "</strong>;";
	}

	$filters_array = $this->get_template_vars('filters_array');
	foreach ($filters_array as $filter_name=>$is_result_applied) {
		if(isset($_REQUEST[$filter_name]) && $_REQUEST[$filter_name]!="") {
			echo " " . $is_result_applied->record->name . " - <strong>" . $_REQUEST[$filter_name] . "</strong>;";
		}
	}
	$general_filters_array2 = $this->get_template_vars('general_filters_array2');
	foreach($general_filters_array2 as $filtername=>$is_result_applied){
		if(isset($_REQUEST[$filtername]) && $_REQUEST[$filtername]!="") {
			$condition='-';
			if($filtername=='lastaccess'){

				if($_REQUEST['lastaccess_condition']==1)
					$condition='<=';
				else $condition='>=';
			}
			echo " " . $is_result_applied->filterdesc . " ".$condition." <strong>" . $_REQUEST[$filtername] . "</strong>;";
		}
	}
	
{/php}
	</p>
<div id='dvData'>
<table id="report" class="tablesorter">
 	<thead>
		<tr>
			<th>{php} print_string('fullname') {/php}</th>
			<th>{php} print_string('course', 'block_reporting') {/php}</th>
			<th>{php} print_string('enrolled_date', 'block_reporting') {/php}</th>
			<th>{php} print_string('completion_date', 'block_reporting') {/php}</th>
			{foreach from=$filters_array key=filter_name item=is_result_applied}
				<th>{$is_result_applied->record->name}</th>
	  		{/foreach}

	  		{foreach from=$general_filters_array2 key=filtername item=is_result_applied}
				<th>{$is_result_applied->filterdesc}</th>
	  		{/foreach}
		</tr>
	</thead>
	<tbody>
	{foreach from=$userinfo_row item=course key=user_id}
			{foreach from=$course item=row key=result}
			<tr>
				<td style="border-width:1px;">
					{$row.firstname} {$row.lastname}
				</td>
				<td style="border-width:1px;">
				<div class="report_progress_bar">
				    	{$row.coursename} <b>({$row.percentage})</b>
				</div>
					
				</td>
				<td style="border-width:1px;">{$row.enrolleddate}</td>
				<td style="border-width:1px;">
					{if $row.percentage=='100%'}
						{$row.completiondate}
					{/if}
				</td>

			  	{foreach from = $row.profile_result key=abc item=result}
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
			</tr>	
			{/foreach}
		{/foreach}
  </tbody>
</table>
</div>