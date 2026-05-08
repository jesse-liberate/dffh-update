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
	<p>Applied filters: {$coursename};
{php}
	if(isset($_REQUEST["completionstatus"]) && $_REQUEST["completionstatus"]!="" && $_REQUEST["completionstatus"]!= 0) { 
		switch ($_REQUEST["completionstatus"]) {
			case 1: $status = get_string('not_completed', 'block_reporting'); break;
			case 2: $status = get_string('completed', 'block_reporting'); break;
		}
		echo ' ' . get_string('status', 'block_reporting') . " - <strong>" . $status . "</strong>;";
	}
	if(isset($_REQUEST["enrolleddate_from"]) && $_REQUEST["enrolleddate_from"]!="") echo" Enrolled date from - <strong>" . $_REQUEST["enrolleddate_from"] . "</strong>;";
	if(isset($_REQUEST["enrolleddate_to"]) && $_REQUEST["enrolleddate_to"]!="") echo" Enrolled date to - <strong>" . $_REQUEST["enrolleddate_to"] . "</strong>;";

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

{php} print_string('activityresult', 'block_reporting') {/php}
<p>{php} print_string('sorting_tip', 'block_reporting') {/php}</p>
<div name='defaultuserid'></div>
{php}
$defaultid = get_get('defaultuserid');
{/php}
<div style='text-align:right;margin-bottom:10px'><a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">Export Excel/CSV</a> </div>
<table id="report" class="tablesorter">
 	<thead>
		<tr>
			<th>Full name</th>
			<th>Enrolled Date</th>
			{foreach from=$modules item=modulename}
	  			<th>{$modulename}		
	  			</th>
	  		{/foreach}
			
			{foreach from=$filters_array key=filter_name item=is_result_applied}
				<th>{$is_result_applied->record->name}</th>
	  		{/foreach}

	  		{foreach from=$general_filters_array2 key=filtername item=is_result_applied}
				<th>{$is_result_applied->filterdesc}</th>
	  		{/foreach}
		</tr>
	</thead>
	<tbody>
	{foreach from=$userinfo_row item=row key=result}
			<tr>
				<td style="border-width:1px;">
				<a href="../../../user/profile.php?id={$result}">
					{$row.firstname} {$row.lastname}
				</a>
				</td>
				<td style="border-width:1px;">{$row.enrolleddate}</td>
				{foreach from = $row.module item=result}
				<td style="border-width:1px;text-align:center">
					{if $result == 1}
						<div style="display:none;">0</div>
						{html_image file='img/completion-auto-y.png'}
					{elseif $result == 2}
						<div style="display:none;">0</div>
						{html_image file='img/completion-auto-y.png'}
					{else}
						<div style="display:none;">1</div>
						{html_image file='img/completion-auto-n.png'}
					{/if}
				</td>
				{/foreach}

			  	{foreach from = $row.profile_result item=result}
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
  </tbody>
</table>
<div style='text-align:right'><a href="#" class="btn-custom bg-color-brand-3 hover-bg-color-brand-1 text-white">Export Excel/CSV</a> </div>
{literal}
 <script>
    $(".export").on('click', function (event) {
    	var url = location.href;
        url = url.replace('type=HTML','type=CSV');
        // alert(unescape(location.href));
        window.open(url);
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

    $(document).ready(function(){
        $("#report").tablesorter({
            headers:
                {
                	1: { sorter: "customDate" },
                	{/literal} {$date_field_script} {literal}
                },
            widgets: ['zebra']
        });
    });   

</script>      
{/literal}