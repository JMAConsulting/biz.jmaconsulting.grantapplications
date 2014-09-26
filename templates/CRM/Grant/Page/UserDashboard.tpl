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
<div class="view-content">
    {if $grant_rows}
        {strip}
    
        <table class="selector">
            <tr class="columnheader">
	        <th>{ts}Name{/ts}</th>
                <th>{ts}Grant Type{/ts}</th>
                <th>{ts}Program Name{/ts}</th>
                <th>{ts}Grant Application Received date{/ts}</th>
                <th>{ts}Amount Granted{/ts}</th>
                <th>{ts}Status{/ts}</th>
                <th>{ts}Operations{/ts}</th>
            </tr>
        
            {foreach from=$grant_rows item=row}
                <tr id='rowid{$row.grant_id}' class="{cycle values="odd-row,even-row"}">
		    <td>{$row.sort_name}</td>
                    <td>{$row.grant_type}</td>
                    <td>{$row.program_name}</td>
                    <td>{$row.grant_application_received_date|truncate:10:''|crmDate}</td>
                    <td>{$row.grant_amount_total|crmMoney}</td>
                    <td>{$row.grant_status}</td>
                    <td>{$row.action}</td>
                </tr>
            {/foreach}
        </table>
        {/strip}
    {else}
        <div class="messages status">
           <div class="icon inform-icon"></div>
                    {ts}There are no grants on record for you.{/ts}
        </div>
    {/if}
</div>

