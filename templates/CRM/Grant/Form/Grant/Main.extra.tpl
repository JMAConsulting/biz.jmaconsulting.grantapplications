{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
