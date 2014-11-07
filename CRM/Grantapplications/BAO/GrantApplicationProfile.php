<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class contains function for BUGP
 *
 */
class CRM_BUGP_BAO_GrantApplicationProfile extends CRM_Core_DAO {

  static function getProfileFields() {
    $exportableFields = self::exportableFields();
    //TODO:check if we need to ignore all these fields
    $skipFields = array('grant_id', 'grant_contact_id', 'grant_type', 'grant_note', 'grant_status');
    foreach ($skipFields as $field) {
      if (isset($exportableFields[$field])) {
        unset($exportableFields[$field]);
      }
    }
    return $exportableFields;
  }

  static function exportableFields() {
    $grantFields = array(
      'grant_status_id' => array(
      'title' => ts('Grant Status'),
      'name' => 'grant_status',
      'data_type' => CRM_Utils_Type::T_STRING,
    ),
    'amount_requested' => array(
      'title' => ts('Grant Amount Requested'),
      'name' => 'grant_amount_requested',
      'where' => 'civicrm_grant.amount_requested',
      'data_type' => CRM_Utils_Type::T_FLOAT,
    ),
    'grant_due_date' => array(
      'title' => ts('Grant Report Due Date'),
      'name' => 'grant_due_date',
      'data_type' => CRM_Utils_Type::T_DATE,
    ),
    'grant_note' => array(
      'title' => ts('Grant Note'),
      'name' => 'grant_note',
      'data_type' => CRM_Utils_Type::T_TEXT,
    ),
  );

  $fields = CRM_Grant_DAO_Grant::export();
  $fields = array_merge($fields, $grantFields,
    CRM_Core_BAO_CustomField::getFieldsForImport('Grant')
  );
  return $fields;
  }

/**
 * Function to get list of grant fields for profile
 * For now we only allow custom grant fields to be in
 * profile
 *
 * @return return the list of grant fields
 * @static
 * @access public
 */
  static function getGrantFields() {
    $grantFields = CRM_Grant_DAO_Grant::export();
    $grantFields = array_merge($grantFields, CRM_Core_OptionValue::getFields($mode = 'grant'));
       
    $grantFields = array_merge($grantFields, CRM_Financial_DAO_FinancialType::export());
    
    foreach ($grantFields as $key => $var) {
      $fields[$key] = $var;
    }

    return array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Grant'));
  }
}