ALTER TABLE  `civicrm_grant_app_page` ADD `is_for_organization` TINYINT( 4 ) NULL DEFAULT  '0' COMMENT 'if true, signup is done on behalf of an organization';

ALTER TABLE  `civicrm_grant_app_page` ADD `for_organization` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'This text field is shown when is_for_organization is checked. For example - I am submitting grant application on behalf of an organization.';

ALTER TABLE  `civicrm_grant_app_page` ADD `is_draft` TINYINT(4) DEFAULT NULL COMMENT 'Does this page have a Save as Draft button?' AFTER `default_amount`;

ALTER TABLE  `civicrm_grant_app_page` ADD `draft_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Title for Save as Draft page (header title tag, and display at the top of the page).' AFTER `is_draft`;

ALTER TABLE  `civicrm_grant_app_page` ADD `draft_text` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. Displayed above application fields on Save as Draft page' AFTER `draft_title`;

ALTER TABLE  `civicrm_grant_app_page` ADD `draft_footer` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. Displayed at the bottom of the Save as Draft page.' AFTER `draft_text`;

ALTER TABLE `civicrm_grant_app_page` ADD `confirm_text` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. displayed at the bottom of the confirmation page.' AFTER `thankyou_footer`, ADD `confirm_footer` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. displayed at the bottom of the confirmation page.' AFTER `confirm_text`;

SELECT @dashId := id FROM `civicrm_option_group` WHERE `name` = 'user_dashboard_options';

SELECT @maxValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @dashId;

SELECT @maxWeight := MAX( CAST( `weight` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @dashId;

INSERT IGNORE INTO `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `weight`, {localize field='description'}`description`{/localize}, `is_active`) VALUES (@dashId, {localize}'{ts escape="sql"}Grants{/ts}'{/localize}, @maxValue, 'CiviGrant', @maxWeight, {localize}'Grants on dashboard'{/localize}, 1);

SELECT @statusId := id FROM `civicrm_option_group` WHERE `name` = 'grant_status';

SELECT @maxValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @statusId;

SELECT @maxWeight := MAX( CAST( `weight` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @statusId;

INSERT IGNORE INTO `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `weight`, `is_active`) VALUES (@statusId, {localize}'{ts escape="sql"}Draft{/ts}'{/localize}, @maxValue, 'Draft', @maxWeight, 1);

