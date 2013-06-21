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

DROP TABLE IF EXISTS civicrm_grant_app_page;

DELETE FROM civicrm_option_value WHERE option_group_id IN (SELECT id FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_grant');

DELETE FROM civicrm_navigation WHERE name = 'New Grant Application Page';

DELETE FROM civicrm_msg_template WHERE workflow_id IN (SELECT id FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_grant');

DELETE FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_grant';

DELETE uj.*, uf.* FROM civicrm_uf_group g
LEFT JOIN civicrm_uf_join uj ON uj.uf_group_id = g.id
LEFT JOIN civicrm_uf_field uf ON uf.uf_group_id = g.id
WHERE g.group_type LIKE '%Grant%';

DELETE FROM civicrm_uf_group WHERE g.group_type LIKE '%Grant%';
