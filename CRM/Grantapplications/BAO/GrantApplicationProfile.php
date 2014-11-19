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
 * This class contains function for Grant Applications
 *
 */
class CRM_Grantapplications_BAO_GrantApplicationProfile extends CRM_Core_DAO {
  static function getGrantFields() {
    $exportableFields = self::exportableFields('Grant');
    
    $skipFields = array('grant_id', 'grant_contact_id');
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
    ));
    
    $fields = CRM_Grant_DAO_Grant::export();
    $fields = array_merge($fields, $grantFields,
      CRM_Core_BAO_CustomField::getFieldsForImport('Grant'),
      CRM_Financial_DAO_FinancialType::export()
    );
    return $fields;
  }
  
  /**
   * Function to check if related Grant extension is enabled/disabled
   *
   * return array of enabled extensions 
   */
  function checkRelatedExtensions($name = 'biz.jmaconsulting.bugp') {
    $enableDisable = NULL;
    $sql = "SELECT is_active FROM civicrm_extension WHERE full_name IN ('{name}')";
    $enableDisable = CRM_Core_DAO::excuteQuery($sql);
    return $enableDisable;
  }
}