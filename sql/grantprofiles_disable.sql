/**
 * This extension enhances profile creation for Grants.
 * 
 * Copyright (C) 2012 JMA Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Support: https://github.com/JMAConsulting/biz.jmaconsulting.grantprofiles/issues
 * 
 * Contact: info@jmaconsulting.biz
 *          JMA Consulting
 *          215 Spadina Ave, Ste 400
 *          Toronto, ON  
 *          Canada   M5T 2C7
 */

UPDATE civicrm_option_group SET is_active = 0 WHERE name = 'msg_tpl_workflow_grant';

UPDATE civicrm_uf_group SET is_active = 0 WHERE group_type LIKE '%Grant%';

UPDATE civicrm_option_value SET is_active = 0 WHERE option_group_id IN (SELECT id FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_grant');

UPDATE civicrm_navigation SET is_active = 0 WHERE name = 'New Grant Application Page';

UPDATE civicrm_msg_template SET is_active = 0 WHERE workflow_id IN (SELECT id FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_grant');

