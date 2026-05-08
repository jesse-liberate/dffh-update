<h5>Total enrolled users in the course: {$total_enrolled}</h5>

<table id="deadlines" class="generaltable">
  <thead>
    <tr>
      <th>Priority</th>
      <th>{php} print_string('fullnameuser') {/php}</th>
      <th>Email</th>
      <th>First Reminder</th>
      <th>Day(s) after first reminder</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    {foreach from=$deadlines item=deadline}
      <tr>
        <td>
          {php} print_string('priority1', 'tool_setdeadline') {/php}
        </td>
        <td>
          {$deadline->fullname}
        </td>
        <td>
          {$deadline->email}
        </td>
        <td>
          {if $deadline->firstreminder == 0}
            None
          {else}
            {$deadline->firstreminder|date_format:"%d/%m/%Y"}
          {/if}
        </td>
        <td>
          {if !isset($deadline->secondreminder)}
            None
          {else}
            {$deadline->secondreminder/86400}
          {/if}
        </td>
        <td>
          {$deadline->deletelink}
          {$deadline->editlink}
        </td>
      </tr>
    {/foreach}
  </tbody>
</table>
<br/>

