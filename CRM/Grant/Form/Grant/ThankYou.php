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
 * Form for thank-you / success page - 3rd step of online grant application process.
 */
class CRM_Grant_Form_Grant_ThankYou extends CRM_Grant_Form_GrantBase {
  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_params   = $this->get('params');
    if (CRM_Utils_Array::value('is_draft', $this->_params)) {
      $this->assign('thankyou_title', CRM_Utils_Array::value('draft_title', $this->_values));
      $this->assign('thankyou_text', CRM_Utils_Array::value('draft_text', $this->_values));
      $this->assign('thankyou_footer', CRM_Utils_Array::value('draft_footer', $this->_values));
      $this->assign('isDraft', 1);
      CRM_Utils_System::setTitle(CRM_Utils_Array::value('draft_title', $this->_values));
    }
    else {
      $this->assign('thankyou_title', CRM_Utils_Array::value('thankyou_title', $this->_values));
      $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->_values));
      $this->assign('thankyou_footer', CRM_Utils_Array::value('thankyou_footer', $this->_values));
      CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $this->_values));
    }
    // Make the grantPageID avilable to the template
    $this->assign('grantPageID', $this->_id);
    $this->assign('is_for_organization', CRM_Utils_Array::value('is_for_organization', $this->_params));
  }

  /**
   * Overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   */
  public function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->assignToTemplate();
    $option = $this->get('option');
    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));

    $params = $this->_params;
 
    $qParams = "reset=1&amp;id={$this->_id}";

    $this->assign('qParams', $qParams);

    $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);
    if (!empty($params['onbehalf'])) {
      $fieldTypes = array('Contact', 'Organization');
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes = array_merge($fieldTypes, $contactSubType);
      $fieldTypes = array_merge($fieldTypes, array('Grant'));

      $this->buildCustom($this->_values['onbehalf_profile_id'], 'onbehalfProfile', TRUE, 'onbehalf', $fieldTypes);
    }
    $this->assign('application_received_date',
      CRM_Utils_Date::mysqlToIso(CRM_Utils_Array::value('receive_date', $this->_params))
    );

    $defaults = array();
    $fields = array();
    foreach ($this->_fields as $name => $dontCare) {
      $fields[$name] = 1;
    }
    $fields['state_province'] = $fields['country'] = $fields['email'] = 1;
    $contact = $this->_params;
    if (CRM_Utils_Array::value('fileFields', $this->_fields)) {
      CRM_Grant_BAO_Grant_Utils::processFiles($this);
    }
    foreach ($fields as $name => $dontCare) {
      if (isset($contact[$name])) {
        $defaults[$name] = $contact[$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
        }
        elseif (in_array($name, array(
              'addressee',
              'email_greeting',
              'postal_greeting',
            )) && !empty($contact[$name . '_custom'])
        ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
      }
    }

    $this->_submitValues = array_merge($this->_submitValues, $defaults);

    $this->setDefaults($defaults);
    $this->freeze();

    // can we blow away the session now to prevent hackery
    // CRM-9491
    $this->controller->reset();
  }

}
