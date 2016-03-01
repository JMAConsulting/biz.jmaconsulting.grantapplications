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
 */

/**
 * This class contains Grant Application Page related functions.
 */
class CRM_Grant_BAO_GrantApplicationPage extends CRM_Grant_DAO_GrantApplicationPage {

  /**
   * Creates a grant application page.
   *
   * @param array $params
   *
   * @return object CRM_Grant_DAO_GrantApplicationPage object
   */
  public static function create(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'GrantApplicationPage', CRM_Utils_Array::value('id', $params), $params);
    $dao = new CRM_Grant_DAO_GrantApplicationPage();
    $dao->copyValues($params);
    $dao->save();
    CRM_Utils_Hook::post($hook, 'GrantApplicationPage', $dao->id, $dao);
    return $dao;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @deprecated - this bypasses hooks.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Grant_DAO_GrantApplicationPage', $id, 'is_active', $is_active);
  }

  public function deleteGrantApplicationPage($id, $title) {
    $transaction = new CRM_Core_Transaction();
    
    // first delete the join entries associated with this grant application page
    $dao = new CRM_Core_DAO_UFJoin();
    
    $params = array(
      'entity_table' => 'civicrm_grant_app_page',
      'entity_id' => $id,
    );
    $dao->copyValues($params);
    $dao->delete();
           
    // finally delete the grant application page
    $dao = new CRM_Grant_DAO_GrantApplicationPage();
    $dao->id = $id;
    $dao->delete();

    $transaction->commit();

    CRM_Core_Session::setStatus(ts('The Grant Application page \'%1\' has been deleted.', array(1 => $title)));
  }

  /**
   * Load values for a contribution page.
   *
   * @param int $id
   * @param array $values
   */
  public static function setValues($id, &$values) {
    $modules = array('CiviGrant', 'on_behalf');
	$values['custom_pre_id'] = $values['custom_post_id'] = NULL;

    $params = array('id' => $id);

    CRM_Core_DAO::commonRetrieve('CRM_Grant_DAO_GrantApplicationPage', $params, $values);
    
    // get the profile ids
    $ufJoinParams = array(
      'entity_table' => 'civicrm_grant_app_page',
      'entity_id' => $id,
    );

    // retrieve profile id as also unserialize module_data corresponding to each $module
    foreach ($modules as $module) {
      $ufJoinParams['module'] = $module;
      $ufJoin = new CRM_Core_DAO_UFJoin();
      $ufJoin->copyValues($ufJoinParams);
      if ($module == 'CiviGrant') {
        $ufJoin->orderBy('weight asc');
        $ufJoin->find();
        while ($ufJoin->fetch()) {
          if ($ufJoin->weight == 1) {
            $values['custom_pre_id'] = $ufJoin->uf_group_id;
          }
          else {
            $values['custom_post_id'] = $ufJoin->uf_group_id;
          }
        }
      }
      else {
        $ufJoin->find(TRUE);
        if (!$ufJoin->is_active) {
          continue;
        }
        $params = CRM_Contribute_BAO_ContributionPage::formatModuleData($ufJoin->module_data, TRUE, $module);
        $values = array_merge($params, $values);
        $values['onbehalf_profile_id'] = $ufJoin->uf_group_id;
      }
    }
  }

  /**
   * Function to add activity for Grant
   *
   * @param object  $activity   (reference) particular component object
   * @param string  $activityType for Grant
   *
   *
   * @static
   * @access public
   */
  public static function addActivity(&$grant,
    $targetContactID = NULL,
    $activityType = 'Grant'
  ) {
    
    $subject = CRM_Utils_Money::format($grant->amount_total, $grant->currency);
    if (!empty($grant->source) && $grant->source != 'null') {
      $subject .= " - {$grant->source}";
    }
    $date = CRM_Utils_Date::isoToMysql($grant->application_received_date);
    $component = 'Grant';
    $activityParams = array(
      'source_contact_id' => $grant->contact_id,
      'source_record_id' => $grant->id,
      'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
        $activityType,
        'name'
      ),
      'subject' => $subject,
      'activity_date_time' => $date,
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
        'Completed',
        'name'
      ),
      'skipRecentView' => TRUE,
    );

    // create activity with target contacts
    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if ($id) {
      $activityParams['source_contact_id'] = $id;
      $activityParams['target_contact_id'][] = $grant->contact_id;
    }
    
    if ($targetContactID) {
      $activityParams['target_contact_id'][] = $targetContactID;
    }
    if (is_a(CRM_Activity_BAO_Activity::create($activityParams), 'CRM_Core_Error')) {
      CRM_Core_Error::fatal("Failed creating Activity for $component of id {$activity->id}");
      return FALSE;
    }
  }
  
  /**
   * Function to send the emails
   *
   * @param int     $contactID         contact id
   * @param array   $values            associated array of fields
   * @param boolean $isTest            if in test mode
   * @param boolean $returnMessageText return the message text instead of sending the mail
   *
   * @return void
   * @access public
   * @static
   */
  public static function sendMail($contactID, &$values, $returnMessageText = FALSE, $fieldTypes = NULL) {
    $gIds = $params = array();
    $email = NULL;
    if (isset($values['custom_pre_id'])) {
      $preProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_pre_id']);
      if ($preProfileType == 'Grant' && CRM_Utils_Array::value('grant_id', $values)) {
        $params['custom_pre_id'] = array(array('grant_id', '=', $values['grant_id'], 0, 0));
      }

      $gIds['custom_pre_id'] = $values['custom_pre_id'];
    }

    if (isset($values['custom_post_id'])) {
      $postProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_post_id']);
      if ($postProfileType == 'Grant' && CRM_Utils_Array::value('grant_id', $values)) {
        $params['custom_post_id'] = array(array('grant_id', '=', $values['grant_id'], 0, 0));
      }

      $gIds['custom_post_id'] = $values['custom_post_id'];
    }
    
    if (!$returnMessageText && !empty($gIds)) {
      //send notification email if field values are set (CRM-1941)
      foreach ($gIds as $key => $gId) {
        if ($gId) {
          $email = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gId, 'notify');
          if ($email) {
            $val = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($gId, $contactID, CRM_Utils_Array::value($key, $params), true );
            CRM_Core_BAO_UFGroup::commonSendMail($contactID, $val);
          }
        }
      }
    }
    $onbehalfId = NULL;
    if (CRM_Utils_Array::value('is_for_organization', $values)
      && CRM_Utils_Array::value('grant_id', $values)) {
      $onbehalfId = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $values['grant_id'], 'contact_id' );
    }
    if (CRM_Utils_Array::value('is_email_receipt', $values) ||
      CRM_Utils_Array::value('onbehalf_dupe_alert', $values) ||
      $returnMessageText
    ) {
      $template = CRM_Core_Smarty::singleton();
      
      if (!$email) {
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      }
      if (empty($displayName)) {
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      }

      $userID = $contactID;
      if ($preID = CRM_Utils_Array::value('custom_pre_id', $values)) {
        if (CRM_Utils_Array::value('related_contact', $values)) {
          $preProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($preID);
          if (in_array('Individual', $preProfileTypes) || in_array('Contact', $preProfileTypes)) {
            //Take Individual contact ID
            $userID = CRM_Utils_Array::value('related_contact', $values);
          }
        }
        self::buildCustomProfile($preID, 'customPre', $userID, $template, $params['custom_pre_id'], $onbehalfId);
      }
      $userID = $contactID;
      if ($postID = CRM_Utils_Array::value('custom_post_id', $values)) {
        if (CRM_Utils_Array::value('related_contact', $values)) {
          $postProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($postID);
          if (in_array('Individual', $postProfileTypes) || in_array('Contact', $postProfileTypes)) {
            //Take Individual contact ID
            $userID = CRM_Utils_Array::value('related_contact', $values);
          }
        }
        self::buildCustomProfile($postID, 'customPost', $userID, $template, $params['custom_post_id'], $onbehalfId);
      }
      
      $title = isset($values['title']) ? $values['title'] : CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $values['id'], 'title');

      // set email in the template here
      $tplParams = array(
        'email' => $email,
        'receiptFromEmail' => CRM_Utils_Array::value('receipt_from_email', $values),
        'contactID' => $contactID,
        'displayName' => $displayName,
        'grantID' => CRM_Utils_Array::value('grant_id', $values),
        'title' => $title,
      );
    
      if ($grantTypeId = CRM_Utils_Array::value('grant_type_id', $values)) {
        $tplParams['grantTypeId'] = $grantTypeId;
        $tplParams['grantTypeName'] = CRM_Core_OptionGroup::getLabel('grant_type', $grantTypeId);
      }

      if ($grantApplicationPageId = CRM_Utils_Array::value('id', $values)) {
        $tplParams['grantApplicationPageId'] = $grantApplicationPageId;
      }
      $originalCCReceipt = CRM_Utils_Array::value('cc_receipt', $values);

      $sendTemplateParams = array(
        'groupName' => 'msg_tpl_workflow_grant',
        'valueName' => 'grant_online_receipt',
        'contactId' => $contactID,
        'tplParams' => $tplParams,
        'PDFFilename' => 'receipt.pdf',
      );

      if ($returnMessageText) {
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        return array(
          'subject' => $subject,
          'body' => $message,
          'to' => $displayName,
          'html' => $html,
        );
      }

      if ($values['is_email_receipt']) {
        $sendTemplateParams['from'] = CRM_Utils_Array::value('receipt_from_name', $values) . ' <' . $values['receipt_from_email'] . '>';
        $sendTemplateParams['toName'] = $displayName;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_receipt', $values);
        $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_receipt', $values);
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }

      // send duplicate alert, if dupe match found during on-behalf-of processing.
      if (!empty($values['onbehalf_dupe_alert'])) {
        $sendTemplateParams['groupName'] = 'msg_tpl_workflow_grant';
        $sendTemplateParams['valueName'] = 'grant_dupalert';
        $sendTemplateParams['from'] = ts('Automatically Generated') . " <{$values['receipt_from_email']}>";
        $sendTemplateParams['toName'] = CRM_Utils_Array::value('receipt_from_name', $values);
        $sendTemplateParams['toEmail'] = CRM_Utils_Array::value('receipt_from_email', $values);
        $sendTemplateParams['tplParams']['onBehalfID'] = $contactID;
        $sendTemplateParams['tplParams']['receiptMessage'] = $message;

        // fix cc and reset back to original, CRM-6976
        $sendTemplateParams['cc'] = $originalCCReceipt;

        CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }
    }
  }

  /**
   * Get the profile title and fields.
   *
   * @param int $gid
   * @param int $cid
   * @param array $params
   * @param array $fieldTypes
   *
   * @return array
   */
  protected static function getProfileNameAndFields($gid, $cid, &$params, $fieldTypes = array()) {
    $groupTitle = NULL;
    $values = array();
    if ($gid) {
      if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $cid)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW, NULL, NULL, FALSE, NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL);
        foreach ($fields as $k => $v) {
          if (!$groupTitle) {
            $groupTitle = $v["groupTitle"];
          }
          // suppress all file fields from display and formatting fields
          if (
            CRM_Utils_Array::value('data_type', $v, '') == 'File' ||
            CRM_Utils_Array::value('name', $v, '') == 'image_URL' ||
            CRM_Utils_Array::value('field_type', $v) == 'Formatting'
          ) {
            unset($fields[$k]);
          }

          if (!empty($fieldTypes) && (!in_array($v['field_type'], $fieldTypes))) {
            unset($fields[$k]);
          }
        }

        CRM_Core_BAO_UFGroup::getValues($cid, $fields, $values, FALSE, $params);
      }
    }
    return array($groupTitle, $values);
  }
  
  /*
     * Construct the message to be sent by the send function
     *
     */
  public function composeMessage($tplParams, $contactID, $isTest) {
    $sendTemplateParams = array(
      'groupName' => 'msg_tpl_workflow_grant',
      'valueName' => 'grant_online_receipt',
      'contactId' => $contactID,
      'tplParams' => $tplParams,
      'PDFFilename' => 'receipt.pdf',
    );
    if ($returnMessageText) {
      list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      return array(
        'subject' => $subject,
        'body' => $message,
        'to' => $displayName,
        'html' => $html,
      );
    }
  }

  /**
   * Function to get info for all sections enable/disable.
   *
   * @return array $info info regarding all sections.
   * @access public
   * @static
   */
  public static function getSectionInfo($grantAppPageIds = array(
    )) {
    $info = array();
    $whereClause = NULL;
    if (is_array($grantAppPageIds) && !empty($grantAppPageIds)) {
      $whereClause = 'WHERE civicrm_grant_app_page.id IN ( ' . implode(', ', $grantAppPageIds) . ' )';
    }
 
    $sections = array(
      'settings',
      'draft',
      'custom',
      'thankyou',
    );
    $query = "SELECT  civicrm_grant_app_page.id as id,
civicrm_grant_app_page.grant_type_id as settings,
civicrm_grant_app_page.is_draft as draft,  
civicrm_uf_join.id as custom,
civicrm_grant_app_page.thankyou_title as thankyou
FROM  civicrm_grant_app_page
LEFT JOIN  civicrm_uf_join ON ( civicrm_uf_join.entity_id = civicrm_grant_app_page.id 
AND civicrm_uf_join.entity_table = 'civicrm_grant_app_page'
AND module = 'CiviGrant'  AND civicrm_uf_join.is_active = 1 ) $whereClause";

    $grantAppPage = CRM_Core_DAO::executeQuery($query);
    while ($grantAppPage->fetch()) {
      if (!isset($info[$grantAppPage->id]) || !is_array($info[$grantAppPage->id])) {
        $info[$grantAppPage->id] = array_fill_keys(array_values($sections), FALSE);
      }
      foreach ($sections as $section) {
        if ($grantAppPage->$section) {
          $info[$grantAppPage->id][$section] = TRUE;
        }
      }
    }

    return $info;
  }
  
  /**
   * Function to alter CustomPre/CustomPost mail Params
   *
   * @access public
   * @static
   */
  public static function buildCustomProfile($gid, $name, $cid, &$template, &$params, $onBehalfId = NULL) {
    //Ignore fields for mails
    $fieldsToIgnore = array(
      'amount_granted' => 1,
      'application_received_date' => 1,
      'decision_date' => 1,
      'grant_money_transfer_date' => 1,
      'grant_due_date' => 1,
      'grant_report_received' => 1,
      'grant_type_id' => 1,
      'currency' => 1,
      'rationale' => 1,
      'status_id' => 1
    );
    
    if ($gid) {
      if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $cid)) {
        $values = array();
        $groupTitle = NULL;
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW, NULL, NULL, FALSE, NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL);
        $fields = array_diff_key($fields, $fieldsToIgnore);
        foreach ($fields as $k => $v) {
          if (!$groupTitle) {
            $groupTitle = $v["groupTitle"];
          }
          // suppress all file fields from display and formatting fields
          if (
            CRM_Utils_Array::value('data_type', $v, '') == 'File' ||
            CRM_Utils_Array::value('name', $v, '') == 'image_URL' ||
            CRM_Utils_Array::value('field_type', $v) == 'Formatting'
          ) {
            unset($fields[$k]);
          }

          if (!empty($fieldTypes) && (!in_array($v['field_type'], $fieldTypes))) {
            unset($fields[$k]);
          }
        }

        if ($groupTitle) {
          $template->assign($name . "_grouptitle", $groupTitle);
        }
        if ($onBehalfId) {
          $allFields = $fields;
          $grantParams = $params;
          $grantFields = $grantValues = $params = array();
          foreach($fields as $key => $value) {
            if ($value['field_type'] == 'Grant') {
              $grantFields[$key] = $value;
              unset($fields[$key]);
            }
          }
        }
          
        CRM_Core_BAO_UFGroup::getValues($cid, $fields, $values, FALSE, $params);
        if ($onBehalfId) {
          CRM_Core_BAO_UFGroup::getValues($onBehalfId, $grantFields, $grantValues, FALSE, $grantParams);
          $allValues = array_merge($values, $grantValues);
          $values = array();
          foreach($allFields as $key => $value) {
            if (CRM_Utils_Array::value($value['title'], $allValues)) {
              $values[$value['title']] = $allValues[$value['title']];
            }
          }
        }

        if (count($values)) {
          $template->assign($name, $values);
        }
      }
    }
  }
}

