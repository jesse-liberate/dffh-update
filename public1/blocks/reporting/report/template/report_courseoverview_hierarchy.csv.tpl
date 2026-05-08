"Full name","{php}print_string('course_name', 'block_reporting'){/php}","Completed percentage","Enrolled date","Completion date","{foreach from=$filters_array key=filter_name item=is_result_applied}{$is_result_applied->record->name}","{/foreach}{foreach from=$general_filters_array2 key=filtername item=is_result_applied}{$is_result_applied->filterdesc}","{/foreach}"
{foreach from=$userinfo_row item=course}
{foreach from=$course item=row key=result}
"{$row.firstname} {$row.lastname}","{$row.coursename}","{$row.percentage}","{$row.enrolleddate}","{if $row.percentage == '100%'}{$row.completiondate}{/if}",{foreach from = $row.profile_result item=result}"{if $result.type=='lastaccess'}{if $result.value!=0}{$result.value|date_format:"%d/%m/%Y"}{/if}{else}{$result.value}{/if}",{/foreach},
{/foreach}
{/foreach}