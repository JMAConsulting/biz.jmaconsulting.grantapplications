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
 * Grant Application Page form.
 */
class CRM_Grant_Form_GrantPage extends CRM_Core_Form {

  /**
   * the page id saved to the session for an update
   *
   * @var int
   */
  protected $_id;

  /**
   * Are we in single form mode or wizard mode?
   *
   * @var boolean
   */
  protected $_single;

  /**
   * Is this the first page?
   *
   * @var boolean
   */
  protected $_first = FALSE;

  /**
   * Is this the last page?
   *
   * @var boolean
   */
  protected $_last = FALSE;
  
  protected $_values;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Grant';
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    // current grant application page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE, NULL, 'REQUEST'
    );
    $this->assign('grantApplicationPageID', $this->_id);

    // get the requested action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );

    // setting title and 3rd level breadcrumb for html page if application page exists
    if ($this->_id) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $this->_id, 'title');

      if ($this->_action == CRM_Core_Action::UPDATE) {
        $this->_single = TRUE;
      }
    }

    // CRM-16776 - show edit/copy/create buttons on Profiles Tab if user has required permission.
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->assign('perm', TRUE);
    }
    // set up tabs
    CRM_Grant_Form_GrantPage_TabHeader::build($this);

    if ($this->_action == CRM_Core_Action::UPDATE) {
      CRM_Utils_System::setTitle(ts('Configure Page - %1', array(1 => $title)));
    }
    elseif ($this->_action == CRM_Core_Action::VIEW) {
      CRM_Utils_System::setTitle(ts('Preview Page - %1', array(1 => $title)));
    }
    elseif ($this->_action == CRM_Core_Action::DELETE) {
      CRM_Utils_System::setTitle(ts('Delete Page - %1', array(1 => $title)));
    }

    //cache values.
    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = array();
      if (isset($this->_id) && $this->_id) {
        $params = array('id' => $this->_id);
        CRM_Core_DAO::commonRetrieve('CRM_Grant_DAO_GrantApplicationPage', $params, $this->_values);
        CRM_Grant_BAO_GrantApplicationPage::setValues($this->_id, $this->_values);
      }
      $this->set('values', $this->_values);
    }
    // Preload libraries required by the "Profiles" tab
    $schemas = array('IndividualModel', 'OrganizationModel', 'GrantModel');
    CRM_UF_Page_ProfileEditor::registerProfileScripts();
    CRM_UF_Page_ProfileEditor::registerSchemas($schemas);
    CRM_Core_Resources::singleton()->addScriptFile('biz.jmaconsulting.grantapplications', 'js/grantapplications.js');
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');

    $session = CRM_Core_Session::singleton();
    $this->_cancelURL = CRM_Utils_Array::value('cancelURL', $_POST);

    if (!$this->_cancelURL) {
      $this->_cancelURL = CRM_Utils_System::url('civicrm/grant', 'reset=1');
    }

    if ($this->_cancelURL) {
      $this->addElement('hidden', 'cancelURL', $this->_cancelURL);
    }

    if ($this->_single) {
      $buttons = array(
        array(
          'type' => 'next',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'upload',
          'name' => ts('Save and Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'subName' => 'done',
        ),
      );
      if (!$this->_last) {
        $buttons[] = array(
          'type' => 'submit',
          'name' => ts('Save and Next'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'subName' => 'savenext',
        );
      }
      $buttons[] = array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      );
      $this->addButtons($buttons);
    }
    else {
      $buttons = array();
      if (!$this->_first) {
        $buttons[] = array(
          'type' => 'back',
          'name' => ts('Previous'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        );
      }
      $buttons[] = array(
        'type' => 'next',
        'name' => ts('Continue'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      );
      $buttons[] = array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      );

      $this->addButtons($buttons);
    }

    $session->replaceUserContext($this->_cancelURL);
    // views are implemented as frozen form
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
      $this->addElement('button', 'done', ts('Done'), array('onclick' => "location.href='civicrm/admin/custom/group?reset=1&action=browse'"));
    }
    CRM_Core_Region::instance('page-body')->add(array(
       'template' => 'CRM/css/grantapplications.tpl',
    ));
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   *   defaults
   */
  public function setDefaultValues() {
    //some child classes calling setdefaults directly w/o preprocess.
    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = array();
      if (isset($this->_id) && $this->_id) {
        $params = array('id' => $this->_id);
        CRM_Core_DAO::commonRetrieve('CRM_Grant_DAO_GrantApplicationPage', $params, $this->_values);
      }
      $this->set('values', $this->_values);
    }
    $defaults = $this->_values;

    $config = CRM_Core_Config::singleton();
    if (isset($this->_id)) {

      // fix the display of the monetary value, CRM-4038
      if (isset($defaults['default_amount'])) {
        $defaults['default_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['default_amount']);
      }

      if (!empty($defaults['end_date'])) {
        list($defaults['end_date'], $defaults['end_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['end_date']);
      }

      if (!empty($defaults['start_date'])) {
        list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['start_date']);
      }
    }
    else {
      $defaults['is_active'] = 1;
      // set current date as start date
      list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults();
    }
    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $pageId = $this->get('id');
    //page is newly created.
    if ($pageId && !$this->_id) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/grant', 'reset=1'));
    }
  }

  public function endPostProcess() {
    // make submit buttons keep the current working tab opened, or save and next tab
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $className = CRM_Utils_String::getClassName($this->_name);

      //retrieve list of pages from StateMachine and find next page
      //this is quite painful because StateMachine is full of protected variables
      //so we have to retrieve all pages, find current page, and then retrieve next
      $stateMachine = new CRM_Grant_StateMachine_GrantPage($this);
      $states = $stateMachine->getStates();
      $statesList = array_keys($states);
      $currKey = array_search($className, $statesList);
      $nextPage = (array_key_exists($currKey + 1, $statesList)) ? $statesList[$currKey + 1] : '';

      //unfortunately, some classes don't map to subpage names, so we alter the exceptions
          
      if ($className) {
      
        $subPage     = strtolower($className);
        $subPageName = $className;
        $nextPage    = strtolower($nextPage);
        
        if ( $subPage == "custom" ) {
          $nextPage = "settings";
        }
      }

      CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
        array(1 => $subPageName)
      ), ts('Saved'), 'success');

      $this->postProcessHook();

      if ($this->controller->getButtonName('submit') == "_qf_{$className}_next") {
            CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/grant/{$subPage}",
           "action=update&reset= &id={$this->_id}"
          ));
      }
      elseif ($this->controller->getButtonName('submit') == "_qf_{$className}_submit_savenext") {
        if ($nextPage) {
          CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/grant/{$nextPage}",
              "action=update&reset=1&id={$this->_id}"
            ));
        }
        else {
          CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/admin/grant",
              "reset=1"
            ));
        }
      }
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url("civicrm/grant", 'reset=1'));
      }
    }
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  /**
   * @return string
   */
  public function getTemplateFileName() {
    if ($this->controller->getPrint() || $this->getVar('_id') <= 0 ||
      ($this->_action & CRM_Core_Action::DELETE) ||
      (CRM_Utils_String::getClassName($this->_name) == 'AddProduct')
    ) {
      return parent::getTemplateFileName();
    }
    else {
      // hack lets suppress the form rendering for now
      self::$_template->assign('isForm', FALSE);
      return 'CRM/Grant/Form/GrantPage/Tab.tpl';
    }
  }

}
