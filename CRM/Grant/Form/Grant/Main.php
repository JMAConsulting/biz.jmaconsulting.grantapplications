<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.4                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for processing a grant application
 *
 */
class CRM_Grant_Form_Grant_Main extends CRM_Grant_Form_GrantBase {

  public $_relatedOrganizationFound;
  public $_onBehalfRequired = FALSE;
  public $_onbehalf = FALSE;
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
    $reset       = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $mainDisplay = CRM_Utils_Request::retrieve('_qf_Main_display', 'Boolean', CRM_Core_DAO::$_nullObject);

    if ($reset) {
      $this->assign('reset', $reset);
    }

    if ($mainDisplay) {
      $this->assign('mainDisplay', $mainDisplay);
    }

    // Checking if is Save as Draft is enabled
    if (!empty($this->_values['is_draft'])) {
      $this->_isDraft = TRUE;
    }

    // Possible values for 'is_for_organization':
    // * 0 - org profile disabled
    // * 1 - org profile optional
    // * 2 - org profile required
    $this->_onbehalf = FALSE;
    if (!empty($this->_values['is_for_organization'])) {
      if ($this->_values['is_for_organization'] == 2) {
        $this->_onBehalfRequired = TRUE;
      }
      // Add organization profile if 1 of the following are true:
      // If the org profile is required
      if ($this->_onBehalfRequired ||
        // Or we are building the form for the first time
        empty($_POST) ||
        // Or the user has submitted the form and checked the "On Behalf" checkbox
        !empty($_POST['is_for_organization'])
      ) {
        $this->_onbehalf = TRUE;
        CRM_Grant_Form_Grant_OnBehalfOf::preProcess($this);
      }
    }
    $this->assign('onBehalfRequired', $this->_onBehalfRequired);

    if (!empty($this->_values['intro_text'])) {
      $this->assign('intro_text', $this->_values['intro_text']);
    }

    $qParams = "reset=1&amp;id={$this->_id}";
    
    $this->assign( 'qParams' , $qParams );

    if (CRM_Utils_Array::value('footer_text', $this->_values)) {
      $this->assign('footer_text', $this->_values['footer_text']);
    }

    //CRM-5001
    if (CRM_Utils_Array::value('is_for_organization', $this->_values)) {
      $msg = ts('Mixed profile not allowed for on behalf of registration/sign up.');
      if ($preID = CRM_Utils_Array::value('custom_pre_id', $this->_values)) {
        $preProfile = CRM_Core_BAO_UFGroup::profileGroups($preID);
        foreach (array(
            'Individual', 'Organization', 'Household') as $contactType) {
          if (in_array($contactType, $preProfile) &&
            (in_array('Membership', $preProfile) ||
              in_array('Contribution', $preProfile)
            )
          ) {
            CRM_Core_Error::fatal($msg);
          }
        }
      }

      if ($postID = CRM_Utils_Array::value('custom_post_id', $this->_values)) {
        $postProfile = CRM_Core_BAO_UFGroup::profileGroups($postID);
        foreach (array(
            'Individual', 'Organization', 'Household') as $contactType) {
          if (in_array($contactType, $postProfile) &&
            (in_array('Membership', $postProfile) ||
              in_array('Contribution', $postProfile)
            )
          ) {
            CRM_Core_Error::fatal($msg);
          }
        }
      }
    }
  }

  function setDefaultValues() {
    // check if the user is registered and we have a contact ID
    $contactID = $this->getContactID();

    if (!empty($contactID)) {
      $options = array();
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

      $names = array(
        'first_name', 'middle_name', 'last_name', "street_address-Primary", "city-Primary",
        "postal_code-Primary", "country_id-Primary", "state_province_id-Primary",
      );
      foreach ($names as $name) {
        $fields[$name] = 1;
      }
      $fields["state_province-Primary"] = 1;
      $fields["country-Primary"] = 1;
      $fields['email-Primary'] = 1;
     
      CRM_Core_BAO_UFGroup::setProfileDefaults($contactID, $fields, $this->_defaults);

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

    $config = CRM_Core_Config::singleton();

    //process drafts
    if ($gid = CRM_Utils_Request::retrieve('gid', 'Positive')) {
      $ssParams = array();
      $grantStatusID = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $gid, 'status_id');
      $grantStatus = CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'status_id', array('labelColumn' => 'name'));
      if ($grantStatusID != array_search('Draft', $grantStatus)) {
        CRM_Core_Error::fatal(ts('This grant application has already been submitted.'));
      }
      $ssParams['id'] = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_saved_search WHERE form_values LIKE "%\"grant_id\";i:'.$gid.'%"');
      CRM_Contact_BAO_SavedSearch::retrieve($ssParams, $savedSearch);
      $this->_defaults = array_replace( $this->_defaults, unserialize($savedSearch['form_values']) );
    }
    return $this->_defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
   
    $config = CRM_Core_Config::singleton();

    if ($this->_onbehalf) {
      CRM_Grant_Form_Grant_OnBehalfOf::buildQuickForm($this);
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', "email-Primary",
      ts('Email Address'), array(
        'size' => 30, 'maxlength' => 60), TRUE
    );
 
    $this->addRule("email-Primary", ts('Email is not valid.'), 'email');
 
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre');
    $this->buildCustom($this->_values['custom_post_id'], 'customPost');
    
    if ( !CRM_Utils_Array::value('amount_requested', $this->_fields) && CRM_Utils_Array::value('default_amount', $this->_values) ){
        $this->assign('defaultAmount', $this->_values['default_amount']);
        $this->add('hidden', "default_amount_hidden",
          $this->_values['default_amount'] ? $this->_values['default_amount'] : '0', '', FALSE
        );
    } else if ( !CRM_Utils_Array::value('default_amount', $this->_fields) && !CRM_Utils_Array::value('amount_requested', $this->_fields) ) {
        $this->assign('defaultAmount', '0.00');
        $this->add('hidden', "default_amount_hidden",
          '0.00', '', FALSE
        );
    }
    $this->add('hidden', "grant_id",
      NULL, '', FALSE
    );
    $this->add('hidden', "is_draft", '0', '', FALSE);
    if ( CRM_Utils_Array::value('amount_requested', $this->_fields) ) {
      $this->addRule('amount_requested', ts('Please enter a valid amount (numbers and decimal point only).'), 'money');
    }

    if ($this->_values['is_for_organization']) {
      $this->buildOnBehalfOrganization();
    }

    if ( !empty( $this->_fields ) ) {
      $profileAddressFields = array();
      $numericFields['amount_total'] = 'Float';
      foreach( $this->_fields as $key => $value ) {
        CRM_Core_BAO_UFField::assignAddressField($key, $profileAddressFields, array('uf_group_id' => $this->_values['custom_pre_id']));
        $dataType = CRM_Utils_Array::value('data_type', $value);
        if (in_array($dataType, array('Float', 'Int', 'Money'))) {
          if ($dataType == 'Money') {
            $dataType = 'Float';
          }          
          $numericFields[$value['name']] = $dataType;
        }
      }
      $this->assign('numericFields', json_encode($numericFields));
      $this->set('profileAddressFields', $profileAddressFields);
    }

    //to create an cms user
    if (!$this->_userID) {
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
      $groupTree = &CRM_Core_BAO_CustomGroup::getTree("Grant", $this, $gid, 0, $grantType);
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
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
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
   * build elements to enable grant application on behalf of an organization.
   *
   * @access public
   */
  function buildOnBehalfOrganization() {
  
    if (!$this->_onBehalfRequired) {
      $this->addElement('checkbox', 'is_for_organization',
        $this->_values['for_organization'],
        NULL, array('onclick' => "showOnBehalf( );")
      );
    }

    $this->assign('is_for_organization', TRUE);
    $this->assign('urlPath', 'civicrm/grant/transact');
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
    // we first reset the confirm page so it accepts new values
    $this->controller->resetPage('Confirm');

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
   
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('save')) {
      $this->set('is_draft', 1);
    }
    else {
      $this->set('is_draft', 0);
    }
    
    if (CRM_Utils_Array::value('default_amount_hidden', $params) > 0 && !CRM_Utils_Array::value('amount_requested', $params)) {  
        $this->set('default_amount', $params['default_amount_hidden']);
    } elseif (CRM_Utils_Array::value('amount_requested', $params))  {
        $this->set('default_amount', $params['amount_requested']);
    }
  }
}

