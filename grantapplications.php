<?php
require_once 'grantapplications.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function grantapplications_civicrm_config(&$config) {
  _grantapplications_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function grantapplications_civicrm_xmlMenu(&$files) {
  _grantapplications_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function grantapplications_civicrm_install() {
  _grantapplications_civix_civicrm_install();

  $smarty = CRM_Core_Smarty::singleton();
  $smarty->assign('currentDirectoryPath', __DIR__);
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $smarty->fetch(__DIR__ . '/sql/civicrm_msg_template.tpl'), NULL, TRUE);
  grantapplications_addRemoveMenu(TRUE);
  return TRUE;
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function grantapplications_civicrm_uninstall() {
  grantapplications_enableDisableNavigationMenu(2);
  return _grantapplications_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function grantapplications_civicrm_enable() {
  grantapplications_enableDisableNavigationMenu(1);
  return _grantapplications_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function grantapplications_civicrm_disable() {
  grantapplications_enableDisableNavigationMenu(0);
  return _grantapplications_civix_civicrm_disable();
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
function grantapplications_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _grantapplications_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function grantapplications_civicrm_managed(&$entities) {
  return _grantapplications_civix_civicrm_managed($entities);
}

function grantapplications_civicrm_validate($formName, &$fields, &$files, &$form) {
  $errors = array();
  if ($formName == 'CRM_Grant_Form_Grant_Confirm') {
    $form->_errors = array(); // hack to prevent file fields from throwing an error in case they are required.
  }
  // Keeping this in validate hook to prevent re-use of same functionality
  if (($formName == 'CRM_Grant_Form_Grant_Main' ||  $formName == 'CRM_Grant_Form_Grant_Confirm') 
    && $form->_values['is_draft'] == 1 && (CRM_Utils_Array::value('_qf_Main_save', $fields) == 'Save as Draft' || $form->_params['is_draft'] == 1)) {
    foreach($form->_fields as $name => $values) {
      $form->setElementError($name, NULL);
      $form->_errors = array();
    }
  }
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

function grantapplications_civicrm_buildForm($formName, &$form) {
  // Code to be done to avoid core editing
  if ($formName == "CRM_UF_Form_Field" && CRM_Core_Permission::access('CiviGrant')) {
    if (!$form->elementExists('field_name')) {
      return NULL;
    }
    
    $elements = & $form->getElement('field_name');
    
    if ($elements && !array_key_exists('Grant', $elements->_options[0])) {
      $elements->_options[0]['Grant'] = 'Grant';
      $elements->_options[1]['Grant'] = $form->_mapperFields['Grant'];
          
      $elements->_elements[0]->_options[] = array(
        'text' => 'Grant',
        'attr' => array('value' => 'Grant')
      );
      
      $elements->_js .= 'hs_field_name_Grant = ' . json_encode($form->_mapperFields['Grant']) . ';';
    }
    
    // set default mapper when updating profile fields
    if ($form->_defaultValues && array_key_exists('field_name', $form->_defaultValues) 
      && $form->_defaultValues['field_name'][0] == 'Grant') {
      $defaults['field_name'] = $form->_defaultValues['field_name'];
      $form->setDefaults($defaults);
    }
  }
}

function grantapplications_civicrm_pageRun(&$page) {
  if ($page->getVar('_name') == 'CRM_Contact_Page_View_UserDashBoard') {
    $cid = $page->getVar('_contactId'); 
    // Check if grant program extension is enabled
    $enabled = CRM_Grantapplications_BAO_GrantApplicationProfile::checkRelatedExtensions('biz.jmaconsulting.grantprograms');
    $smarty = CRM_Core_Smarty::singleton();
    $rels = CRM_Contact_BAO_Relationship::getRelationship($cid, 3, 0, 0, 0, NULL, NULL, TRUE);
    $actionLinks = $smarty->get_template_vars('grant_rows');
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit grants')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviGrant')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);
    foreach ($actionLinks as $key => $fields) {
      //FIXME:Replace it with option value name
      if (CRM_Utils_Array::value('grant_status', $fields) != 'Draft') {
        unset($actionLinks[$key]);
        continue;
      }
      $ssID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_saved_search WHERE form_values LIKE "%\"grant_id\";i:'.$fields['grant_id'].'%"');
      if ($ssID) {
        $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($ssID);
        $actionLinks[$key]['action'] = CRM_Core_Action::formLink(grantapplications_dashboardActionLinks(),
          $mask,
          array(
            'id' => $formValues['grantApplicationPageID'],
            'gid' => $fields['grant_id'],
          )
        );
      }
    } 
    $rows = array();
    if (!empty($rels)) {
      $extraSelect = '';
      $relationshipType = CRM_Core_PseudoConstant::relationshipType('name');
      $grantType = CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'grant_type_id');
      $grantStatus = CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'status_id');
      $grantStatusByName = CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'status_id');
      
      if ($enabled) {
        $extraSelect = ', grant_program_id ';
        $grantProgram = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
      }
      foreach($rels as $id => $values) {
        if ($relationshipType[$values['relationship_type_id']]['name_a_b'] != 'Employee of') {
          continue;
        }
        $query = "SELECT grant_type_id, application_received_date, amount_total, status_id, id, currency {$extraSelect} FROM civicrm_grant WHERE contact_id = {$values['cid']} AND status_id = " . array_search('Draft', $grantStatusByName);
        $dao = CRM_Core_DAO::executeQuery($query);
        while ($dao->fetch()) {
          $row = array();
          $row['contact_id'] = $values['cid'];
          $row['sort_name'] = $values['display_name'];
          $row['grant_type'] = CRM_Utils_Array::value($dao->grant_type_id, $grantType);
          $row['grant_application_received_date'] = $dao->application_received_date;
          $row['grant_amount_total'] = $dao->amount_total;
          $row['grant_status'] = CRM_Utils_Array::value($dao->status_id, $grantStatus);
          
          if ($enabled) {
            $row['program_id'] = $dao->grant_program_id;
            $row['program_name'] =$grantProgram[$row['program_id']];
          }
          
          // FIXME:Calling multiple times
          $ssID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_saved_search WHERE form_values LIKE "%\"grant_id\";i:'.$dao->id.'%"');
          if ($ssID) {
            $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($ssID);
            $row['action'] = CRM_Core_Action::formLink(grantapplications_dashboardActionLinks(),
              $mask,
              array(
                'id' => $formValues['grantApplicationPageID'],
                'gid' => $dao->id,
              )
            );
          }
          $rows[] = $row;
        }
      }
    }
    $page->assign('grant_rows', array_merge($actionLinks, $rows));
    $page->assign('enabled', $enabled);    
  }
}

function grantapplications_addRemoveMenu($enable) {
  $config = CRM_Core_Config::singleton();
  
  $params['enableComponents'] = $config->enableComponents;
  if ($enable) {
    if (array_search('CiviGrant', $config->enableComponents)) {
      return NULL;
    }
    $params['enableComponents'][] = 'CiviGrant';
  }
  else {
    $key = array_search('CiviGrant', $params['enableComponents']);
    if ($key) {
      unset($params['enableComponents'][$key]);
    }
  }
  
  CRM_Core_BAO_Setting::setItem($params['enableComponents'],
    CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,'enable_components');
}

function grantapplications_civicrm_entityTypes(&$entityTypes) {
  $entityTypes['CRM_Grant_DAO_GrantApplicationPage'] = array(
    'name' => 'GrantApplicationPage',
    'class' => 'CRM_Grant_DAO_GrantApplicationPage',
    'table' => 'civicrm_grant_app_page',
  );
}


/**
 * function to disable/enable/delete navigation menu
 *
 * @param integer $action 
 *
 */

function grantapplications_enableDisableNavigationMenu($action) {
  $domainID = CRM_Core_Config::domainID();
  
  $enableDisableDeleteData = NULL;
  if ($action != 1) {
    $enableDisableDeleteData = CRM_Grantapplications_BAO_GrantApplicationProfile::checkRelatedExtensions();   
  }


  if ($action < 2) { 
    
    if (!$enableDisableDeleteData) {
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_uf_group SET is_active = %1 WHERE group_type LIKE '%Grant%'", 
        array(
          1 => array($action, 'Integer'),
        )
      ); 
    }
    
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_option_value 
       INNER JOIN civicrm_option_group ON  civicrm_option_value.option_group_id = civicrm_option_group.id
       INNER JOIN civicrm_msg_template ON civicrm_msg_template.workflow_id = civicrm_option_value.id
         SET civicrm_option_value.is_active = %1,
           civicrm_option_group.is_active = %1,
           civicrm_msg_template.is_active = %1
       WHERE civicrm_option_group.name LIKE 'msg_tpl_workflow_grant'", 
      array(
        1 => array($action, 'Integer')
      )
    ); 
    
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_navigation SET is_active = %2 WHERE name = 'New Grant Application Page' AND domain_id = %1", 
      array(
        1 => array($domainID, 'Integer'),
        2 => array($action, 'Integer')
      )
    ); 
  }
  else {
    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_navigation  WHERE name = 'New Grant Application Page' AND domain_id = %1", 
      array(
        1 => array($domainID, 'Integer')
      )
    );
    
    if ($enableDisableDeleteData === NULL) {
      CRM_Core_DAO::executeQuery(
        "DELETE uj.*, uf.*, g.* FROM civicrm_uf_group g
         LEFT JOIN civicrm_uf_join uj ON uj.uf_group_id = g.id
         LEFT JOIN civicrm_uf_field uf ON uf.uf_group_id = g.id
         WHERE g.group_type LIKE '%Grant%';"
      );
    }
    $action = 0;
  }
  
  if ($enableDisableDeleteData) {
    return FALSE;
  }
  
  grantapplications_addRemoveMenu($action);
}