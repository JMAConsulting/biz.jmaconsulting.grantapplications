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

  private static $_actionLinks;
  private static $_configureActionLinks;
  private static $_onlineGrantLinks;

  /**
   * Get the action links for this page.
   *
   * @return array $_actionLinks
   *
   */
  public static function &actionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_actionLinks)) {
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this Grant application page?');

      self::$_actionLinks = array(
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'title' => ts('Disable'),
          'ref' => 'crm-enable-disable',
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/grant/delete',
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ),
      );
    }
    return self::$_actionLinks;
  }

  /**
   * Get the configure action links for this page.
   *
   * @return array $_configureActionLinks
   *
   */
  public static function &configureActionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_configureActionLinks)) {
      $urlString = 'civicrm/admin/grant/';
      $urlParams = 'reset=1&action=update&id=%%id%%';

      self::$_configureActionLinks = array(
        CRM_Core_Action::ADD => array(
          'name' => ts('Info and Settings'),
          'title' => ts('Info and Settings'),
          'url' => $urlString . 'settings',
          'qs' => $urlParams,
          'uniqueName' => 'settings',
          'class' => 'no-popup',
        ),
        CRM_Core_Action::FOLLOWUP => array(
          'name' => ts('Save as Draft'),
          'title' => ts('Save as Draft'),
          'url' => $urlString . 'draft',
          'qs' => $urlParams,
          'uniqueName' => 'draft',
          'class' => 'no-popup',
        ),
        CRM_Core_Action::EXPORT => array(
          'name' => ts('Receipt'),
          'title' => ts('Receipt'),
          'url' => $urlString . 'thankyou',
          'qs' => $urlParams,
          'uniqueName' => 'thankyou',
          'class' => 'no-popup',
        ),
        CRM_Core_Action::PROFILE => array(
          'name' => ts('Profiles'),
          'title' => ts('Profiles'),
          'url' => $urlString . 'custom',
          'qs' => $urlParams,
          'uniqueName' => 'custom',
          'class' => 'no-popup',
        ),
      );
    }

    return self::$_configureActionLinks;
  }

  /**
   * Get the online grant links.
   *
   * @return array $_onlineGrantLinks.
   *
   */
  public static function onlineGrantLinks() {
    if (!isset(self::$_onlineGrantLinks)) {
      $urlString = 'civicrm/grant/transact';
      $urlParams = 'reset=1&id=%%id%%';
      self::$_onlineGrantLinks = array(
        CRM_Core_Action::RENEW => array(
          'name' => ts('Grant Application (Live)'),
          'title' => ts('Grant Application (Live)'),
          'url' => $urlString,
          'qs' => $urlParams,
          'fe' => TRUE,
          'uniqueName' => 'live_page',
          'class' => 'no-popup',
        ),
      );
    }

    return self::$_onlineGrantLinks;
  }

  /**
   * Format the configurable action links.
   *
   * @return array $formattedConfLinks.
   *
   */
  public static function formatConfigureLinks($sectionsInfo) {
    //build the formatted configure links.
    $formattedConfLinks = self::configureActionLinks();
    foreach ($formattedConfLinks as $act => & $link) {
      $sectionName = CRM_Utils_Array::value('uniqueName', $link);
      if (!$sectionName) {
        continue;
      }

      $classes = array();
      if (isset($link['class'])) {
        $classes = $link['class'];
      }

      if (!CRM_Utils_Array::value($sectionName, $sectionsInfo)) {
        $classes = array();
        if (isset($link['class'])) {
          $classes = array($link['class']);
        }
        $link['class'] = array_merge($classes, array('disabled'));
      }
    }

    return $formattedConfLinks;
  }

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
    $activityType = 'Grant'
  ) {

    $subject = CRM_Utils_Money::format($grant->amount_total, $grant->currency);
    if (!empty($grant->source) && $grant->source != 'null') {
      $subject .= " - {$grant->source}";
    }
    $date = CRM_Utils_Date::isoToMysql($grant->application_received_date);
    $activityParams = array(
      'source_record_id' => $grant->id,
      'activity_type_id' => $activityType,
      'subject' => $subject,
      'activity_date_time' => $date,
      'status_id' => 'Completed',
      'skipRecentView' => TRUE,
      'target_contact_id' => array($grant->contact_id),
    );
    // create activity with target contacts
    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if ($id) {
      $activityParams['source_contact_id'] = $id;
    }
    else {
      $activityParams['source_contact_id'] = $grant->contact_id;
    }

    civicrm_api3('Activity', 'create', $activityParams);
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
