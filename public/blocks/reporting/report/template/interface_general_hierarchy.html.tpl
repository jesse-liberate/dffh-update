<h2 class="headingblock header">{php} print_string('general_reports', 'block_reporting') {/php}</h2>
<p class="text-muted">{php} print_string('fill_out_fields_you_wish', 'block_reporting') {/php}</p>
<form action="general.php" method="get" name="search" id="report">
  <input type="hidden" name="is_post" value="1" />
  <input type="hidden" name="hierarchy" id="hierarchy"/>
  <input type="hidden" name="selectednodes" id="selectednodes"/>
  <input type="hidden" name="selectednodenames" id="selectednodenames"/>
  <input type="hidden" name="reportname" id="reportname" value="{$reportname}" />
  <table class='general-report w-100'>
    <tr>
      <td class="pr-5 color-brand-1"><strong>{php} print_string('course_name', 'block_reporting'); {/php}</strong></td>
      <td>
        {* @25/07/2018 enhancement *}
        <select name="course[]" class="chzn-select" multiple>
          <option value=""></option>
          {foreach from=$courses item=course_name key=course_id}
            {html_options values=$course_id output=$course_name}
          {/foreach}
        </select>
      </td>
    </tr>
    <tr>
      <td class="pr-5 color-brand-1"><strong>{php} print_string('completionmenuitem', 'core_completion'); {/php}</strong></td>
      <td>
        <select name="completionstatus" class="form-control" id="completionstatus">
          <option label="" value="0"></option>
          <option label="Not Completed" value="1">Not Completed</option>
          <option label="Completed" value="2">Completed</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="pr-5 color-brand-1"><strong>{php} print_string('enrolled_date', 'block_reporting') {/php}</strong></td>
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
        <input type="radio" name="enrol_date_condition" value="1">{php} print_string('before', 'block_reporting') {/php} &nbsp
        <input type="radio" name="enrol_date_condition" value="2" checked>{php} print_string('after', 'block_reporting') {/php} *}
      </td>
    </tr>
    <tr>
      <td class="pr-5 color-brand-1"><strong>{php} print_string('completion_date', 'block_reporting') {/php}</strong></td>
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
        <input type="radio" name="completion_date_condition" value="1" checked>{php} print_string('before', 'block_reporting') {/php} &nbsp
        <input type="radio" name="completion_date_condition" value="2">{php} print_string('after', 'block_reporting') {/php} *}
      </td>
    </tr>
    <tr>
      <td class="pr-5 color-brand-1" style='vertical-align: top;'><strong>{php} print_string('hierarchy', 'block_reporting') {/php}</strong></td>
      <td class='hierarchy-filter-block'>
        <input type="text" class="form-control" id="hie_search" placeholder="{php} print_string('search_for_node', 'block_reporting') {/php}"/>
        <div id='jstree'></div>
        <p>{php} print_string('current_selected_hierarchy', 'block_reporting') {/php}: <strong id="selection"></strong></p>
      </td>
    </tr>

    {foreach from=$user_profile_filters_array item=user_profile_filter}
      <tr>
        <td class="pr-5 color-brand-1"><strong>{$user_profile_filter->name}</strong></td>
        <td>
          {if $user_profile_filter->type != 'datetime'}
            {* @25/07/2018 enhancement *}
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
        <td class="pr-5 color-brand-1"><strong>{$general_filters->filterdesc}</strong></td>
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
      <td class="pr-5 color-brand-1"><strong>{php} print_string('suspended_users', 'block_reporting') {/php}</strong></td>
      <td>
        <select name="suspendedusers" class="form-control">
          <option value="none" selected="selected">{php} print_string('exclude_suspended_users', 'block_reporting') {/php}</option>
          <option value="all">{php} print_string('include_suspended_users', 'block_reporting') {/php}</option>
          <option value="only">{php} print_string('show_suspended_users_only', 'block_reporting') {/php}</option>
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
<script type="x/template" id="id_hierarchy_nodes">{$hierarchy_nodes}</script>
<script type="x/template" id="id_root_node">{$root_node_id}</script>                 

