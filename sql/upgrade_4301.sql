ALTER TABLE  `civicrm_grant_app_page` ADD `is_draft` TINYINT(4) DEFAULT NULL COMMENT 'Does this page have a Save as Draft button?' AFTER `default_amount`;

ALTER TABLE  `civicrm_grant_app_page` ADD `draft_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Title for Save as Draft page (header title tag, and display at the top of the page).' AFTER `is_draft`;

ALTER TABLE  `civicrm_grant_app_page` ADD `draft_text` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. Displayed above application fields on Save as Draft page' AFTER `draft_title`;

ALTER TABLE  `civicrm_grant_app_page` ADD `draft_footer` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. Displayed at the bottom of the Save as Draft page.' AFTER `draft_text`;

ALTER TABLE `civicrm_grant_app_page` ADD `confirm_text` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. displayed at the bottom of the confirmation page.' AFTER `thankyou_footer`, ADD `confirm_footer` text COLLATE utf8_unicode_ci COMMENT 'Text and html allowed. displayed at the bottom of the confirmation page.' AFTER `confirm_text`;

SELECT @dashId := id FROM `civicrm_option_group` WHERE `name` = 'user_dashboard_options';

SELECT @maxValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @dashId;

SELECT @maxWeight := MAX( CAST( `weight` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @dashId;

INSERT IGNORE INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `weight`, `description`, `is_active`) VALUES (@dashId, 'Grants', @maxValue, 'CiviGrant', @maxWeight, 'Grants on dashboard', 1);

SELECT @statusId := id FROM `civicrm_option_group` WHERE `name` = 'grant_status';

SELECT @maxValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @statusId;

SELECT @maxWeight := MAX( CAST( `weight` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @statusId;

INSERT IGNORE INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `weight`, `is_active`) VALUES (@statusId, 'Draft', @maxValue, 'Draft', @maxWeight, 1);

DELETE FROM civicrm_uf_field WHERE field_name = 'grant_application_received_date';

UPDATE civicrm_uf_field 
SET  field_name = 'amount_requested'
WHERE field_name = 'grant_amount_requested';
