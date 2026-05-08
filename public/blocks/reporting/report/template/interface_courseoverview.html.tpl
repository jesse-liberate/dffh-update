
  <p class="text-muted">{php} print_string('fill_out_fields_you_wish', 'block_reporting') {/php}</p>
  <form action="courseoverview.php" method="get" name="search">
	<input type="hidden" name="is_post" value="1" />
	<input type="hidden" name="reportname" id="reportname" value="{$reportname}" />
	<table class="w-100">
	  <tr>
		<td class="pr-5 color-brandcolor-1"><strong>Course Name</strong></td>
		<td>
			{* @25/07/2018 *}
		  <select name="course[]" class="chzn-select" multiple>
			<option value=""></option>
			{foreach from=$courses item=course_name key=course_id}
				{html_options values=$course_id output=$course_name}
			{/foreach}
		  </select>
		</td>
	  </tr>

	  <tr>
		<td class="pr-5 color-brandcolor-1"><strong>Enrolled date</strong></td>
		<td>
			{* @23/07/2018 new enhancement *}
			<div class="enrolleddate-range row no-gutters">
				<div class="input-prepend col row no-gutters">
					<span class="add-on col-auto">From</span>
					<div class="col pr-2">
						<input type="text" name="enrolleddate_from" id="enrolleddate_from" class="datepicker form-control">
					</div>
				</div>
				<div class="input-prepend col row no-gutters">
					<span class="add-on col-auto">To</span>
					<div class="col">
						<input type="text" name="enrolleddate_to" id="enrolleddate_to" class="datepicker form-control">
					</div>
				</div>
			</div>
			{* <input type="text" name="enrolleddate" id="enrolleddate" value="" class="datepicker"/>
			<input type="radio" name="enrol_date_condition" value="1">Before &nbsp
			<input type="radio" name="enrol_date_condition" value="2" checked>After *}
		</td>
	  </tr>
	  <tr>
		<td class="pr-5 color-brandcolor-1"><strong>Completion Date</strong></td>
		<td>
			{* @23/07/2018 new enhancement *}
			<div class="completiondate-range row no-gutters">
				<div class="input-prepend col row no-gutters">
					<span class="add-on col-auto">From</span>
					<div class="col pr-2">
						<input type="text" name="completiondate_from" id="completiondate_from" class="datepicker form-control">
					</div>
				</div>
				<div class="input-prepend col row no-gutters">
					<span class="add-on col-auto">To</span>
					<div class="col">
						<input type="text" name="completiondate_to" id="completiondate_to" class="datepicker form-control">
					</div>
				</div>
			</div>
			{* <input type="text" name="completiondate" id="completiondate" value="" class="datepicker"/>
			<input type="radio" name="completion_date_condition" value="1" checked>Before &nbsp
			<input type="radio" name="completion_date_condition" value="2">After *}
		</td>
	  </tr>
	{foreach from=$user_profile_filters_array item=user_profile_filter}
		<tr>
			<td class="pr-5 color-brandcolor-1"><strong>{$user_profile_filter->name}</strong></td>
			<td>
		  {if $user_profile_filter->type != 'datetime'}
			  <select name={$user_profile_filter->shortname}[] 
			  	{if $user_profile_filter->type == 'text'}
			  		class='chzn-select' multiple
			  	{/if}>
				<option value=""></option>
				{foreach from=$user_profile_filter->user_profile_values item=user_profile_value}
					{if $user_profile_filter->type == 'checkbox'}
						{if $user_profile_value == '0'}
							<option value='0'>No</option>
						{elseif $user_profile_value == '1'}
							<option value='1'>Yes</option>
						{/if}
					{else}					
						<option value="{$user_profile_value}">{$user_profile_value}</option>
					{/if}
				{/foreach}
			  </select>
			{else}
				{* @23/07/2018 enhancement *}
				<div class="{$user_profile_filter->shortname}-range row no-gutters">
					<div class="input-prepend col row no-gutters">
						<span class="add-on col-auto">From</span>
						<div class="col pr-2">
							<input type="text" name="{$user_profile_filter->shortname}_from" id="{$user_profile_filter->shortname}_from" class="datepicker form-control">
						</div>
					</div>
					<div class="input-prepend col row no-gutters">
						<span class="add-on col-auto">To</span>
						<div class="col">
							<input type="text" name="{$user_profile_filter->shortname}_to" id="{$user_profile_filter->shortname}_to" class="datepicker form-control">
						</div>
					</div>
				</div>
				{* <input type="text" name="{$user_profile_filter->shortname}" id="{$user_profile_filter->shortname}" value="" class="datepicker"/>
				<input type="radio" name="{$user_profile_filter->shortname}_condition" value="1" checked>Before &nbsp
				<input type="radio" name="{$user_profile_filter->shortname}_condition" value="2">After *}
			{/if}
			</td>
		</tr>
	{/foreach} 
	  
	{foreach from=$general_filters_array item=general_filters}
		<tr>
			<td class="pr-5 color-brandcolor-1"><strong>{$general_filters->filterdesc}</strong></td>
			<td>
				{if $general_filters->filtername == 'lastaccess'}
					{* @23/07/2018 enhancement *}
					<div class="lastaccess-range row no-gutters">
						<div class="input-prepend col row no-gutters">
							<span class="add-on col-auto">From</span>
							<div class="col pr-2">
								<input type="text" name="lastaccess_from" id="lastaccess_from" class="datepicker form-control">
							</div>
						</div>
						<div class="input-prepend col row no-gutters">
							<span class="add-on col-auto">To</span>
							<div class="col">
								<input type="text" name="lastaccess_to" id="lastaccess_to" class="datepicker form-control">
							</div>
						</div>
					</div>
					{* <input type="text" name="lastaccess" id="lastaccess" value="" class="datepicker"/>
					<input type="radio" name="lastaccess_condition" value="1" checked>Before &nbsp
					<input type="radio" name="lastaccess_condition" value="2">After *}
				{elseif $general_filters->filtername == 'username' || $general_filters->filtername == 'email'}
					<div class="p-2 bg-color-brand-4 text-white mb-2">Show all</div>
				{else}
					{* @25/07/2018 *}
					<select name="{$general_filters->filtername}[]" id="{$general_filters->filtername}" class="chzn-select" multiple>
						<option value=""></option>
						{foreach from=$general_filters->value item=value}
							<option value="{$value}">{$value}</option>
						{/foreach}
					</select>
				{/if}
			</td>
		</tr>
	{/foreach} 

	<tr>
		<td class="pr-5 color-brandcolor-1"><strong>Suspended users</strong></td>
		<td>
			<select name="suspendedusers" class="form-control">
				<option value="none" selected="selected">Exclude suspended users</option>
				<option value="all">Include Suspended Users</option>
				<option value="only">Show Suspended Users Only</option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="pr-5 color-brand-1 py-5"><strong>{php} print_string('display_type', 'block_reporting') {/php}</strong></td>
		<td class="py-5">
			<div class="custom-control custom-radio custom-control-inline">
				<input type="radio" id="html" name="type" value="HTML" class="custom-control-input" checked="checked">
				<label class="custom-control-label" for="html">HTML</label>
				<span style='color: #ec1c24;'>
					{$max_display_limit_reminder}
				</span>
			</div>
			<div class="custom-control custom-radio custom-control-inline">
				<input type="radio" id="csv" name="type" value="CSV" class="custom-control-input">
				<label class="custom-control-label" for="csv">Excel/CSV ({php} print_string('select_to_print', 'block_reporting') {/php})</label>
				<span style='color: #ec1c24;'>
					{$max_export_limit_reminder}
				</span>
			</div>
			{if $report_pdf == 2}
				<div class="custom-control custom-radio custom-control-inline">
					<input type="radio" id="pdf" name="type" value="PDF" class="custom-control-input">
					<label class="custom-control-label" for="pdf">PDF</label>
				</div>
			{/if}
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" class='btn-custom bg-color-brand-4 hover-bg-color-brand-1 text-white mr-2' value="{php} print_string('go', 'block_reporting') {/php}"/>
			{if $is_admin}
			<a href="{$export_link}" class="btn-custom bg-color-brand-4 hover-bg-color-brand-1 text-white" >{php}print_string('general:downloadall', 'block_reporting'){/php}</a>
			{/if}
		</td>
	</tr>
</table>
</form>
  {literal}
  <style>
  		.chosen-container-single .chosen-single {
  			line-height: 30px;
  		}
  		.chosen-container-single .chosen-single {
  			height: 30px;
  		}
  		.hierarchy-filter-block div {
  			padding-top: 7px;
  		}
  		.hierarchy-filter-block p {
  			padding-top: 10px;
  		}
  		.general-report tr > td {
  			vertical-align: top;
  		}
		.chosen-container {
	 		min-width: 220px;
		}   
	</style>
  	<script>
	  	//Note: jquery UI - Only use Datepicker and Autocomplete Libraries
		$(function(){
			// General Reports
			// @25/07/2018 enhancement
			$(".chzn-select").chosen({search_contains: true, width: '100%', placeholder_text_multiple: 'Select some options'});
			$(".datepicker").datepicker({ dateFormat: 'dd/mm/yy' });
			//$("#lastaccess").datepicker({ dateFormat: 'dd/mm/yy' });
			//{/literal}{$datepicker_fields}{literal}

			//Individual Reports
			//if ($(".autocomplete")[0]){
			$('head').append('<style>.ui-autocomplete { max-height: 100px; overflow-y: auto; overflow-x: hidden; padding-right: 20px; } * html .ui-autocomplete { height: 100px; }</style>');
			//}
			$(".autocomplete#name").autocomplete({ source:'get_names_list.php' }); // /blocks/reporting/
			
			
			$("#tabs").tabs();

			// $(".chzn-select").chosen({search_contains: true});

		});
		function isNumberKey(evt){
		    var charCode = (evt.which) ? evt.which : event.keyCode
		    if (charCode > 31 && (charCode < 48 || charCode > 57))
		        return false;
		    return true;
		}

  		function handleChange(input) {
		    if (input.value < 0) input.value = 0;
		    if (input.value > 100) input.value = 100;
		  }


	</script>
  {/literal}
