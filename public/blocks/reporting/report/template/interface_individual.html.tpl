  <form action="individual.php" method="POST" name="search">
	<input type="hidden" name="report" value="1" />
	<table class="w-100">
	  <tr>
		<td class="pr-5 color-brand-1"><strong>{php} print_string('fullname') {/php}</strong></td>
		<td><select name='uid' id='uid' data-placeholder='{php} print_string('fullname') {/php}' class='chosen-select'>{$user_fullname_options}</select></td>
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
		</td>
	  </tr>
	</table>
  </form>

 {literal}

<script type="text/javascript">
var config = {
    '.chosen-select'           : {width:"100%", placeholder_text_multiple: 'Select some options'},
    '.chosen-select-deselect'  : {allow_single_deselect:true, placeholder_text_multiple: 'Select some options'},
    '.chosen-select-no-single' : {disable_search_threshold:10},
    '.chosen-select-no-results': {no_results_text:'Could not find any!', placeholder_text_multiple: 'Select some options'},
    '.chosen-select-width'     : {width:"95%", placeholder_text_multiple: 'Select some options'}
}
for (var selector in config) {
    $(selector).chosen(config[selector]);
}
</script>

  {/literal}
