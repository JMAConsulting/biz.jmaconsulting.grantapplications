{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-form-block crm-grant-grantpage-draft-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>				    
  <table class="form-layout-compressed">
    <tr class="crm-grant-grantpage-draft-form-block-nosubmit_check">
      <td class="label">&nbsp;</td>
      <td class="html-adjust">{$form.is_draft.html} {$form.is_draft.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_grant_app_page' field='is_draft' id=$grantApplicationPageID}{/if}<br />
        <span class="description">{ts}Include a 'Save as Draft' button on this Grant Application Page.{/ts}</span>
      </td>
    </tr>
  </table> 
  <table id="savedDetails" class="form-layout-compressed">
    <tr class="crm-grant-grantpage-thankyou-form-block-thankyou_title">
      <td class="label">{$form.draft_title.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_grant_app_page' field='draft_title' id=$grantApplicationPageID}{/if}</td>
      <td class="html-adjust">{$form.draft_title.html}<br />
        <span class="description">{ts}This title will be displayed at the top of the Save as Draft page.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-grant-grantpage-draft-form-block-thankyou_text">
      <td class="label">{$form.draft_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_grant_app_page' field='thankyou_text' id=$grantApplicationPageID}{/if}</td>
      <td class="html-adjust">{$form.draft_text.html}<br />
       	<span class="description">{ts}Enter text (and optional HTML layout tags) for the draft message that will appear at the top of the Save as Draft page.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-grant-grantpage-draft-form-block-thankyou_footer">
      <td class="label">{$form.draft_footer.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_grant_app_page' field='draft_footer' id=$grantApplicationPageID}{/if}</td>
      <td class="html-adjust">{$form.draft_footer.html}<br />
        <span class="description">{ts}Enter link(s) and/or text that you want to appear at the bottom of the Save as Draft page.{/ts}</span>
      </td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>


<script type="text/javascript">
  showSavedDetails();
  {literal}
    function showSavedDetails() {
      var checkbox = document.getElementsByName("is_draft");
      if (checkbox[0].checked) {
        document.getElementById("savedDetails").style.display = "block";
      } else {
        document.getElementById("savedDetails").style.display = "none";
      }  
    } 
  {/literal} 
</script>