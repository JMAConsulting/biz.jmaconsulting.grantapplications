SELECT @workflow_id := MAX(id) FROM civicrm_option_value WHERE name = 'grant_online_receipt';

SET @html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

<center>
 <table width="500" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>

    {if $receipt_text}
     <p>{$receipt_text|htmlize}</p>
    {/if}

     <p>{ts}Please print this confirmation for your records.{/ts}</p>

   </td>
  </tr>
  </table>
  <table width="500" style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse;">

     {if $default_amount_hidden AND $default_amount_hidden neq "0.00"}


      <tr>
       <th {$headerStyle}>
        {ts}Grant Application Information{/ts}
       </th>
      </tr>
       <tr>
        <td {$labelStyle}>
         {ts}Requested Amount{/ts}
        </td>
        <td {$valueStyle}>
         {$default_amount_hidden|crmMoney:$currency}        </td>
       </tr>
     {/if}


     {if $application_received_date}
      <tr>
       <td {$labelStyle}>
        {ts}Date{/ts}
       </td>
       <td {$valueStyle}>
        {$application_received_date|crmDate}
       </td>
      </tr>
     {/if}

     
       <tr>
        <th {$headerStyle}>
         {ts}Registered Email{/ts}
        </th>
       </tr>
       <tr>
        <td colspan="2" {$valueStyle}>
         {$email}
        </td>
       </tr>

     {if $customPre}
      <tr>
       <th {$headerStyle}>
        {$customPre_grouptitle}
       </th>
      </tr>
      {foreach from=$customPre item=customValue key=customName}
       {if ($trackingFields AND ! in_array($customName, $trackingFields)) or ! $trackingFields}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$customValue}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

     {if $customPost}
      <tr>
       <th {$headerStyle}>
        {$customPost_grouptitle}
       </th>
      </tr>
      {foreach from=$customPost item=customValue key=customName}
       {if ($trackingFields AND ! in_array($customName, $trackingFields)) or ! $trackingFields}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$customValue}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

  </table>
</center>

</body>
</html>';

SET @text = '{if $receipt_text}
{$receipt_text}
{/if}

{ts}Please print this receipt for your records.{/ts}

===========================================================
{ts}Grant Application Information{/ts}

===========================================================

{if $default_amount_hidden AND $default_amount_hidden neq "0.00"}
{ts}Requested Amount{/ts}: {$default_amount_hidden|crmMoney:$currency}   
{/if}
{if $application_received_date}
{ts}Date{/ts}: {$application_received_date|crmDate}
{/if}
{ts}Registered Email{/ts}:    {$email}
{if $contributeMode eq "direct" AND !$is_pay_later AND $amount GT 0}
{ts}Credit Card Information{/ts}:  {$credit_card_type}
					            {$credit_card_number}<br />
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{if $selectPremium}
{ts}Premium Information{/ts} : {$product_name}
{if $option}
{ts}Option{/ts} :      {$option}
{/if}
{if $sku}
{ts}SKU{/ts}:  {$sku}
{/if}
{if $start_date}
{ts}Start Date{/ts}:  {$start_date|crmDate}
{/if}
{if $end_date}
{ts}End Date{/ts}: {$end_date|crmDate}
{/if}
{if $contact_email OR $contact_phone}
{ts}For information about this premium, contact:{/ts}
{if $contact_email}
{$contact_email}
{/if}
{if $contact_phone}
{$contact_phone}
{/if}
{/if}
{if $is_deductible AND $price}
{ts 1=$price|crmMoney:$currency}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}
{/if}
{/if}

{if $customPre}
===========================================================
        {$customPre_grouptitle}
===========================================================
{foreach from=$customPre item=customValue key=customName}
{if ($trackingFields AND ! in_array($customName, $trackingFields)) or ! $trackingFields}
{$customName}:  {$customValue}
{/if}
{/foreach}
{/if}

{if $customPost}
===========================================================
        {$customPost_grouptitle}
===========================================================
{foreach from=$customPost item=customValue key=customName}
{if ($trackingFields AND ! in_array($customName, $trackingFields)) or ! $trackingFields}
{$customName}:    {$customValue}
{/if}
{/foreach}
{/if}';

UPDATE civicrm_msg_template SET msg_html = @html WHERE workflow_id = @workflow_id;
UPDATE civicrm_msg_template SET msg_text = @text WHERE workflow_id = @workflow_id;