<?php

require_once 'grantprofiles.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function grantprofiles_civicrm_config(&$config) {
  _grantprofiles_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function grantprofiles_civicrm_xmlMenu(&$files) {
  _grantprofiles_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function grantprofiles_civicrm_install() {
  _grantprofiles_civix_civicrm_install();

  $smarty = CRM_Core_Smarty::singleton();
  $config = CRM_Core_Config::singleton();
  $data = $smarty->fetch($config->extensionsDir . 'biz.jmaconsulting.grantprofiles/sql/civicrm_msg_template.tpl');
  file_put_contents($config->uploadDir . "civicrm_data.sql", $data);
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->uploadDir . "civicrm_data.sql");
  grantprofiles_addRemoveMenu(TRUE);
  return TRUE;
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function grantprofiles_civicrm_uninstall() {
  return _grantprofiles_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function grantprofiles_civicrm_enable() {
  $config = CRM_Core_Config::singleton();
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->extensionsDir.'biz.jmaconsulting.grantprofiles/sql/grantprofiles_enable.sql');
  grantprofiles_addRemoveMenu(TRUE);
  return _grantprofiles_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function grantprofiles_civicrm_disable() {
  $config = CRM_Core_Config::singleton();
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->extensionsDir.'biz.jmaconsulting.grantprofiles/sql/grantprofiles_disable.sql');
  grantprofiles_addRemoveMenu(FALSE);
  return _grantprofiles_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function grantprofiles_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _grantprofiles_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function grantprofiles_civicrm_managed(&$entities) {
  return _grantprofiles_civix_civicrm_managed($entities);
}

function grantprofiles_civicrm_validate($formName, &$fields, &$files, &$form) {
  $errors = array();
  if ($formName == "CRM_UF_Form_Field" && CRM_Core_Permission::access('CiviGrant') 
    && ($form->getVar('_action') != CRM_Core_Action::DELETE)) {
    $fieldType = $fields['field_name'][0];
    $errorField = FALSE;
    //get the group type.
    $groupType = CRM_Core_BAO_UFGroup::calculateGroupType($form->getVar('_gid'), FALSE, CRM_Utils_Array::value('field_id', $fields));
    if ($fieldType == "Activity" || $fieldType == "Participant" || $fieldType == "Contribution" || $fieldType =="Membership") {
      if (in_array('Grant', $groupType)) {
        $errors['field_name'] = ts('The profile has a grant field already, and this field is not a contact or grant field.');
      }
    }
    elseif ($fieldType == "Grant") {
      if ( in_array('Membership', $groupType) || 
        in_array('Activity', $groupType) || 
        in_array('Participant', $groupType) || 
        in_array('Contribution', $groupType) ) {
        $errors['field_name'] = ts('A grant field can only be added to a profile that has only contact and grant fields. This profile has fields that are not contact or grant fields');
      }
    }
  }
  return $errors;
}

function grantprofiles_civicrm_buildForm($formName, &$form) { 
  
  if ($formName == "CRM_Grant_Form_GrantPage_Settings" || 
    $formName == "CRM_Grant_Form_GrantPage_Custom" || 
    $formName == "CRM_Grant_Form_GrantPage_ThankYou") {
    CRM_Core_Region::instance('page-body')->add(array(
       'template' => 'CRM/css/grantprofiles.tpl',
    ));
  } 
  // Code to be done to avoid core editing
  if ($formName == "CRM_UF_Form_Field" && CRM_Core_Permission::access('CiviGrant')) {
    $grantFields = getProfileFields();
    $fields['Grant'] = $grantFields;
    // Add the grant fields to the form
    $originalFields = $form->getVar('_fields');
    $form->setVar('_fields', array_merge(exportableFields('Grant'), $originalFields));
    $originalSelect = $form->getVar('_selectFields');

    foreach ($fields as $key => $value) {
      foreach ($value as $key1 => $value1) {
        //CRM-2676, replacing the conflict for same custom field name from different custom group.
        if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key1)) {
          $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $customFieldId, 'custom_group_id');
          $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'title');
          $mapperFields[$key][$key1] = $value1['title'] . ' :: ' . $customGroupName;
          $selectFields[$key][$key1] = $value1['title'];
        }
        else {
          $mapperFields[$key][$key1] = $value1['title'];
          $selectFields[$key][$key1] = $value1['title'];
        }
        $hasLocationTypes[$key][$key1] = CRM_Utils_Array::value('hasLocationType', $value1);
      }
    }
    if (!empty($selectFields['Grant'])) {
      $form->setVar('_selectFields', array_merge($selectFields['Grant'], $originalSelect));
    }
    if(!empty($noSearchable)) {
      $form->assign('noSearchable', $noSearchable);
    }
    $grantArray = array(
      'text' => 'Grant',
      'attr' => array('value' => 'Grant')
    );

    foreach ($form->_elements as $eleKey => $eleVal) {
      foreach ($eleVal as $optionKey => $optionVal) {
        if ($optionKey == '_options') {
          $form->_elements[$eleKey]->_options[0]['Grant'] = 'Grant';
          $form->_elements[$eleKey]->_options[1]['Grant'] = $mapperFields['Grant'];
        }
        if ($optionKey == '_elements') {
          $form->_elements[$eleKey]->_elements[0]->_options[] = $grantArray;
        } 
        if ($optionKey == '_js') {
          $form->_elements[$eleKey]->_js .= 'hs_field_name_Grant = '. json_encode($mapperFields['Grant']) . ';';
        }
      }
    } 
    if ($form->_defaultValues && array_key_exists('field_name', $form->_defaultValues) 
      && $form->_defaultValues['field_name'][0] == 'Grant') {
      $defaults['field_name'] = $form->_defaultValues['field_name'];
      $form->setDefaults($defaults);
    }
  }
}

function grantprofiles_civicrm_pageRun( &$page ) {
  if( $page->getVar('_name') == 'CRM_Grant_Page_DashBoard') {
    browse();
    CRM_Core_Region::instance('page-body')->add(array(
        'template' => 'CRM/Grant/Page/GrantApplicationDashboard.tpl',
      ));
  }
}

function &exportableFields() {
  $grantFields = array(
                       'grant_status' => array(
                                               'title' => 'Grant Status',
                                               'name' => 'grant_status',
                                               'data_type' => CRM_Utils_Type::T_STRING,
                                               ),
                       'grant_type' => array(
                                             'title' => 'Grant Type',
                                             'name' => 'grant_type',
                                             'data_type' => CRM_Utils_Type::T_STRING,
                                             ),
                       'grant_money_transfer_date' => array(
                                                            'title' => 'Grant Money Transfer Date',
                                                            'name' => 'grant_money_transfer_date',
                                                            'data_type' => CRM_Utils_Type::T_DATE,
                                                            ),
                       'grant_amount_requested' => array(
                                                         'title' => 'Grant Amount Requested',
                                                         'name' => 'grant_amount_requested',
                                                         'where' => 'civicrm_grant.amount_requested',
                                                         'data_type' => CRM_Utils_Type::T_FLOAT,
                                                         ),
                       'grant_application_received_date' => array(
                                                                  'title' => 'Grant Application Recieved Date',
                                                                  'name' => 'grant_application_received_date',
                                                                  'data_type' => CRM_Utils_Type::T_DATE,
                                                                  ),
                       );

  $fields = CRM_Grant_DAO_Grant::export();
  $grantNote = array('grant_note' => array('title' => ts('Grant Note'),
                                           'name' => 'grant_note',
                                           'data_type' => CRM_Utils_Type::T_TEXT,
                                           ));
  $fields = array_merge($fields, $grantFields, $grantNote,
                        CRM_Core_BAO_CustomField::getFieldsForImport('Grant')
                        );
  return $fields;
}
  
function getProfileFields() {
  $exportableFields = exportableFields('Grant');
      
  $skipFields = array('grant_id', 'grant_contact_id', 'grant_type', 'grant_note', 'grant_status' );
  foreach ($skipFields as $field) {
    if (isset($exportableFields[$field])) {
      unset($exportableFields[$field]);
    }
  }
      
  return $exportableFields;
}

/**
 * Function to get list of grant fields for profile
 * For now we only allow custom grant fields to be in
 * profile
 *
 * @param boolean $addExtraFields true if special fields needs to be added
 *
 * @return return the list of grant fields
 * @static
 * @access public
 */
function getGrantFields() {
  $grantFields = CRM_Grant_DAO_Grant::export();
  $grantFields = array_merge($grantFields, CRM_Core_OptionValue::getFields($mode = 'grant'));
       
  $grantFields = array_merge($grantFields, CRM_Financial_DAO_FinancialType::export());
    
  foreach ($grantFields as $key => $var) {
    $fields[$key] = $var;
  }

  $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Grant'));
   
  return $fields;
}

function browse($action = NULL) {
  $params = array();
  $query = "SELECT * from civicrm_grant_app_page WHERE 1";
  $grantPage = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Grant_DAO_GrantApplicationPage');
  $rows = array();
  $allowToDelete = CRM_Core_Permission::check('delete in CiviGrant');
  //get configure actions links.
  $configureActionLinks = configureActionLinks();
  $query = "
         SELECT  id
         FROM  civicrm_grant_app_page
         WHERE  1";
  $grantAppPage = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Grant_DAO_GrantApplicationPage');
  $grantAppPageIds = array();
  while ($grantAppPage->fetch()) {
    $grantAppIds[$grantAppPage->id] = $grantAppPage->id;
  }
  //get all section info.
  $grantAppPageSectionInfo = CRM_Grant_BAO_GrantApplicationPage::getSectionInfo($grantAppPageIds);

  while ( $grantPage->fetch() ) {
    $rows[$grantPage->id] = array();
    CRM_Core_DAO::storeValues($grantPage, $rows[$grantPage->id]);

    // form all action links
    $action = array_sum(array_keys(actionLinks()));

    //add configure actions links.
    $action += array_sum(array_keys($configureActionLinks));

    //add online grant links.
    $action += array_sum(array_keys(onlineGrantLinks()));

    if ($grantPage->is_active) {
      $action -= CRM_Core_Action::ENABLE;
    }
    else {
      $action -= CRM_Core_Action::DISABLE;
    }
         
    //CRM-4418
    if (!$allowToDelete) {
      $action -= CRM_Core_Action::DELETE;
    }
    $sectionsInfo = CRM_Utils_Array::value($grantPage->id, $grantAppPageSectionInfo, array());

    $rows[$grantPage->id]['configureActionLinks'] = CRM_Core_Action::formLink(formatConfigureLinks($sectionsInfo),
                                                                              $action,
                                                                              array('id' => $grantPage->id),
                                                                              ts('Configure'),
                                                                              TRUE
                                                                              );
                  
    //build the online grant application links.
    $rows[$grantPage->id]['onlineGrantLinks'] = CRM_Core_Action::formLink(onlineGrantLinks(),
                                                                          $action,
                                                                          array('id' => $grantPage->id),
                                                                          ts('Grant Application (Live)'),
                                                                          FALSE
                                                                          );

    //build the normal action links.
    $rows[$grantPage->id]['action'] = CRM_Core_Action::formLink(actionLinks(),
                                                                $action,
                                                                array('id' => $grantPage->id),
                                                                ts('more'),
                                                                TRUE
                                                                );
         
    $rows[$grantPage->id]['title'] = $grantPage->title;
    $rows[$grantPage->id]['is_active'] = $grantPage->is_active;
    $rows[$grantPage->id]['id'] = $grantPage->id;
      
  }
  $smarty =  CRM_Core_Smarty::singleton( );
  $smarty->assign('fields', $rows);
}


function &configureActionLinks() {
    // check if variable _actionsLinks is populated
      $urlString = 'civicrm/admin/grant/';
      $urlParams = 'reset=1&action=update&id=%%id%%';

      $configureActionLinks = array(
        CRM_Core_Action::ADD => array(
          'name' => ts('Info and Settings'),
          'title' => ts('Info and Settings'),
          'url' => $urlString . 'settings',
          'qs' => $urlParams,
          'uniqueName' => 'settings',
        ),
        CRM_Core_Action::EXPORT => array(
          'name' => ts('Receipt'),
          'title' => ts('Receipt'),
          'url' => $urlString . 'thankyou',
          'qs' => $urlParams,
          'uniqueName' => 'thankyou',
        ),
        CRM_Core_Action::PROFILE => array(
          'name' => ts('Profiles'),
          'title' => ts('Profiles'),
          'url' => $urlString . 'custom',
          'qs' => $urlParams,
          'uniqueName' => 'custom',
        ),
      );

    return $configureActionLinks;
  }

function &actionLinks() {
    // check if variable _actionsLinks is populated
      // helper variable for nicer formatting
      $deleteExtra = ts('Are you sure you want to delete this Grant application page?');

      $actionLinks = array(
         CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'title' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Grant_BAO_GrantApplicationPage' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Grant_BAO_GrantApplicationPage' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => CRM_Utils_System::currentPath(),
          'qs' => 'action=delete&reset=1&id=%%id%%',
          'title' => ts('Delete Custom Field'),
          'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
        ),
      );
    return $actionLinks;
  }

function &onlineGrantLinks() {
  $urlString = 'civicrm/grant/transact';
  $urlParams = 'reset=1&id=%%id%%';
  $onlineGrantLinks = array(
    CRM_Core_Action::RENEW => array(
      'name' => ts('Grant Application (Live)'),
      'title' => ts('Grant Application (Live)'),
      'url' => $urlString,
      'qs' => $urlParams,
      'fe' => TRUE,
      'uniqueName' => 'live_page',
    ),
  );
  return $onlineGrantLinks;
}

function formatConfigureLinks($sectionsInfo) {
  //build the formatted configure links.
  $formattedConfLinks = configureActionLinks();
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
        $classes = $link['class'];
      }
      $link['class'] = array_merge($classes, array('disabled'));
    }
  }

  return $formattedConfLinks;
}

function grantprofiles_addRemoveMenu($enable) {
  $config = CRM_Core_Config::singleton();

  $params['enableComponents'] = $config->enableComponents;
  $params['enableComponentIDs'] = $config->enableComponentIDs;
  $grantComponentID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_component WHERE name = "CiviGrant"');
  if ($enable) {
    $params['enableComponents'][] = 'CiviGrant';
    $params['enableComponentIDs'][] = $grantComponentID;
  }
  else {
    $params['enableComponents'] = array_unique($params['enableComponents']);
    $params['enableComponentIDs'] = array_unique($params['enableComponentIDs']);
    $key = array_search('CiviGrant', $params['enableComponents']);
    if ($key) {
      unset($params['enableComponents'][$key]);
    }
    $key = array_search($grantComponentID, $params['enableComponentIDs']);
    if ($key) {
      unset($params['enableComponentIDs'][$key]);
    }
  }
  CRM_Core_BAO_ConfigSetting::create($params);
  return;
}