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
 * This class generates form components for processing a contribution.
 */
class CRM_Grant_Form_GrantBase extends CRM_Core_Form {

  /**
   * the id of the grant application page that we are proceessing
   *
   * @var int
   */
  public $_id;

  /**
   * The mode that we are in
   *
   * @var string
   * @protect
   */
  public $_mode;
  /**
   * the values for the grant db object
   *
   * @var array
   * @protected
   */
  public $_values;
  /**
   * the values for the draft processing
   *
   * @var array
   * @protected
   */
  public $_draftProcessing;
  /**
   * the default values for the form
   *
   * @var array
   * @protected
   */
  public $_defaults;

  /**
   * The params submitted by the form and computed by the app
   *
   * @var array
   * @public
   */
  public $_params;

  /**
   * The fields involved in this grant application page
   *
   * @var array
   * @public
   */
  public $_fields;

  /**
   * The billing location id for this grant application page.
   *
   * @var int
   */
  public $_bltID;

  /**
   * Cache the amount to make things easier
   *
   * @var float
   * @public
   */
  public $_amount;

  protected $_userID;

  public $_action;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {

    // current grant application page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    if (!$this->_id) {
      // seems like the session is corrupted and/or we lost the id trail
      // lets just bump this to a regular session error and redirect user to main page
      $this->controller->invalidKeyRedirect();
    }

    // this was used prior to the cleverer this_>getContactID - unsure now
    $this->_userID = CRM_Core_Session::singleton()->get('userID');

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);
    // Grant Application page values are cleared from session, so can't use normal Printer Friendly view.
    // Use Browser Print instead.
    $this->assign('browserPrint', TRUE);

    // action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);

    // current mode
    $this->_mode = ($this->_action == 1024) ? 'test' : 'live';

    $this->_values = $this->get('values');
    $this->_fields = $this->get('fields');
    $this->assign('title', $this->_values['title']);
    CRM_Utils_System::setTitle($this->_values['title']);
    if (!$this->_values) {
      // get all the values from the dao object
      $this->_values = array();
      $this->_fields = array();

      CRM_Grant_BAO_GrantApplicationPage::setValues($this->_id, $this->_values);
      $this->assign('title', $this->_values['title']);

      CRM_Utils_System::setTitle($this->_values['title']);
      // check if form is active
      if (!CRM_Utils_Array::value('is_active', $this->_values)) {
        // form is inactive, die a fatal death
        CRM_Core_Error::fatal(ts('The page you requested is currently unavailable.'));
      }
      
      if ($this->_values['custom_pre_id']) {
        $preProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_pre_id']);
      }

      if ($this->_values['custom_post_id']) {
        $postProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_post_id']);
      }

      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);
    }
      
    $this->assign('is_email_receipt', $this->_values['is_email_receipt']);

    //assign cancelSubscription URL to templates
    $this->assign('cancelSubscriptionUrl',
      CRM_Utils_Array::value('cancelSubscriptionUrl', $this->_values)
    );
  
    $this->_defaults = array();

    $this->_amount = $this->get('amount');

    //CRM-6907
    $config = CRM_Core_Config::singleton();
    $config->defaultCurrency = CRM_Utils_Array::value('currency',
      $this->_values,
      $config->defaultCurrency
    );
  }

  /**
   * set the default values
   *
   * @return void
   * @access public
   */
  function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * assign the minimal set of variables to the template
   *
   * @return void
   * @access public
   */
  function assignToTemplate() {
      $vars = array(
      'default_amount_hidden'
    );

    $config = CRM_Core_Config::singleton();
 
    if (CRM_Utils_Array::value('default_amount_hidden', $this->_params)) {
      $this->assign('default_amount_hidden', $this->_params['default_amount_hidden']);
    }

    // assign the address formatted up for display
    $addressParts = array(
      "street_address-{$this->_bltID}",
      "city-{$this->_bltID}",
      "postal_code-{$this->_bltID}",
      "state_province-{$this->_bltID}",
      "country-{$this->_bltID}",
    );

    $addressFields = array();
    foreach ($addressParts as $part) {
      list($n, $id) = explode('-', $part);
      $addressFields[$n] = CRM_Utils_Array::value('billing_' . $part, $this->_params);
    }

    $this->assign('address', CRM_Utils_Address::format($addressFields));

    if (!empty($this->_params['onbehalf_profile_id']) && !empty($this->_params['onbehalf'])) {
      $this->assign('onBehalfName', $this->_params['organization_name']);
      $locTypeId = array_keys($this->_params['onbehalf_location']['email']);
      $this->assign('onBehalfEmail', $this->_params['onbehalf_location']['email'][$locTypeId[0]]['email']);
    }
    $this->assign('email',
      $this->controller->exportValue('Main', "email-{$this->_bltID}")
    );

    // also assign the receipt_text
    if (isset($this->_values['receipt_text'])) {
      $this->assign('receipt_text', $this->_values['receipt_text']);
    }
  }

  /**
   * Add the custom fields.
   *
   * @param int $id
   * @param string $name
   * @param bool $viewOnly
   * @param null $profileContactType
   * @param array $fieldTypes
   */
  public function buildCustom($id, $name, $viewOnly = FALSE, $profileContactType = NULL, $fieldTypes = NULL) {
    if ($id) {
      $contactID = $this->getContactID();

      // we don't allow conflicting fields to be
      // configured via profile - CRM 2100
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
        'grant_status_id' => 1,
      );

      $fields = NULL;
      if ($contactID && CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
          NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
        );
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
          NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
        );
      }

      if ($fields) {
        // unset any email-* fields since we already collect it, CRM-2888
        foreach (array_keys($fields) as $fieldName) {
          if (substr($fieldName, 0, 6) == 'email-' && !in_array($profileContactType, array('honor', 'onbehalf'))) {
            unset($fields[$fieldName]);
          }
        }

        if (array_intersect_key($fields, $fieldsToIgnore)) {
          $fields = array_diff_key($fields, $fieldsToIgnore);
          CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'), ts('Warning'), 'alert');
        }

        $fields = array_diff_key($fields, $this->_fields);

        CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
        $addCaptcha = FALSE;
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            if (CRM_Utils_Array::value('grant_id', $this->_params)) {
              $cFid = substr($field['name'], strpos($field['name'], "_") + 1);
              $cfParams = array('id' => $cFid);
              $cfDefaults = array();
              CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $cfParams, $cfDefaults);
              $columnName = $cfDefaults['column_name'];
            
              //table name of custom data
              $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
                $cfDefaults['custom_group_id'],
                'table_name', 'id');
            
              //query to fetch id from civicrm_file
              $query = "SELECT {$columnName} FROM {$tableName} where entity_id = {$this->_params['grant_id']}";
              $fileID = CRM_Core_DAO::singleValueQuery($query);
            }
            $this->_fields['fileFields'][$key]['noDisplay'] = TRUE;
            $subType = CRM_Contact_BAO_ContactType::subTypeInfo('Organization', TRUE);
            if (in_array($field['field_type'], array_keys($subType)) && CRM_Utils_Array::value('grant_id', $this->_params)) {
              $ssParams['id'] = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_saved_search WHERE form_values LIKE "%\"grant_id\";i:'.$this->_params['grant_id'].'%"');
              CRM_Contact_BAO_SavedSearch::retrieve($ssParams, $savedSearch);
              $grantParams = unserialize($savedSearch['form_values']);
              $this->_fields['fileFields'][$key] = array(
                'fileID' => $fileID,
                'entityID' => $grantParams['contactID'],
                'cfID' => $cFid,
              );
              unset($this->_fields['fileFields'][$key]['noDisplay']);
            }
            elseif ($field['field_type'] == 'Grant' && CRM_Utils_Array::value('grant_id', $this->_params)) {
              $this->_fields['fileFields'][$key] = array(
                'fileID' => $fileID,
                'entityID' => $this->_params['grant_id'],
                'cfID' => $cFid,
              );
              unset($this->_fields['fileFields'][$key]['noDisplay']);
            }
          }

          if ($profileContactType) {
            if (!empty($fieldTypes) && in_array($field['field_type'], $fieldTypes)) {
              CRM_Core_BAO_UFGroup::buildProfile(
                $this,
                $field,
                CRM_Profile_Form::MODE_CREATE,
                $contactID,
                TRUE,
                $profileContactType
              );
              $this->_fields[$profileContactType][$key] = $field;
            }
            else {
              unset($fields[$key]);
            }
          }
          else {
            CRM_Core_BAO_UFGroup::buildProfile(
              $this,
              $field,
              CRM_Profile_Form::MODE_CREATE,
              $contactID,
              TRUE
            );
            $this->_fields[$key] = $field;
          }
          // CRM-11316 Is ReCAPTCHA enabled for this profile AND is this an anonymous visitor
          if ($field['add_captcha'] && !$this->_userID) {
            $addCaptcha = TRUE;
          }
        }

        $this->assign($name, $fields);

        if ($addCaptcha && !$viewOnly) {
          $captcha = CRM_Utils_ReCAPTCHA::singleton();
          $captcha->add($this);
          $this->assign('isCaptcha', TRUE);
        }
      }
    }
  }

  /**
   * Check template file exists.
   *
   * @param string $suffix
   *
   * @return null|string
   */
  public function checkTemplateFileExists($suffix = NULL) {
    if ($this->_id) {
      $templateFile = "CRM/Grant/Form/Grant/{$this->_id}/{$this->_name}.{$suffix}tpl";
      $template = CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return NULL;
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  /**
   * Add the extra.tpl in.
   *
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we do not override - why isn't this done at the CRM_Core_Form level?
   *
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }
}

