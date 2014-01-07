ALTER TABLE  `civicrm_grant_app_page` ADD  `is_for_organization` TINYINT( 4 ) NULL DEFAULT  '0' COMMENT 'if true, signup is done on behalf of an organization';

ALTER TABLE  `civicrm_grant_app_page` ADD  `for_organization` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'This text field is shown when is_for_organization is checked. For example - I am submitting grant application on behalf of an organization.';

SELECT @optionGroupId := id FROM `civicrm_option_group` WHERE `name` = 'activity_type';

SELECT @maxValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @optionGroupId;

SELECT @maxWeight := MAX( CAST( `weight` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @optionGroupId;

SELECT @activityTypeId := id FROM `civicrm_option_value` WHERE `name` = 'Grant';

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) VALUES
(@activityTypeId, @optionGroupId, 'Grant', @maxValue, 'Grant', NULL, 1, NULL, @maxWeight, 'Online Grant Application', 0, 1, 1, 5, NULL, NULL);

