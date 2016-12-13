{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}

{* Callback snippet: On-behalf profile *}
{if $snippet and !empty($isOnBehalfCallback)}
  <div class="crm-public-form-item crm-section">
    {include file="CRM/Grant/Form/Grant/OnBehalfOf.tpl" context="front-end"}
  </div>
{/if}
{include file="CRM/common/TrackingFields.tpl"}

{capture assign='reqMark'}<span class="marker" title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-grant-main-form-block">
  <div id="intro_text" class="crm-public-form-item crm-section intro_text-section">
    {$intro_text}
  </div>
  {assign var=n value=email-$bltID}
  <div class="crm-public-form-item crm-section {$form.$n.name}-section">
    <div class="label">{$form.$n.label}</div>
    <div class="content">
      {$form.$n.html}
    </div>
    <div class="clear"></div>
  </div>
    <div class="crm-public-form-item crm-section">
    {include file="CRM/Grant/Form/Grant/OnBehalfOf.tpl"}
  </div>

  <div class="crm-section default_amount-section">
    {if isset($defaultAmount) && $defaultAmount neq "0.00"}
      <div class="label">Requested Amount</div>
      <div class="content">
        {$defaultAmount|crmMoney}
      </div>	
      <div class="clear"></div>
    {/if}
  </div> 

  {include file="CRM/common/CMSUser.tpl"}

  <div class="crm-group custom_pre_profile-group">
    {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
  </div>

  <div class="crm-group custom_post_profile-group">
    {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
  </div>

  {if $isCaptcha}
    {include file='CRM/common/ReCAPTCHA.tpl'}
  {/if}
  <div id="crm-submit-buttons" class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  {if $footer_text}
    <div id="footer_text" class="crm-section grant_footer_text-section">
      <p>{$footer_text}</p>
    </div>
  {/if}
</div>
	
{literal}
<script type="text/javascript">
  cj(function(){
    var numericFields = {/literal}{$numericFields}{literal};
    var elementName = '';
    var value = '';
    cj('input').blur(function(){
      elementName = cj(this).attr('id');
      if (elementName in numericFields) {
      	value = cj(this).val();
	if (value) {
	  if (numericFields[elementName] == 'Int') {
      	    value = value.replace(/[^0-9|,]/g, '');	 
          }
          else {
	    value = value.replace(/[^\d|.|,]/g, '');
          }
	  cj(this).val(value);
        }
      }
    });
  });
</script>
{/literal}

{if $fileFields}
  {literal}
    <script type='text/javascript'>
  {/literal}
  {foreach from=$fileFields item=values key=id}
    {literal}
      var id = {/literal}'{$id}'{literal};
      var displayURL = {/literal}'{$values.displayURL}'{literal};
      var fileURL = {/literal}'{$values.fileURL}'{literal};
      var fileName = {/literal}'{$values.fileName}'{literal};
      var fid = {/literal}'{$values.fid}'{literal};
      if (displayURL) {
        cj('#editrow-'+id).append('<div class=label>Attached File:</div><div class=content><a href='+displayURL+' class=crm-image-popup><img src='+displayURL+' height="100" width="100"></a></div>');
      }
      else {
        cj('#editrow-'+id).append('<div class=label>Attached File:</div><div class=content><a href='+fileURL+'>'+fileName+'</a></div>');
      }
    {/literal}
  {/foreach}
  {literal}
    </script>
  {/literal}
{/if}
