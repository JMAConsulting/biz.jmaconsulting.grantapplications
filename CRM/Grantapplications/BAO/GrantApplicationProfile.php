<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * This class contains function for Grant Applications
 */
class CRM_Grantapplications_BAO_GrantApplicationProfile extends CRM_Core_DAO {

  public static function getGrantFields() {
    $exportableFields = self::exportableFields('Grant');
    
    $skipFields = array('grant_id', 'grant_contact_id');
    foreach ($skipFields as $field) {
      if (isset($exportableFields[$field])) {
        unset($exportableFields[$field]);
      }
    }
    return $exportableFields;
  }

  public static function exportableFields() {
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
  public static function checkRelatedExtensions($name = 'biz.jmaconsulting.bugp') {
    $enableDisable = NULL;
    $sql = "SELECT is_active FROM civicrm_extension WHERE full_name = '{name}'";
    $enableDisable = CRM_Core_DAO::singleValueQuery($sql);
    return $enableDisable;
  }

  /**
   * Calculate the profile type 'group_type' as per profile fields.
   *
   * @param int $gId
   *   Profile id.
   * @param bool $includeTypeValues
   * @param int $ignoreFieldId
   *   Ignore particular profile field.
   *
   * @return array
   *   list of calculated group type
   */
  public static function calculateGroupType($gId, $includeTypeValues = FALSE, $ignoreFieldId = NULL) {
    //get the profile fields.
    $ufFields = CRM_Core_BAO_UFGroup::getFields($gId, FALSE, NULL, NULL, NULL, TRUE, NULL, TRUE);
    return self::_calculateGroupType($ufFields, $includeTypeValues, $ignoreFieldId);
  }

  /**
   * Calculate the profile type 'group_type' as per profile fields.
   *
   * @param $ufFields
   * @param bool $includeTypeValues
   * @param int $ignoreFieldId
   *   Ignore perticular profile field.
   *
   * @return array
   *   list of calculated group type
   */
  public static function _calculateGroupType($ufFields, $includeTypeValues = FALSE, $ignoreFieldId = NULL) {
    $groupType = $groupTypeValues = $customFieldIds = [];
    if (!empty($ufFields)) {
      foreach ($ufFields as $fieldName => $fieldValue) {
        //ignore field from group type when provided.
        //in case of update profile field.
        if ($ignoreFieldId && ($ignoreFieldId == $fieldValue['field_id'])) {
          continue;
        }
        if (!in_array($fieldValue['field_type'], $groupType)) {
          $groupType[$fieldValue['field_type']] = $fieldValue['field_type'];
        }

        if ($includeTypeValues && ($fldId = CRM_Core_BAO_CustomField::getKeyID($fieldName))) {
          $customFieldIds[$fldId] = $fldId;
        }
      }
    }

    if (!empty($customFieldIds)) {
      $query = 'SELECT DISTINCT(cg.id), cg.extends, cg.extends_entity_column_id, cg.extends_entity_column_value FROM civicrm_custom_group cg LEFT JOIN civicrm_custom_field cf ON cf.custom_group_id = cg.id WHERE cg.extends_entity_column_value IS NOT NULL AND cf.id IN (' . implode(',', $customFieldIds) . ')';

      $customGroups = CRM_Core_DAO::executeQuery($query);
      while ($customGroups->fetch()) {
        if (!$customGroups->extends_entity_column_value) {
          continue;
        }

        $groupTypeName = "{$customGroups->extends}Type";
        if ($customGroups->extends == 'Participant' && $customGroups->extends_entity_column_id) {
          $groupTypeName = CRM_Core_PseudoConstant::getName('CRM_Core_DAO_CustomGroup', 'extends_entity_column_id', $customGroups->extends_entity_column_id);
        }

        foreach (explode(CRM_Core_DAO::VALUE_SEPARATOR, $customGroups->extends_entity_column_value) as $val) {
          if ($val) {
            $groupTypeValues[$groupTypeName][$val] = $val;
          }
        }
      }

      if (!empty($groupTypeValues)) {
        $groupType = array_merge($groupType, $groupTypeValues);
      }
    }

    return $groupType;
  }

  /**
   * Update the profile type 'group_type' as per profile fields including group types and group subtype values.
   * Build and store string like: group_type1,group_type2[VALUE_SEPERATOR]group_type1Type:1:2:3,group_type2Type:1:2
   *
   * FIELDS                                                   GROUP_TYPE
   * BirthDate + Email                                        Individual,Contact
   * BirthDate + Subject                                      Individual,Activity
   * BirthDate + Subject + SurveyOnlyField                    Individual,Activity\0ActivityType:28
   * BirthDate + Subject + SurveyOnlyField + PhoneOnlyField   (Not allowed)
   * BirthDate + SurveyOnlyField                              Individual,Activity\0ActivityType:28
   * BirthDate + Subject + SurveyOrPhoneField                 Individual,Activity\0ActivityType:2:28
   * BirthDate + SurveyOrPhoneField                           Individual,Activity\0ActivityType:2:28
   * BirthDate + SurveyOrPhoneField + SurveyOnlyField         Individual,Activity\0ActivityType:2:28
   * BirthDate + StudentField + Subject + SurveyOnlyField     Individual,Activity,Student\0ActivityType:28
   *
   * @param int $gId
   * @param array $groupTypes
   *   With key having group type names.
   *
   * @return bool
   */
  public static function updateGroupTypes($gId, $groupTypes = []) {
    if (!is_array($groupTypes) || !$gId) {
      return FALSE;
    }

    // If empty group types set group_type as 'null'
    if (empty($groupTypes)) {
      return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFGroup', $gId, 'group_type', 'null');
    }

    $componentGroupTypes = ['Contribution', 'Participant', 'Membership', 'Activity', 'Case', 'Grant'];
    $validGroupTypes = array_merge([
      'Contact',
      'Individual',
      'Organization',
      'Household',
    ], $componentGroupTypes, CRM_Contact_BAO_ContactType::subTypes());

    $gTypes = $gTypeValues = [];

    $participantExtends = ['ParticipantRole', 'ParticipantEventName', 'ParticipantEventType'];
    // Get valid group type and group subtypes
    foreach ($groupTypes as $groupType => $value) {
      if (in_array($groupType, $validGroupTypes) && !in_array($groupType, $gTypes)) {
        $gTypes[] = $groupType;
      }

      $subTypesOf = NULL;

      if (in_array($groupType, $participantExtends)) {
        $subTypesOf = $groupType;
      }
      elseif (strpos($groupType, 'Type') > 0) {
        $subTypesOf = substr($groupType, 0, strpos($groupType, 'Type'));
      }
      else {
        continue;
      }

      if (!empty($value) &&
        (in_array($subTypesOf, $componentGroupTypes) ||
          in_array($subTypesOf, $participantExtends)
        )
      ) {
        $gTypeValues[$subTypesOf] = $groupType . ":" . implode(':', $value);
      }
    }

    if (empty($gTypes)) {
      return FALSE;
    }

    // Build String to store group types and group subtypes
    $groupTypeString = implode(',', $gTypes);
    if (!empty($gTypeValues)) {
      $groupTypeString .= CRM_Core_DAO::VALUE_SEPARATOR . implode(',', $gTypeValues);
    }

    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFGroup', $gId, 'group_type', $groupTypeString);
  }

}
