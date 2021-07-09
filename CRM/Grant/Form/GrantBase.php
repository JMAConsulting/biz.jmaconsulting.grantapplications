<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for processing a grant application.
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
   * Cache the amount to make things easier
   *
   * @var float
   * @public
   */
  public $_amount;

  /**
   * The contact id of the person for whom membership is being added or renewed based on the cid in the url,
   * checksum, or session
   * @var int
   */
  public $_contactID;

  protected $_userID;

  /**
   * Flag if email field exists in embedded profile
   *
   * @var bool
   */
  public $_emailExists = FALSE;

  public $_action;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    CRM_Core_Resources::singleton()->addStyle('#crm-container.crm-public .calc-value, #crm-container.crm-public .content {
      padding-top: 6px !important;
      font-size: 15px !important;
    }');

    // current grant application page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    if (!$this->_id) {
      // seems like the session is corrupted and/or we lost the id trail
      // lets just bump this to a regular session error and redirect user to main page
      $this->controller->invalidKeyRedirect();
    }
    $this->_emailExists = $this->get('emailExists');

    // this was used prior to the cleverer this_>getContactID - unsure now
    $this->_userID = CRM_Core_Session::singleton()->getLoggedInContactID();
    $this->_contactID = $this->getContactID();

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

    $this->assignHomeType();
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
        throw new CRM_Core_Exception(ts('The page you requested is currently unavailable.'));
      }

      if (!empty($this->_values['custom_pre_id'])) {
        $preProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_pre_id']);
      }

      if (!empty($this->_values['custom_post_id'])) {
        $postProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_post_id']);
      }

      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);
    }

    $this->assign('is_email_receipt', $this->_values['is_email_receipt']);

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
   * Assign home type id to bltID.
   *
   */
  public function assignHomeType() {
    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', array(), 'validate');
    $this->_bltID = array_search('Home', $locationTypes);
    $this->set('bltID', $this->_bltID);
    $this->assign('bltID', $this->_bltID);
  }

  /**
   * Set the default values.
   */
  public function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Assign the minimal set of variables to the template.
   */
  function assignToTemplate() {
      $vars = array(
      'default_amount_hidden'
    );

    $config = CRM_Core_Config::singleton();

    if (CRM_Utils_Array::value('default_amount_hidden', $this->_params)) {
      $this->assign('default_amount_hidden', $this->_params['default_amount_hidden']);
    }

    $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
      $this->_params,
      $this->_bltID
    ));

    if (!empty($this->_params['onbehalf_profile_id']) && !empty($this->_params['onbehalf'])) {
      $this->assign('onBehalfName', $this->_params['organization_name']);
      $locTypeId = array_keys($this->_params['onbehalf_location']['email']);
      $this->assign('onBehalfEmail', $this->_params['onbehalf_location']['email'][$locTypeId[0]]['email']);
    }
    $this->assign('email',
      $this->controller->exportValue('Main', "email")
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
        'financial_type_id' => 1,
      );

      $fields = self::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
        NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
      );

      if ($fields) {
        // determine if email exists in profile so we know if we need to manually insert CRM-2888, CRM-15067
        foreach ($fields as $key => $field) {
          if (substr($key, 0, 6) == 'email-' &&
              !in_array($profileContactType, array('honor', 'onbehalf'))
          ) {
            $this->_emailExists = TRUE;
            $this->set('emailExists', TRUE);
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
          if ($viewOnly && (CRM_Utils_Array::value('html_type', $field) == 'RichTextEditor')) {
            $this->_params[$key] = html_entity_decode($this->_params[$key]);
          }
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

          // in order to add required rule after maxfilesize rule we are setting the required parameter false for 
          // bypassing the is_required parameter ONLY for file type fields, and later added again below
          $required = $field['is_required'];
          $field['is_required'] = ($field['html_type'] == 'File') ? FALSE : $field['is_required'];
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
          if ($field['html_type'] == 'File') {
            $uploadFileSize = CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'), TRUE);
            $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);
            $this->addRule($field['name'], ts('%1 size exeeds %2 MB', [
              1 => $field['title'],
              2 => $uploadSize,
            ]), 'maxfilesize', $uploadFileSize);
            if ($required) {
              // restore the required rule
              $this->addRule($field['name'], ts('%1 is required', [1 => $field['title']]), 'required');
            }
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
   * Get all the fields that belong to the group with the name title,
   * and format for use with buildProfile. This is the SQL analog of
   * formatUFFields().
   *
   * @param mix $id
   *   The id of the UF group or ids of ufgroup.
   * @param bool|int $register are we interested in registration fields
   * @param int $action
   *   What action are we doing.
   * @param int $visibility
   *   Visibility of fields we are interested in.
   * @param $searchable
   * @param bool $showAll
   * @param string $restrict
   *   Should we restrict based on a specified profile type.
   * @param bool $skipPermission
   * @param null $ctype
   * @param int $permissionType
   * @param string $orderBy
   * @param null $orderProfiles
   *
   * @param bool $eventProfile
   *
   * @return array
   *   The fields that belong to this ufgroup(s)
   *
   * @throws \CRM_Core_Exception
   */
  public static function getFields(
    $id,
    $register = FALSE,
    $action = NULL,
    $visibility = NULL,
    $searchable = NULL,
    $showAll = FALSE,
    $restrict = NULL,
    $skipPermission = FALSE,
    $ctype = NULL,
    $permissionType = CRM_Core_Permission::CREATE,
    $orderBy = 'field_name',
    $orderProfiles = NULL,
    $eventProfile = FALSE
  ) {
    if (!is_array($id)) {
      $id = CRM_Utils_Type::escape($id, 'Positive');
      $profileIds = [$id];
    }
    else {
      $profileIds = $id;
    }

    $gids = implode(',', $profileIds);
    $params = [];
    if ($restrict) {
      $query = "SELECT g.* from civicrm_uf_group g
                LEFT JOIN civicrm_uf_join j ON (j.uf_group_id = g.id)
                WHERE g.id IN ( {$gids} )
                AND ((j.uf_group_id IN ( {$gids} ) AND j.module = %1) OR g.is_reserved = 1 )
                ";
      $params = [1 => [$restrict, 'String']];
    }
    else {
      $query = "SELECT g.* from civicrm_uf_group g WHERE g.id IN ( {$gids} ) ";
    }

    if (!$showAll) {
      $query .= " AND g.is_active = 1";
    }

    $checkPermission = [
      [
        'administer CiviCRM',
        'manage event profiles',
      ],
    ];
    if ($eventProfile && CRM_Core_Permission::check($checkPermission)) {
      $skipPermission = TRUE;
    }

    // add permissioning for profiles only if not registration
    if (!$skipPermission) {
      $permissionClause = CRM_Core_Permission::ufGroupClause($permissionType, 'g.');
      $query .= " AND $permissionClause ";
    }

    if ($orderProfiles and count($profileIds) > 1) {
      $query .= " ORDER BY FIELD(  g.id, {$gids} )";
    }
    $group = CRM_Core_DAO::executeQuery($query, $params);
    $fields = [];
    $validGroup = FALSE;

    while ($group->fetch()) {
      $validGroup = TRUE;
      $query = self::createUFFieldQuery($group->id, $searchable, $showAll, $visibility, $orderBy);
      $field = CRM_Core_DAO::executeQuery($query);

      $importableFields = self::getProfileFieldMetadata($showAll);
      list($customFields, $addressCustomFields) = self::getCustomFields($ctype);

      while ($field->fetch()) {
        list($name, $formattedField) = self::formatUFField($group, $field, $customFields, $addressCustomFields, $importableFields, $permissionType);
        if ($formattedField !== NULL) {
          $fields[$name] = $formattedField;
        }
      }
    }

    if (empty($fields) && !$validGroup) {
      throw new CRM_Core_Exception(ts('The requested Profile (gid=%1) is disabled OR it is not configured to be used for \'Profile\' listings in its Settings OR there is no Profile with that ID OR you do not have permission to access this profile. Please contact the site administrator if you need assistance.',
        [1 => implode(',', $profileIds)]
      ));
    }
    else {
      CRM_Core_BAO_UFGroup::reformatProfileFields($fields);
    }

    return $fields;
  }

  /**
   * @param $ctype
   *
   * @return mixed
   */
  protected static function getCustomFields($ctype) {
    $cacheKey = 'uf_grant_group_custom_fields_' . $ctype;
    if (!Civi::cache('metadata')->has($cacheKey)) {
      $customFields = CRM_Core_BAO_CustomField::getFieldsForImport($ctype, FALSE, FALSE, FALSE, TRUE, TRUE);

      // hack to add custom data for components
      $components = ['Contribution', 'Participant', 'Membership', 'Activity', 'Case', 'Grant'];
      foreach ($components as $value) {
        $customFields = array_merge($customFields, CRM_Core_BAO_CustomField::getFieldsForImport($value));
      }
      $addressCustomFields = CRM_Core_BAO_CustomField::getFieldsForImport('Address');
      $customFields = array_merge($customFields, $addressCustomFields);
      Civi::cache('metadata')->set($cacheKey, [$customFields, $addressCustomFields]);
    }
    return Civi::cache('metadata')->get($cacheKey);
  }

  /**
   * Create a query to find all visible UFFields in a UFGroup.
   *
   * This is the SQL-variant of checkUFFieldDisplayable().
   *
   * @param int $groupId
   * @param bool $searchable
   * @param bool $showAll
   * @param int $visibility
   * @param string $orderBy
   *   Comma-delimited list of SQL columns.
   *
   * @return string
   */
  protected static function createUFFieldQuery($groupId, $searchable, $showAll, $visibility, $orderBy) {
    $where = " WHERE uf_group_id = {$groupId}";

    if ($searchable) {
      $where .= " AND is_searchable = 1";
    }

    if (!$showAll) {
      $where .= " AND is_active = 1";
    }

    if ($visibility) {
      $clause = [];
      if ($visibility & self::PUBLIC_VISIBILITY) {
        $clause[] = 'visibility = "Public Pages"';
      }
      if ($visibility & self::ADMIN_VISIBILITY) {
        $clause[] = 'visibility = "User and User Admin Only"';
      }
      if ($visibility & self::LISTINGS_VISIBILITY) {
        $clause[] = 'visibility = "Public Pages and Listings"';
      }
      if (!empty($clause)) {
        $where .= ' AND ( ' . implode(' OR ', $clause) . ' ) ';
      }
    }

    $query = "SELECT * FROM civicrm_uf_field $where ORDER BY weight";
    if ($orderBy) {
      $query .= ", " . $orderBy;
      return $query;
    }
    return $query;
  }

  /**
   * Get the metadata for all potential profile fields.
   *
   * @param bool $isIncludeInactive
   *   Should disabled fields be included.
   *
   * @return array
   *   Field metadata for all fields that might potentially be in a profile.
   */
  protected static function getProfileFieldMetadata($isIncludeInactive) {
    return self::getImportableFields($isIncludeInactive, NULL, NULL, NULL, TRUE);
  }


  /**
   * Get a list of filtered field metadata.
   *
   * @param $showAll
   * @param $profileType
   * @param $contactActivityProfile
   * @param bool $filterMode
   *   Filter mode means you are using importable fields for filtering rather than just getting metadata.
   *   With filter mode = FALSE BOTH activity fields and component fields are returned.
   *   I can't see why you would ever want to use this function in filter mode as the component fields are
   *   still unfiltered. However, I feel scared enough to leave it as it is. I have marked this function as
   *   deprecated and am recommending the wrapper 'getProfileFieldMetadata' in order to try to
   *   send this confusion to history.
   *
   * @return array
   * @deprecated use getProfileFieldMetadata
   *
   */
  protected static function getImportableFields($showAll, $profileType, $contactActivityProfile, $filterMode = TRUE) {
    if (!$showAll) {
      $importableFields = CRM_Contact_BAO_Contact::importableFields('All', FALSE, FALSE, FALSE, TRUE, TRUE);
    }
    else {
      $importableFields = CRM_Contact_BAO_Contact::importableFields('All', FALSE, TRUE, FALSE, TRUE, TRUE);
    }

    $activityFields = CRM_Activity_BAO_Activity::getProfileFields();
    $componentFields = CRM_Core_Component::getQueryFields();
    if ($filterMode == TRUE) {
      if ($profileType == 'Activity' || $contactActivityProfile) {
        $importableFields = array_merge($importableFields, $activityFields);
      }
      else {
        $importableFields = array_merge($importableFields, $componentFields);
      }
    }
    else {
      $importableFields = array_merge($importableFields, $activityFields, $componentFields);
    }

    $importableFields['group']['title'] = ts('Group(s)');
    $importableFields['group']['where'] = NULL;
    $importableFields['tag']['title'] = ts('Tag(s)');
    $importableFields['tag']['where'] = NULL;
    return $importableFields;
  }

  /**
   * Prepare a field for rendering with CRM_Core_BAO_UFGroup::buildProfile.
   *
   * @param CRM_Core_DAO_UFGroup|CRM_Core_DAO $group
   * @param CRM_Core_DAO_UFField|CRM_Core_DAO $field
   * @param array $customFields
   * @param array $addressCustomFields
   * @param array $importableFields
   * @param int $permissionType
   *   Eg CRM_Core_Permission::CREATE.
   *
   * @return array
   */
  protected static function formatUFField(
    $group,
    $field,
    $customFields,
    $addressCustomFields,
    $importableFields,
    $permissionType = CRM_Core_Permission::CREATE
  ) {
    $name = $field->field_name;
    $title = $field->label;

    $addressCustom = FALSE;
    if (in_array($permissionType, [CRM_Core_Permission::CREATE, CRM_Core_Permission::EDIT]) &&
      in_array($field->field_name, array_keys($addressCustomFields))
    ) {
      $addressCustom = TRUE;
      $name = "address_{$name}";
    }
    if ($field->field_name == 'url') {
      $name .= "-{$field->website_type_id}";
    }
    elseif (!empty($field->location_type_id)) {
      $name .= "-{$field->location_type_id}";
    }
    else {
      $locationFields = CRM_Core_BAO_UFGroup::getLocationFields();
      if (in_array($field->field_name, $locationFields) || $addressCustom) {
        $name .= '-Primary';
      }
    }

    if (isset($field->phone_type_id)) {
      $name .= "-{$field->phone_type_id}";
    }
    $fieldMetaData = CRM_Utils_Array::value($name, $importableFields, ($importableFields[$field->field_name] ?? []));

    // No lie: this is bizarre; why do we need to mix so many UFGroup properties into UFFields?
    // I guess to make field self sufficient with all the required data and avoid additional calls
    $formattedField = [
      'name' => $name,
      'groupTitle' => $group->title,
      'groupName' => $group->name,
      'groupDisplayTitle' => (!empty($group->frontend_title)) ? $group->frontend_title : $group->title,
      'groupHelpPre' => empty($group->help_pre) ? '' : $group->help_pre,
      'groupHelpPost' => empty($group->help_post) ? '' : $group->help_post,
      'title' => $title,
      'where' => CRM_Utils_Array::value('where', CRM_Utils_Array::value($field->field_name, $importableFields)),
      'attributes' => CRM_Core_DAO::makeAttribute(CRM_Utils_Array::value($field->field_name, $importableFields)),
      'is_required' => $field->is_required,
      'is_view' => $field->is_view,
      'help_pre' => $field->help_pre,
      'help_post' => $field->help_post,
      'visibility' => $field->visibility,
      'in_selector' => $field->in_selector,
      'rule' => CRM_Utils_Array::value('rule', CRM_Utils_Array::value($field->field_name, $importableFields)),
      'location_type_id' => $field->location_type_id ?? NULL,
      'website_type_id' => $field->website_type_id ?? NULL,
      'phone_type_id' => $field->phone_type_id ?? NULL,
      'group_id' => $group->id,
      'add_to_group_id' => $group->add_to_group_id ?? NULL,
      'add_captcha' => $group->add_captcha ?? NULL,
      'field_type' => $field->field_type,
      'field_id' => $field->id,
      'pseudoconstant' => CRM_Utils_Array::value(
        'pseudoconstant',
        CRM_Utils_Array::value($field->field_name, $importableFields)
      ),
      // obsolete this when we remove the name / dbName discrepancy with gender/suffix/prefix
      'dbName' => CRM_Utils_Array::value(
        'dbName',
        CRM_Utils_Array::value($field->field_name, $importableFields)
      ),
      'skipDisplay' => 0,
      'data_type' => CRM_Utils_Type::getDataTypeFromFieldMetadata($fieldMetaData),
      'bao' => $fieldMetaData['bao'] ?? NULL,
    ];

    $formattedField = CRM_Utils_Date::addDateMetadataToField($fieldMetaData, $formattedField);

    //adding custom field property
    if (substr($field->field_name, 0, 6) == 'custom' ||
      substr($field->field_name, 0, 14) === 'address_custom'
    ) {
      // if field is not present in customFields, that means the user
      // DOES NOT HAVE permission to access that field
      if (array_key_exists($field->field_name, $customFields)) {
        $formattedField['serialize'] = !empty($customFields[$field->field_name]['serialize']);
        $formattedField['is_search_range'] = $customFields[$field->field_name]['is_search_range'];
        // fix for CRM-1994
        $formattedField['options_per_line'] = $customFields[$field->field_name]['options_per_line'];
        $formattedField['html_type'] = $customFields[$field->field_name]['html_type'];

        if (CRM_Utils_Array::value('html_type', $formattedField) == 'Select Date') {
          $formattedField['date_format'] = $customFields[$field->field_name]['date_format'];
          $formattedField['time_format'] = $customFields[$field->field_name]['time_format'];
          $formattedField['is_datetime_field'] = TRUE;
          $formattedField['smarty_view_format'] = CRM_Utils_Date::getDateFieldViewFormat($formattedField['date_format']);
        }

        $formattedField['is_multi_summary'] = $field->is_multi_summary;
        return [$name, $formattedField];
      }
      else {
        $formattedField = NULL;
        return [$name, $formattedField];
      }
    }
    return [$name, $formattedField];
  }

  /**
   * Add onbehalf profile fields and native module fields.
   *
   * @param int $id
   * @param CRM_Core_Form $form
   */
  public function buildComponentForm($id, $form) {
    if (empty($id)) {
      return;
    }

    $contactID = $this->getContactID();

    if (empty($form->_values['onbehalf_profile_id'])) {
      $form->assign('onBehalfOfFields', []);
      return;
    }

    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $form->_values['onbehalf_profile_id'], 'is_active')) {
      throw new CRM_Core_Exception(ts('This grant application page has been configured for application of grant on behalf of an organization and the selected onbehalf profile is either disabled or not found.'));
    }

    if ($contactID) {
      // retrieve all permissioned organizations of contact $contactID
      $organizations = CRM_Contact_BAO_Relationship::getPermissionedContacts($contactID, NULL, NULL, 'Organization');

      if (count($organizations)) {
        // Related org url - pass checksum if needed
        $args = array(
          'ufId' => $form->_values['onbehalf_profile_id'],
          'cid' => '',
        );
        if (!empty($_GET['cs'])) {
          $args = array(
            'ufId' => $form->_values['onbehalf_profile_id'],
            'uid' => $this->_contactID,
            'cs' => $_GET['cs'],
            'cid' => '',
          );
        }
        $locDataURL = CRM_Utils_System::url('civicrm/ajax/permlocation', $args, FALSE, NULL, FALSE);
        $form->assign('locDataURL', $locDataURL);
      }
      if (count($organizations) > 0) {
        $form->add('select', 'onbehalfof_id', '', CRM_Utils_Array::collect('name', $organizations));

        $orgOptions = array(
          0 => ts('Select an existing organization'),
          1 => ts('Enter a new organization'),
        );
        $form->addRadio('org_option', ts('options'), $orgOptions);
        $form->setDefaults(array('org_option' => 0));
      }
    }

    $form->assign('fieldSetTitle', ts('Organization Details'));

    if (CRM_Utils_Array::value('is_for_organization', $form->_values)) {
      if ($form->_values['is_for_organization'] == 2) {
        $form->assign('onBehalfRequired', TRUE);
      }
      else {
        $form->addElement('checkbox', 'is_for_organization',
          $form->_values['for_organization'],
          NULL
        );
      }
    }

    $profileFields = CRM_Core_BAO_UFGroup::getFields(
      $form->_values['onbehalf_profile_id'],
      FALSE, CRM_Core_Action::VIEW, NULL,
      NULL, FALSE, NULL, FALSE, NULL,
      CRM_Core_Permission::CREATE, NULL
    );

    $form->assign('onBehalfOfFields', $profileFields);
    if (!empty($form->_submitValues['onbehalf'])) {
      if (!empty($form->_submitValues['onbehalfof_id'])) {
        $form->assign('submittedOnBehalf', $form->_submitValues['onbehalfof_id']);
      }
      $form->assign('submittedOnBehalfInfo', json_encode($form->_submitValues['onbehalf']));
    }

    $fieldTypes = array('Contact', 'Organization');
    $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
    $fieldTypes = array_merge($fieldTypes, $contactSubType);

    foreach ($profileFields as $name => $field) {
      if (in_array($field['field_type'], $fieldTypes)) {
        list($prefixName, $index) = CRM_Utils_System::explode('-', $name, 2);
        if (in_array($prefixName, array('organization_name', 'email')) && empty($field['is_required'])) {
          $field['is_required'] = 1;
        }
        if (count($form->_submitValues) &&
            empty($form->_submitValues['is_for_organization']) &&
            $form->_values['is_for_organization'] == 1 &&
            !empty($field['is_required'])
            ) {
          $field['is_required'] = FALSE;
        }
        CRM_Core_BAO_UFGroup::buildProfile($form, $field, NULL, NULL, FALSE, 'onbehalf', NULL, 'onbehalf');
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
