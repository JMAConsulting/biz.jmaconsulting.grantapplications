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
class CRM_Grant_Form_GrantPage_Settings extends CRM_Grant_Form_GrantPage {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_id) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $this->_id, 'title');
      CRM_Utils_System::setTitle(ts('Title and Settings') . " ($title)");

      $module = 'on_behalf';
      $ufJoinDAO = new CRM_Core_DAO_UFJoin();
      $ufJoinDAO->module = $module;
      $ufJoinDAO->entity_id = $this->_id;
      $ufJoinDAO->entity_table = 'civicrm_grant_app_page';
      if ($ufJoinDAO->find(TRUE)) {
        $jsonData = CRM_Contribute_BAO_ContributionPage::formatModuleData($ufJoinDAO->module_data, TRUE, $module);
        $defaults['onbehalf_profile_id'] = $ufJoinDAO->uf_group_id;
        $defaults = array_merge($defaults, $jsonData);
        $defaults['is_organization'] = $ufJoinDAO->is_active;
      }
      else {
        $ufGroupDAO = new CRM_Core_DAO_UFGroup();
        $ufGroupDAO->name = 'on_behalf_organization';
        if ($ufGroupDAO->find(TRUE)) {
          $defaults['onbehalf_profile_id'] = $ufGroupDAO->id;
        }
        $defaults['for_organization'] = ts('I am applying for grant on behalf of an organization.');
        $defaults['is_for_organization'] = 1;
      }
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->_first = TRUE;
    $attributes = CRM_Core_DAO::getAttribute('CRM_Grant_DAO_GrantApplicationPage');

    // name
    $this->add('text', 'title', ts('Title'), $attributes['title'], TRUE);
    $this->addSelect('grant_type_id', array(), TRUE);

    // Check if grant program extension is enabled
    $enabled = CRM_Grantapplications_BAO_GrantApplicationProfile::checkRelatedExtensions('biz.jmaconsulting.grantprograms');
    if ($enabled) {
      $programs = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
      $this->add('select', 'grant_program_id',
        ts('Grant Program'),
        $programs,
        TRUE
      );
    }

    $this->add('wysiwyg', 'intro_text', ts('Introductory Message'), $attributes['intro_text']);

    $this->add('wysiwyg', 'footer_text', ts('Footer Message'), $attributes['footer_text']);

    //Register schema which will be used for OnBehalOf and HonorOf profile Selector
    CRM_UF_Page_ProfileEditor::registerSchemas(array('OrganizationModel', 'HouseholdModel'));

    // is on behalf of an organization ?
    $this->addElement('checkbox', 'is_organization', ts('Allow individuals to apply for grants on behalf of an organization?'), NULL, array('onclick' => "showHideByValue('is_organization',true,'for_org_text','table-row','radio',false);showHideByValue('is_organization',true,'for_org_option','table-row','radio',false);"));

    $coreTypes = array('Contact', 'Organization');

    $entities[] = array(
      'entity_name' => array('contact_1'),
      'entity_type' => 'OrganizationModel',
    );
    // collect default amount
    $this->add('text', 'default_amount', ts('Default Amount'), array('size' => 8, 'maxlength' => 12));
    $this->addRule('default_amount', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    // is this page active ?
    $this->addElement('checkbox', 'is_active', ts('Is this Grant Aplication Page Active?'));

    $allowCoreTypes = array_merge($coreTypes, CRM_Contact_BAO_ContactType::subTypes('Organization'));
    $allowSubTypes = array();

    $this->addProfileSelector('onbehalf_profile_id', ts('Organization Profile'), $allowCoreTypes, $allowSubTypes, $entities);

    $options   = array();
    $options[] = $this->createElement('radio', NULL, NULL, ts('Optional'), 1);
    $options[] = $this->createElement('radio', NULL, NULL, ts('Required'), 2);
    $this->addGroup($options, 'is_for_organization', '');
    $this->add('textarea', 'for_organization', ts('On behalf of Label'), array('rows' => 2, 'cols' => 50));
    // add optional start and end dates
    $this->addDateTime('start_date', ts('Start Date'));
    $this->addDateTime('end_date', ts('End Date'));

    $this->addFormRule(array('CRM_Grant_Form_GrantPage_Settings', 'formRule'), $this);

    parent::buildQuickForm();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @param $files
   * @param $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    $errors = array();
    // Check if grant program extension is enabled
    $enabled = CRM_Grantapplications_BAO_GrantApplicationProfile::checkRelatedExtensions('biz.jmaconsulting.grantprograms');
    if ($enabled) {
      $grantType = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantProgram', $values['grant_program_id'], 'grant_type_id');
      if ($grantType != $values['grant_type_id']) {
        $errors['grant_program_id'] = ts("Please select a Grant Program which uses the same grant type.");
      }
    }

    //CRM-4286
    if (strstr($values['title'], '/')) {
      $errors['title'] = ts("Please do not use '/' in Title");
    }

    // ensure on-behalf-of profile meets minimum requirements
    if (!empty($values['is_organization'])) {
      if (empty($values['onbehalf_profile_id'])) {
        $errors['onbehalf_profile_id'] = ts('Please select a profile to collect organization information on this contribution page.');
      }
      else {
        $requiredProfileFields = array('organization_name', 'email');
        if (!CRM_Core_BAO_UFGroup::checkValidProfile($values['onbehalf_profile_id'], $requiredProfileFields)) {
          $errors['onbehalf_profile_id'] = ts('Profile does not contain the minimum required fields for an On Behalf Of Organization');
        }
      }
    }
    $start = CRM_Utils_Date::processDate($values['start_date']);
    $end = CRM_Utils_Date::processDate($values['end_date']);
    if (($end < $start) && ($end != 0)) {
      $errors['end_date'] = ts('End date should be after Start date.');
    }
    return $errors;
  }

  public function submit($params, $isTest = FALSE) {
    // we do this in case the user has hit the forward/back button
    if (!empty($this->_id)) {
      $params['id'] = $this->_id;
    }
    else {
      $params['created_id'] = CRM_Core_Session::singleton()->get('userID');
      $params['created_date'] = date('YmdHis');
      $params['currency'] = CRM_Core_Config::singleton()->defaultCurrency;
    }

    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['default_amount'] = CRM_Utils_Rule::cleanMoney($params['default_amount']);

    $params['is_for_organization'] = !empty($params['is_organization']) ? CRM_Utils_Array::value('is_for_organization', $params, FALSE) : 0;
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time'], TRUE);
    $params['end_date'] = CRM_Utils_Date::processDate($params['end_date'], $params['end_date_time'], TRUE);

    $dao = CRM_Grant_BAO_GrantApplicationPage::create($params);

    // make entry in UF join table for onbehalf of org profile
    $ufJoinParams = array(
      'is_organization' => array(
        'module' => 'on_behalf',
        'entity_table' => 'civicrm_grant_app_page',
        'entity_id' => $dao->id,
      ),
    );

    foreach ($ufJoinParams as $index => $ufJoinParam) {
      if (!empty($params[$index])) {
        // first delete all past entries
        CRM_Core_BAO_UFJoin::deleteAll($ufJoinParam);
        $ufJoinParam['uf_group_id'] = $params[$index];
        $ufJoinParam['weight'] = 1;
        $ufJoinParam['is_active'] = 1;
        $ufJoinParam['uf_group_id'] = $params['onbehalf_profile_id'];
        $ufJoinParam['module_data'] = CRM_Contribute_BAO_ContributionPage::formatModuleData($params, FALSE, 'on_behalf');

        CRM_Core_BAO_UFJoin::create($ufJoinParam);
      }
      else {
        $params['for_organization'] = NULL;

        $ufId = CRM_Core_BAO_UFJoin::findJoinEntryId($ufJoinParam);
        if ($ufId) {
          $ufJoinParam['uf_group_id'] = CRM_Core_BAO_UFJoin::findUFGroupId($ufJoinParam);
          $ufJoinParam['is_active'] = 0;
          CRM_Core_BAO_UFJoin::create($ufJoinParam);
        }
      }
    }

    if ($isTest) {
      return $dao->id;
    }

    $this->set('id', $dao->id);
    if ($this->_action & CRM_Core_Action::ADD) {
      $url = 'civicrm/admin/grant/draft';
      $urlParams = "action=update&reset=1&id={$dao->id}";
      // special case for 'Save and Done' consistency.
      if ($this->controller->getButtonName('submit') == '_qf_Amount_upload_done') {
        $url = 'civicrm/admin/grant';
        $urlParams = 'reset=1';
        CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
          array(1 => $this->getTitle())
        ), ts('Saved'), 'success');
      }

      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    $this->submit($params);

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Title and Settings');
  }

}
