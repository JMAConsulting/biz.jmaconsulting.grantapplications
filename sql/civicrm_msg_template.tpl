-- +--------------------------------------------------------------------+
-- | CiviCRM version 4.5                                                |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2012                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
{* not sure how to define the below in Smarty, so doing it in PHP instead *}
{php}
  $ogNames = array(
    'grant'         => ts('Message Template Workflow for Grant',          array('escape' => 'sql')),
  );
  $ovNames = array(
    'grant' => array(
      'grant_online_receipt' => ts('Grants - Receipt (on-line)', array('escape' => 'sql')),
    ),
  );
  $smarty = CRM_Core_Smarty::singleton();
  $smarty->assign('ogNames',  $ogNames);
  $smarty->assign('ovNames',  $ovNames);
{/php}

INSERT INTO civicrm_option_group
  (name,                         {localize field='title'}title{/localize},            {localize field='description'}description{/localize},      is_reserved, is_active) VALUES
{foreach from=$ogNames key=name item=description name=for_groups}
    ('msg_tpl_workflow_{$name}', {localize}'{$description}'{/localize},               {localize}'{$description}'{/localize},                     1,           1) {if $smarty.foreach.for_groups.last};{else},{/if}
{/foreach}

{foreach from=$ogNames key=name item=description}
  SELECT @tpl_ogid_{$name} := MAX(id) FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_{$name}';
{/foreach}

INSERT INTO civicrm_option_value
  (option_group_id,        name,       {localize field='label'}label{/localize},   value,                                  weight) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=label name=for_values}
      (@tpl_ogid_{$gName}, '{$vName}', {localize}'{$label}'{/localize},            {$smarty.foreach.for_values.iteration}, {$smarty.foreach.for_values.iteration}) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
{/foreach}
{/foreach}

{foreach from=$ovNames key=gName item=ovs}
{foreach from=$ovs key=vName item=label}
    SELECT @tpl_ovid_{$vName} := MAX(id) FROM civicrm_option_value WHERE option_group_id = @tpl_ogid_{$gName} AND name = '{$vName}';
{/foreach}
{/foreach}
INSERT INTO civicrm_msg_template
  (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=title name=for_values}
      {fetch assign=subject file="`$currentDirectoryPath`/sql/message_templates/`$vName`_subject.tpl"}
      {fetch assign=text    file="`$currentDirectoryPath`/sql/message_templates/`$vName`_text.tpl"}
      {fetch assign=html    file="`$currentDirectoryPath`/sql/message_templates/`$vName`_html.tpl"}
      ('{$title}', '{$subject|escape:"quotes"}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 1,          0),
      ('{$title}', '{$subject|escape:"quotes"}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 0,          1) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
{/foreach}
{/foreach}