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
 * form to process actions on the group aspect of Custom Data
 */
class CRM_Grant_Form_Grant_Confirm extends CRM_Grant_Form_GrantBase {

  /**
   * The id of the contact associated with this contribution.
   *
   * @var int
   */
  public $_contactID;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();

    parent::preProcess();
    $this->assign('confirm_text', CRM_Utils_Array::value('confirm_text', $this->_values));
    $this->assign('confirm_footer', CRM_Utils_Array::value('confirm_footer', $this->_values));
    $this->_params['amount'] = $this->get('default_amount_hidden');
    // we use this here to incorporate any changes made by folks in hooks
    $this->_params['currencyID'] = $config->defaultCurrency;
    $this->_params = $this->controller->exportValues('Main');

    $this->_params['is_draft'] = $this->get('is_draft');

    $this->_params['ip_address'] = $_SERVER['REMOTE_ADDR'];
    // hack for safari
    if ($this->_params['ip_address'] == '::1') {
      $this->_params['ip_address'] = '127.0.0.1';
    }
    $this->_params['amount'] = $this->get('default_amount');

    // if onbehalf-of-organization
    if (!empty($this->_values['onbehalf_profile_id']) && !empty($this->_params['onbehalf']['organization_name'])) {
      // CRM-15182
      $this->_params['organization_id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_params['onbehalf']['organization_name'], 'id', 'display_name');

      $this->_params['organization_name'] = $this->_params['onbehalf']['organization_name'];
      $addressBlocks = array(
        'street_address',
        'city',
        'state_province',
        'postal_code',
        'country',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'postal_code_suffix',
        'geo_code_1',
        'geo_code_2',
        'address_name',
      );

      $blocks = array('email', 'phone', 'im', 'url', 'openid');
      foreach ($this->_params['onbehalf'] as $loc => $value) {
        $field = $typeId = NULL;
        if (strstr($loc, '-')) {
          list($field, $locType) = explode('-', $loc);
        }

        if (in_array($field, $addressBlocks)) {
          if ($locType == 'Primary') {
            $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
            $locType = $defaultLocationType->id;
          }

          if ($field == 'country') {
            $value = CRM_Core_PseudoConstant::countryIsoCode($value);
          }
          elseif ($field == 'state_province') {
            $value = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value);
          }

          $isPrimary = 1;
          if (isset($this->_params['onbehalf_location']['address'])
            && count($this->_params['onbehalf_location']['address']) > 0
          ) {
            $isPrimary = 0;
          }

          $this->_params['onbehalf_location']['address'][$locType][$field] = $value;
          if (empty($this->_params['onbehalf_location']['address'][$locType]['is_primary'])) {
            $this->_params['onbehalf_location']['address'][$locType]['is_primary'] = $isPrimary;
          }
          $this->_params['onbehalf_location']['address'][$locType]['location_type_id'] = $locType;
        }
        elseif (in_array($field, $blocks)) {
          if (!$typeId || is_numeric($typeId)) {
            $blockName = $fieldName = $field;
            $locationType = 'location_type_id';
            if ($locType == 'Primary') {
              $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
              $locationValue = $defaultLocationType->id;
            }
            else {
              $locationValue = $locType;
            }
            $locTypeId = '';
            $phoneExtField = array();

            if ($field == 'url') {
              $blockName = 'website';
              $locationType = 'website_type_id';
              list($field, $locationValue) = explode('-', $loc);
            }
            elseif ($field == 'im') {
              $fieldName = 'name';
              $locTypeId = 'provider_id';
              $typeId = $this->_params['onbehalf']["{$loc}-provider_id"];
            }
            elseif ($field == 'phone') {
              list($field, $locType, $typeId) = explode('-', $loc);
              $locTypeId = 'phone_type_id';

              //check if extension field exists
              $extField = str_replace('phone', 'phone_ext', $loc);
              if (isset($this->_params['onbehalf'][$extField])) {
                $phoneExtField = array('phone_ext' => $this->_params['onbehalf'][$extField]);
              }
            }

            $isPrimary = 1;
            if (isset ($this->_params['onbehalf_location'][$blockName])
              && count($this->_params['onbehalf_location'][$blockName]) > 0
            ) {
              $isPrimary = 0;
            }
            if ($locationValue) {
              $blockValues = array(
                $fieldName => $value,
                $locationType => $locationValue,
                'is_primary' => $isPrimary,
              );

              if ($locTypeId) {
                $blockValues = array_merge($blockValues, array($locTypeId => $typeId));
              }
              if (!empty($phoneExtField)) {
                $blockValues = array_merge($blockValues, $phoneExtField);
              }

              $this->_params['onbehalf_location'][$blockName][] = $blockValues;
            }
          }
        }
        elseif (strstr($loc, 'custom')) {
          if ($value && isset($this->_params['onbehalf']["{$loc}_id"])) {
            $value = $this->_params['onbehalf']["{$loc}_id"];
          }
          $this->_params['onbehalf_location']["{$loc}"] = $value;
        }
        else {
          if ($loc == 'contact_sub_type') {
            $this->_params['onbehalf_location'][$loc] = $value;
          }
          else {
            $this->_params['onbehalf_location'][$field] = $value;
          }
        }
      }
    }
    elseif (!empty($this->_values['is_for_organization'])) {
      // no on behalf of an organization, CRM-5519
      // so reset loc blocks from main params.
      foreach (array(
                 'phone',
                 'email',
                 'address',
               ) as $blk) {
        if (isset($this->_params[$blk])) {
          unset($this->_params[$blk]);
        }
      }
    }
    $this->set('params', $this->_params);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->assignToTemplate();

    $params = $this->_params;

    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));

    $config = CRM_Core_Config::singleton();
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);
    if (!empty($this->_values['onbehalf_profile_id']) && !empty($params['onbehalf'])) {
      $ufJoinParams = array(
        'module' => 'onBehalf',
        'entity_table' => 'civicrm_grant_app_page',
        'entity_id' => $this->_id,
      );
      $OnBehalfProfile = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      $profileId = $OnBehalfProfile[0];

      $fieldTypes     = array('Contact', 'Organization');
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes     = array_merge($fieldTypes, $contactSubType);
      $fieldTypes = array_merge($fieldTypes, array('Grant'));

      $this->buildCustom($this->_values['onbehalf_profile_id'], 'onbehalfProfile', TRUE, 'onbehalf', $fieldTypes);
    }
    $grantButton = ts('Save Now');
    $this->assign('button', ts('Save Now'));

    $this->addButtons(array(
      array(
        'type' => 'next',
        'name' => $grantButton,
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
        'js' => array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');"),
      ),
      array(
        'type' => 'back',
        'name' => ts('Go Back'),
      ),
     )
    );

    $defaults = array();
    $fields = array_fill_keys(array_keys($this->_fields), 1);
    $contact = $this->_params;
    foreach ($fields as $name => $dontCare) {
      // Recursively set defaults for nested fields
      if (isset($contact[$name]) && is_array($contact[$name]) && ($name == 'onbehalf')) {
        foreach ($contact[$name] as $fieldName => $fieldValue) {
          if (is_array($fieldValue) && !in_array($this->_fields[$name][$fieldName]['html_type'], array(
              'Multi-Select',
              'AdvMulti-Select',
            ))
          ) {
            foreach ($fieldValue as $key => $value) {
              $defaults["{$name}[{$fieldName}][{$key}]"] = $value;
            }
          }
          else {
            $defaults["{$name}[{$fieldName}]"] = $fieldValue;
          }
        }
      }
      elseif (isset($contact[$name])) {
        $defaults[$name] = $contact[$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
          if (isset($contact["{$name}_id"])) {
            $defaults["{$name}_id"] = $contact["{$name}_id"];
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


    // fix attachment info
    if (CRM_Utils_Array::value('fileFields', $this->_fields)) {
      CRM_Grant_BAO_Grant_Utils::processFiles($this);
    }


    $this->setDefaults($defaults);

    $this->freeze();
  }

  /**
   * overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   * @access public
   */
  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {}

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $contactID = $this->getContactID();

    // add a description field at the very beginning
    $this->_params['description'] = ts('Online Grant Application') . ':' . $this->_values['title'];

    // fix currency ID
    $this->_params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;

    $params = $this->_params;
    $fields = array();

    if (!empty($params['image_URL'])) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    $fields = array('email-Primary' => 1);

    // get the add to groups
    $addToGroups = array();

    // now set the values for the billing location.
    foreach ($this->_fields as $name => $value) {
      $fields[$name] = 1;

      // get the add to groups for uf fields
      if (!empty($value['add_to_group_id'])) {
        $addToGroups[$value['add_to_group_id']] = $value['add_to_group_id'];
      }
    }

    if (!array_key_exists('first_name', $fields)) {
      $nameFields = array('first_name', 'middle_name', 'last_name');
      foreach ($nameFields as $name) {
        $fields[$name] = 1;
      }
    }

    // if onbehalf-of-organization contribution, take out
    // organization params in a separate variable, to make sure
    // normal behavior is continued. And use that variable to
    // process on-behalf-of functionality.
    if (!empty($this->_values['onbehalf_profile_id'])) {
      $behalfOrganization = array();
      $orgFields = array('organization_name', 'organization_id', 'org_option');
      foreach ($orgFields as $fld) {
        if (array_key_exists($fld, $params)) {
          $behalfOrganization[$fld] = $params[$fld];
          unset($params[$fld]);
        }
      }

      if (is_array($params['onbehalf']) && !empty($params['onbehalf'])) {
        foreach ($params['onbehalf'] as $fld => $values) {
          if (strstr($fld, 'custom_')) {
            $behalfOrganization[$fld] = $values;
          }
          elseif (!(strstr($fld, '-'))) {
              $behalfOrganization[$fld] = $values;
            $this->_params[$fld] = $values;
          }
        }
      }

      if (array_key_exists('onbehalf_location', $params) && is_array($params['onbehalf_location'])) {
        foreach ($params['onbehalf_location'] as $block => $vals) {
          //fix for custom data (of type checkbox, multi-select)
          if (substr($block, 0, 7) == 'custom_') {
            continue;
          }
          // fix the index of block elements
          if (is_array($vals)) {
            foreach ($vals as $key => $val) {
              //dont adjust the index of address block as
              //it's index is WRT to location type
              $newKey = ($block == 'address') ? $key : ++$key;
              $behalfOrganization[$block][$newKey] = $val;
            }
          }
        }
        unset($params['onbehalf_location']);
      }
      if (!empty($params['onbehalf[image_URL]'])) {
        $behalfOrganization['image_URL'] = $params['onbehalf[image_URL]'];
      }
      // Process attachments for custom fields
      foreach ($params as $fld => $values) {
        if (stristr($fld, 'onbehalf[custom_')) {
          preg_match_all('/\d+/', $fld, $matches);
          $behalfOrganization['custom_'.current($matches[0])] = $values;
        }
      }
    }

    // check for profile double opt-in and get groups to be subscribed
    $subscribeGroupIds = CRM_Core_BAO_UFGroup::getDoubleOptInGroupIds($params, $contactID);

    // since we are directly adding contact to group lets unset it from mailing
    if (!empty($addToGroups)) {
      foreach ($addToGroups as $groupId) {
        if (isset($subscribeGroupIds[$groupId])) {
          unset($subscribeGroupIds[$groupId]);
        }
      }
    }

    foreach ($addToGroups as $k) {
      if (array_key_exists($k, $subscribeGroupIds)) {
        unset($addToGroups[$k]);
      }
    }

    if (empty($contactID)) {
      $dupeParams = $params;
      if (!empty($dupeParams['onbehalf'])) {
        unset($dupeParams['onbehalf']);
      }

      $dedupeParams = CRM_Dedupe_Finder::formatParams($dupeParams, 'Individual');
      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');

      // if we find more than one contact, use the first one
      $contact_id = CRM_Utils_Array::value(0, $ids);

      // Fetch default greeting id's if creating a contact
      if (!$contact_id) {
        foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
          if (!isset($params[$greeting])) {
            $params[$greeting] = CRM_Contact_BAO_Contact_Utils::defaultGreeting('Individual', $greeting);
          }
        }
      }

      $contactID = CRM_Contact_BAO_Contact::createProfileContact(
        $params,
        $fields,
        $contact_id,
        $addToGroups,
        NULL,
        NULL,
        TRUE
      );
    }
    else {
      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type');
      $contactID = CRM_Contact_BAO_Contact::createProfileContact(
        $params,
        $fields,
        $contactID,
        $addToGroups,
        NULL,
        $ctype,
        TRUE
      );
    }

    // Make the contact ID associated with the grant application available at the Class level.
    // Also make available to the session.
    //@todo consider handling this in $this->getContactID();
    $this->set('contactID', $contactID);
    $this->_contactID = $contactID;

    //get email primary first if exist
    $subscribtionEmail = array('email' => CRM_Utils_Array::value('email-Primary', $params));
    // subscribing contact to groups
    if (!empty($subscribeGroupIds) && $subscribtionEmail['email']) {
      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($subscribeGroupIds, $subscribtionEmail, $contactID);
    }
    // If onbehalf-of-organization grant application add organization
    // and it's location.
    if (isset($this->_values['onbehalf_profile_id']) && isset($behalfOrganization['organization_name']) && !empty($this->_params['is_for_organization'])) {
      $ufFields = array();
      foreach ($this->_fields['onbehalf'] as $name => $value) {
        $ufFields[$name] = 1;
      }
      self::processOnBehalfOrganization($behalfOrganization, $contactID, $this->_values,
        $this->_params, $ufFields
      );
    }
    $grantTypeId = $this->_values['grant_type_id'];

    $fieldTypes = array();

    $grantParams = $this->_params;

    CRM_Grant_BAO_Grant_Utils::processConfirm($this,
      $grantParams,
      $contactID,
      $grantTypeId,
      'grant',
      $fieldTypes
    );
  }

  /**
   * Process the grant application
   *
   * @return void
   * @access public
   */
  static function processApplication(&$form,
    $params,
    $contactID,
    $grantTypeId
  ) {
    $transaction = new CRM_Core_Transaction();
    $isDraft = FALSE;
    if (CRM_Utils_Array::value('is_draft', $form->_values)) {
      $isDraft = TRUE;
    }

    $params['is_email_receipt'] = CRM_Utils_Array::value('is_email_receipt', $form->_values);

    $nonDeductibleAmount = isset($params['default_amount_hidden']) ? $params['default_amount_hidden'] : $params['amount_requested'];

    // first create the grant record
    $params += array(
      'contact_id' => $contactID,
      'grant_type_id' => $grantTypeId,
      'grant_page_id' => $form->_id,
      'application_received_date' => date('YmdHis'),
      'currency' => $params['currencyID'],
    );

    if (CRM_Utils_Array::value('grant_program_id', $form->_values)) {
      $params['grant_program_id'] = $form->_values['grant_program_id'];
    }
    // FIXME
    if (CRM_Utils_Array::value('is_draft', $params)) {
      $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Grant_BAO_Grant', 'status_id', 'Draft');
    }
    else {
      $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Grant_BAO_Grant', 'status_id', 'Submitted');
    }
    $ids = array();
    if (!empty($params['grant_id'])) {
      $params['id'] = $params['grant_id'];
      $ids['grant_id'] = $params['grant_id'];
    }

    $params['amount_requested'] = trim(CRM_Utils_Money::format($nonDeductibleAmount, ' '));
    if (empty($params['amount_total'])) {
      $params['amount_total'] = trim(CRM_Utils_Money::format($nonDeductibleAmount, ' '));
    }

    if ($nonDeductibleAmount || $isDraft) {
      //add grant record
      $grant = CRM_Grant_BAO_Grant::add($params, $ids);
    }
    if ($grant) {
        CRM_Core_BAO_CustomValueTable::postProcess($form->_params,
        'civicrm_grant',
        $grant->id,
        'Grant'
      );
    }
    $params['grant_id'] = (int)$grant->id;

    // Save form values into saved search table
    if ($grant && $isDraft) {
      $savedSearch = $formValues = $ssParams = $savedSearch = array();
      $ssParams['id'] = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_saved_search WHERE form_values LIKE "%\"grant_id\";i:'.$grant->id.'%"');
      if (!empty($ssParams['id'])) {
        CRM_Contact_BAO_SavedSearch::retrieve($ssParams, $savedSearch);
      }
      if (CRM_Utils_Array::value('id', $savedSearch)) {
        $formValues['id'] = $savedSearch['id'];
      }
      $formValues['formValues'] = $params;
      CRM_Contact_BAO_SavedSearch::create($formValues);
    }

    // create an activity record
    if ($grant) {
      CRM_Grant_BAO_GrantApplicationPage::addActivity($grant);
    }
    // Re-using function defined in Contribution/Utils.php
    CRM_Contribute_BAO_Contribution_Utils::createCMSUser($params,
      $contactID,
      'email-Primary'
    );

    return $grant;
  }

/**
   * Add on behalf of organization and it's location.
   *
   * This situation occurs when on behalf of is enabled for the grant application page and the person
   * signing up does so on behalf of an organization.
   *
   * @param array $behalfOrganization
   *   array of organization info.
   * @param int $contactID
   *   individual contact id. One.
   *   who is doing the process of applying for grant.
   *
   * @param array $values
   *   form values array.
   * @param array $params
   * @param array $fields
   *   Array of fields from the onbehalf profile relevant to the organization.
   */
  public static function processOnBehalfOrganization(&$behalfOrganization, &$contactID, &$values, &$params, $fields = NULL) {
    $isNotCurrentEmployer = FALSE;
    $dupeIDs = array();
    $orgID = NULL;
    if (!empty($behalfOrganization['organization_id'])) {
      $orgID = $behalfOrganization['organization_id'];
      unset($behalfOrganization['organization_id']);
    }
    // create employer relationship with $contactID only when new organization is there
    // else retain the existing relationship
    else {
      // get the Employee relationship type id
      $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', 'Employee of', 'id', 'name_a_b');

      // keep relationship params ready
      $relParams['relationship_type_id'] = $relTypeId . '_a_b';
      $relParams['is_permission_a_b'] = 1;
      $relParams['is_active'] = 1;
      $isNotCurrentEmployer = TRUE;
    }

    // formalities for creating / editing organization.
    $behalfOrganization['contact_type'] = 'Organization';

    if (!$orgID) {
      // check if matching organization contact exists
      $dedupeParams = CRM_Dedupe_Finder::formatParams($behalfOrganization, 'Organization');
      $dedupeParams['check_permission'] = FALSE;
      $dupeIDs = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Organization', 'Unsupervised');

      // CRM-6243 says to pick the first org even if more than one match
      if (count($dupeIDs) >= 1) {
        $behalfOrganization['contact_id'] = $orgID = $dupeIDs[0];
        // don't allow name edit
        unset($behalfOrganization['organization_name']);
      }
    }
    else {
      // if found permissioned related organization, allow location edit
      $behalfOrganization['contact_id'] = $orgID;
      // don't allow name edit
      unset($behalfOrganization['organization_name']);
    }

    // handling for image url
    if (CRM_Utils_Array::value('image_URL', $behalfOrganization)) {
      CRM_Contact_BAO_Contact::processImageParams($behalfOrganization);
    }

    // create organization, add location
    $orgID = CRM_Contact_BAO_Contact::createProfileContact($behalfOrganization, $fields, $orgID,
      NULL, NULL, 'Organization'
    );
    // create relationship
    if ($isNotCurrentEmployer) {
      $relParams['contact_check'][$orgID] = 1;
      $cid = array('contact' => $contactID);
      CRM_Contact_BAO_Relationship::legacyCreateMultiple($relParams, $cid);
    }

    // if multiple match - send a duplicate alert
    if ($dupeIDs && (count($dupeIDs) > 1)) {
      $values['onbehalf_dupe_alert'] = 1;
      // required for IPN
      $params['onbehalf_dupe_alert'] = 1;
    }

    // make sure organization-contact-id is considered for recording
    // grant application etc..
    if ($contactID != $orgID) {
      // take a note of contact-id, so we can send the
      // receipt to individual contact as well.

      // required for mailing/template display ..etc
      $values['related_contact'] = $contactID;

      //make this employee of relationship as current
      //employer / employee relationship,  CRM-3532
      if ($isNotCurrentEmployer &&
        ($orgID != CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'employer_id'))
      ) {
        $isNotCurrentEmployer = FALSE;
      }

      if (!$isNotCurrentEmployer && $orgID) {
        //build current employer params
        $currentEmpParams[$contactID] = $orgID;
        CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($currentEmpParams);
      }

      // contribution / signup will be done using this
      // organization id.
      $contactID = $orgID;
    }
  }
}
