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
 * This class generates form components for processing a grant application
 *
 */
class CRM_Grant_Form_Grant_Main extends CRM_Grant_Form_GrantBase {

  public $_isDraft = FALSE;

  public $_defaults;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();

    // Make the grantPageID avilable to the template
    $this->assign('grantPageID', $this->_id);

    $this->assign('isConfirmEnabled', 1) ;

    // make sure we have right permission to edit this user
    $csContactID = $this->getContactID();

   $this->assign('reset', CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject));
   $this->assign('mainDisplay', CRM_Utils_Request::retrieve('_qf_Main_display', 'Boolean', CRM_Core_DAO::$_nullObject));

    // Checking if is Save as Draft is enabled
    if (!empty($this->_values['is_draft'])) {
      $this->_isDraft = TRUE;
    }

    if (!empty($this->_values['intro_text'])) {
      $this->assign('intro_text', $this->_values['intro_text']);
    }

    $qParams = "reset=1&amp;id={$this->_id}";

    $this->assign('qParams', $qParams);

    if (!empty($this->_values['footer_text'])) {
      $this->assign('footer_text', $this->_values['footer_text']);
    }
  }

  /**
   * Set the default values.
   */
  public function setDefaultValues() {
    // check if the user is registered and we have a contact ID
    $contactID = $this->getContactID();

    if (!empty($contactID)) {
      $fields = array();
      $removeCustomFieldTypes = array('Contribution', 'Membership', 'Activity', 'Participant', 'Grant');
      $grantFields = CRM_Grantapplications_BAO_GrantApplicationProfile::getGrantFields(FALSE);

      // remove component related fields
      foreach ($this->_fields as $name => $dontCare) {
        if (substr($name, 0, 7) == 'custom_') {
          $id = substr($name, 7);
          if (!CRM_Core_BAO_CustomGroup::checkCustomField($id, $removeCustomFieldTypes)) {
            continue;
          }
          // ignore component fields
        }
        elseif ( array_key_exists($name, $grantFields) || (stristr($name, 'amount_requested') )) {
          continue;
        }
        $fields[$name] = 1;
      }

      if (!empty($fields)) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($contactID, $fields, $this->_defaults);
      }

    }

    //set custom field defaults set by admin if value is not set
    if (!empty($this->_fields)) {
        //set custom field defaults
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          if (!isset($this->_defaults[$name])) {
              CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $this->_defaults,
                NULL, CRM_Profile_Form::MODE_REGISTER
               );
          }
        }
      }
    }

    // to process Custom data that are appended to URL
    $getDefaults = CRM_Core_BAO_CustomGroup::extractGetParams($this, "'Contact', 'Individual', 'Grant'");
    if (!empty($getDefaults)) {
      $this->_defaults = array_merge($this->_defaults, $getDefaults);
    }

    //process drafts
    $gid = CRM_Utils_Request::retrieve('gid', 'Positive');
    if ($gid) {
      $grantStatusID = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $gid, 'status_id');
      if ($grantStatusID != CRM_Core_PseudoConstant::getKey('CRM_Grant_BAO_Grant', 'status_id', 'Draft')) {
        throw new CRM_Core_Exception(ts('This grant application has already been submitted.'));
      }
      $savedSearch = civicrm_api3('SavedSearch', 'get', [
        'search_custom_id' => $gid,
        'api_entity' => 'civicrm_grant',
        'sequential' => 1,
      ]);
      if (!empty($savedSearch['values'][0]['form_values'])) {
        $this->_defaults = array_replace($this->_defaults, $savedSearch['values'][0]['form_values']);
        $numericFields = [
          'amount_total',
          'amount_requested',
        ];
        foreach ($numericFields as $field) {
          $this->_defaults[$field] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($this->_defaults[$field]);
        }
      }
    }
    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // build profiles first so that we can determine address fields etc
    // and then show copy address checkbox
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre');
    $this->buildCustom($this->_values['custom_post_id'], 'customPost');

    $this->buildComponentForm($this->_id, $this);

    if (!empty($this->_fields) && !empty($this->_values['custom_pre_id'])) {
      $profileAddressFields = array();
      foreach ($this->_fields as $key => $value) {
        CRM_Core_BAO_UFField::assignAddressField($key, $profileAddressFields, array('uf_group_id' => $this->_values['custom_pre_id']));
      }
      $this->set('profileAddressFields', $profileAddressFields);
    }

    $config = CRM_Core_Config::singleton();

    $contactID = $this->getContactID();
    if ($contactID) {
      $this->assign('contact_id', $contactID);
      $this->assign('display_name', CRM_Contact_BAO_Contact::displayName($contactID));
    }

    $this->applyFilter('__ALL__', 'trim');
    if ($this->_emailExists == FALSE) {
      $this->add('text', "email-Primary",
        ts('Email Address'),
        array('size' => 30, 'maxlength' => 60, 'class' => 'email'),
        TRUE
      );
      $this->assign('showMainEmail', TRUE);
      $this->addRule("email-Primary", ts('Email is not valid.'), 'email');
    }

    if (!CRM_Utils_Array::value('amount_requested', $this->_fields)) {
      $defaultAmount = isset($this->_values['default_amount']) ? $this->_values['default_amount'] : '0.00';
      $this->assign('defaultAmount', $defaultAmount);
      $this->add('hidden', "default_amount_hidden",
        $defaultAmount, '', FALSE
      );
    }
    $this->add('hidden', "grant_id",
      NULL, '', FALSE
    );
    $this->add('hidden', "is_draft", '0', '', FALSE);
    if ( CRM_Utils_Array::value('amount_requested', $this->_fields) ) {
      $this->addRule('amount_requested', ts('Please enter a valid amount (numbers and decimal point only).'), 'money');
    }

    if ( !empty( $this->_fields ) ) {
      $profileAddressFields = array();
      $numericFields = [
        'amount_total' => 'Float',
        'amount_requested' => 'Float',
        'amount_granted' => 'Float',
      ];
      foreach( $this->_fields as $key => $value ) {
        CRM_Core_BAO_UFField::assignAddressField($key, $profileAddressFields, array('uf_group_id' => $this->_values['custom_pre_id']));
        $dataType = CRM_Utils_Array::value('data_type', $value);
        if (in_array($dataType, array('Money'))) {
          if ($dataType == 'Money') {
            $dataType = 'Float';
          }
          $numericFields[$value['name']] = $dataType;
        }
      }
      $this->assign('numericFields', json_encode($numericFields));
      $this->set('profileAddressFields', $profileAddressFields);
      $this->set('numericFields', $numericFields);
    }

    //to create an cms user
    if (!$this->_contactID) {
      $createCMSUser = FALSE;

      if ($this->_values['custom_pre_id']) {
        $profileID = $this->_values['custom_pre_id'];
        $createCMSUser = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'is_cms_user');
      }

      if (!$createCMSUser &&
        $this->_values['custom_post_id']
      ) {
        if (!is_array($this->_values['custom_post_id'])) {
          $profileIDs = array($this->_values['custom_post_id']);
        }
        else {
          $profileIDs = $this->_values['custom_post_id'];
        }
        foreach ($profileIDs as $pid) {
          if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $pid, 'is_cms_user')) {
            $profileID = $pid;
            $createCMSUser = TRUE;
            break;
          }
        }
      }

      if ($createCMSUser) {
        CRM_Core_BAO_CMSUser::buildForm($this, $profileID, TRUE);
      }
    }
    $buttonArray[] = array(
      'type' => 'upload',
      'name' => ts('Submit'),
      'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
      'isDefault' => TRUE,
    );
    if ($this->_isDraft) {
       $buttonArray[] = array(
        'type' => 'save',
        'name' => ts('Save as Draft'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      );
    }
    $this->addButtons($buttonArray);
    // set up attachments
    if (CRM_Utils_Request::retrieve('gid', 'Positive')) {
      $gid = CRM_Utils_Request::retrieve('gid', 'Positive');
    }
    elseif (CRM_Utils_Array::value('grant_id', $this->_submitValues)) {
      $gid = $this->_submitValues['grant_id'];
    }
    if (!empty($gid)) {
      $grantType = CRM_Core_DAO::getFieldValue("CRM_Grant_DAO_Grant", $gid, "grant_type_id");
      $groupTree = CRM_Core_BAO_CustomGroup::getTree("Grant", $this, $gid, 0, $grantType);
      foreach ($groupTree as $field => $value) {
        if (isset($value['fields'])) {
          foreach ($value['fields'] as $key => $fields) {
            if (CRM_Utils_Array::value('html_type', $fields) == 'File' && isset($fields['customValue'][1]['fid'])) {
              $files['custom_'.$key]['displayURL'] = $fields['customValue'][1]['displayURL'];
              $files['custom_'.$key]['fileURL'] = $fields['customValue'][1]['fileURL'];
              $files['custom_'.$key]['fileName'] = $fields['customValue'][1]['fileName'];
              $files['custom_'.$key]['fid'] = $key;
            }
          }
        }
      }
      if (isset($files)) {
        $this->assign('fileFields', $files);
      }
    }
    $this->addFormRule(array('CRM_Grant_Form_Grant_Main', 'formRule'), $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Core_Form $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    if (array_key_exists('amount_requested', $fields)) {
      if (!is_numeric($fields['amount_requested'])) {
        $errors['amount_requested'] = ts('Please enter valid amount.');
      }
      if ($fields['amount_requested'] < 0) {
        $errors['amount_requested'] = ts('Requested amount has to be greater than zero.');
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Submit function.
   *
   * This is just a placeholder.
   *
   * @param array $params
   *   Submitted values.
   */
  public function submit($params) {
    return TRUE;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // we first reset the confirm page so it accepts new values
    $this->controller->resetPage('Confirm');

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
    $this->submit($params);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('save')) {
      $this->set('is_draft', 1);
    }
    else {
      $this->set('is_draft', 0);
    }

    if (CRM_Utils_Array::value('default_amount_hidden', $params) > 0 && !CRM_Utils_Array::value('amount_requested', $params)) {
        $this->set('default_amount', $params['default_amount_hidden']);
    }
    elseif (CRM_Utils_Array::value('amount_requested', $params))  {
        $this->set('default_amount', $params['amount_requested']);
    }
  }
}
