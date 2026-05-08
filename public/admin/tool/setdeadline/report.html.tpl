<table id="courselist" class="generaltable">
    <thead>
        <tr>
            <th>{php} print_string('coursename', 'tool_setdeadline') {/php}</th>
            <th>Deadline type</th>
            <th>Priority</th>
            <th>Period 1 (in days)</th>
            <th>Period 2 (in days after period 1)</th>
            <th>Repeat the period 2 indefinitely</th>
        {if $is_hierarchy_installed}
            <th>Send to Manager</th>
        {/if}
            <th>Send to Site Admin</th>
            <th>Created date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$deadlinelist item=course}
        <tr>
            <td> {$course->coursename}</td>
            <td> {$course->deadlinetype}</td>
            <td> {$course->priority}</td>
            {if $course->firstreminder == 0}
                <td>None</td>
            {else}
                <td>{$course->firstreminder/86400}</td>
            {/if}
            {if $course->secondreminder == 0}
                <td>None</td>
            {else}
                <td>{$course->secondreminder/86400}</td>
            {/if}
            <td>{if $course->repeated == 1}Yes{else}No{/if}</td>
        {if $is_hierarchy_installed}
            <td>{if $course->manager == 1}Yes{else}No{/if}</td>
        {/if}
            <td>{if $course->siteadmin == 1}Yes{else}No{/if}</td>
            <td> {$course->createddate}</td>
            <td>
                {$course->deletelink}
                {$course->editlink}
                {$course->test_link}
                {$course->override_link}
            </td>
        </tr>
        {/foreach}
    </tbody>
</table>
<br/>