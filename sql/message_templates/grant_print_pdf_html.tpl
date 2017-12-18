<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title></title>
  </head>
  <body>
    {capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
    {capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}
    <center>
      <table>
	<tr>
	  <td {$labelStyle}>{ts}Name{/ts}</td>
	  <td {$valueStyle}>{$values.display_name}</td>
	</tr>
	<tr>
	  <td {$labelStyle}>{ts}Grant Application Received Date{/ts}</td>
	  <td {$valueStyle}>{$values.application_received_date}</td>
	</tr>
	<tr>
	  <td {$labelStyle}>{ts}Grant Decision Date{/ts}</td>
	  <td {$valueStyle}>{$values.decision_date}</td>
	</tr>
	<tr>
	  <td {$labelStyle}>{ts}Grant Money Transferred Date{/ts}</td>
	  <td {$valueStyle}>{$values.money_transfer_date}</td>
	</tr>
	<tr>
	  <td {$labelStyle}>{ts}Grant Due Date{/ts}</td>
	  <td {$valueStyle}>{$values.grant_due_date}</td>
	</tr>
	<tr>
	  <td {$labelStyle}>{ts}Total Amount{/ts}</td>
	  <td {$valueStyle}>{$values.amount_total|crmMoney:$currency}</td>
	</tr>
	<tr>
	  <td {$labelStyle}>{ts}Amount Requested{/ts}</td>
	  <td {$valueStyle}>{$values.amount_requested|crmMoney:$currency}</td>
	</tr>
	<tr>
	  <td {$labelStyle}>{ts}Amount Granted{/ts}</td>
	  <td {$valueStyle}>{$values.amount_granted|crmMoney:$currency}</td>
	</tr>	
	{if $values.rationale}
	<tr>
	  <td {$labelStyle}>{ts}Rationale{/ts}</td>
	  <td {$valueStyle}>{$values.rationale}</td>
	</tr>
	{/if}
	{if $values.noteId}
	<tr>
	  <td {$labelStyle}>{ts}Notes{/ts}</td>
	  <td {$valueStyle}>{$values.noteId}</td>
	</tr>
	{/if}
	{if $values.custom}
	{foreach from=$values.custom item=field key=id}
	<tr>
	  <td {$labelStyle}>{$field.label}</td>
	  <td {$valueStyle}>{$field.value}</td>
	</tr>	
	{/foreach}
	{/if}
	{if $values.attach}
	{foreach from=$values.attach item=field key=id}
	<tr>
	  <td {$labelStyle}>{$field.label}</td>
	  <td {$valueStyle}>{$field.value}</td>
	</tr>
	{/foreach}
	{/if}
      </table>
    </center>
  </body>
</html>
	
