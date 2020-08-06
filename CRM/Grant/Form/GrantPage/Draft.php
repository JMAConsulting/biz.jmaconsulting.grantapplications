<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * form to configure thank-you messages and receipting features for an online grant application page
 */
class CRM_Grant_Form_GrantPage_Draft extends CRM_Grant_Form_GrantPage {

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $title = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $this->_id, 'title');
    CRM_Utils_System::setTitle(ts('Save as Draft (%1)', array(1 => $title)));
    return parent::setDefaultValues();
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->addElement('checkbox', 'is_draft', ts('Save as Draft Enabled?'), NULL, array('onclick' => "showSavedDetails()"));
    // thank you title and text (html allowed in text)
    $this->add('text', 'draft_title', ts('Save as Draft Title'), CRM_Core_DAO::getAttribute('CRM_Grant_DAO_GrantApplicationPage', 'draft_title'));
    $this->add('wysiwyg', 'draft_text', ts('Save as Draft Message'), CRM_Core_DAO::getAttribute('CRM_Grant_DAO_GrantApplicationPage', 'draft_text'));
    $this->add('wysiwyg', 'draft_footer', ts('Save as Draft Page Footer'), CRM_Core_DAO::getAttribute('CRM_Grant_DAO_GrantApplicationPage', 'draft_footer'));
    $this->addFormRule(array('CRM_Grant_Form_GrantPage_Draft', 'formRule'));

    parent::buildQuickForm();
  }

  /**
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static
  function formRule($values) {
    $errors = array();
    if (CRM_Utils_Array::value('is_draft', $values) && !CRM_Utils_Array::value('draft_title', $values)) {
      $errors['draft_title'] = ts('Draft Title is a required field');
    }
    return $errors;
  }

  public function submit($params) {
    $params['id'] = $this->_id;
    $params['is_draft'] = CRM_Utils_Array::value('is_draft', $params, FALSE);
    if (!$params['is_draft']) {
      $params['draft_title'] = NULL;
      $params['draft_text'] = NULL;
      $params['draft_footer'] = NULL;
    }

    CRM_Grant_BAO_GrantApplicationPage::create($params);
  }
  /**
   * Process the form
   *
   * @return void
   * @access public
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
   * @access public
   */
  public function getTitle() {
    return ts('Save as Draft');
  }
}
