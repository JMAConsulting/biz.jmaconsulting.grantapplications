<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
class CRM_Grant_BAO_Grant_Utils {

  /**
   * Function to process payment after confirmation
   *
   * @param object  $form   form object
   * @param int     $contactID       contact id
   * @param int     $component   component id
   *
   * @return array associated array
   *
   * @static
   * @access public
   */
  static function processConfirm(&$form,
    $params,
    $contactID,
    $grantTypeId,
    $component = 'grant',
    $fieldTypes = NULL
  ) {

    $params['grantApplicationPageID'] = $form->_params['grantApplicationPageID'] = $form->_values['id'];
    $params['contactID'] = $form->_params['contactID'] = $contactID;
    $grant = CRM_Grant_Form_Grant_Confirm::processApplication(
      $form,
      $params,
      $contactID,
      $grantTypeId,
      TRUE
    );
      
    if ($grant) {
      $form->_params['grantID'] = $grant->id;
    }
    
    $form->_params['grantTypeID'] = $grantTypeId;
    $form->_params['item_name'] = $form->_params['description'];
    $form->_params['application_received_date'] = date('YmdHis');
    $form->set('params', $form->_params);
    // check if grantprograms extension enabled
    $isActive = CRM_Grantapplications_BAO_GrantApplicationProfile::checkRelatedExtensions('biz.jmaconsulting.grantprograms');
    // finally send an email receipt
    if ($grant && !$isActive) {   
      $form->_values['grant_id'] = $grant->id;
      CRM_Grant_BAO_GrantApplicationPage::sendMail($contactID, 
        $form->_values,
        FALSE,
        $fieldTypes
      );
    }
  }

  /**
   * Function to process files
   *
   * @param object  $form   form object
   *
   * @static
   * @access public
   */
  static function processFiles($form) {
    $files = array();
    foreach ($form->_fields['fileFields'] as $key => $value) {
      if (CRM_Utils_Array::value('fileID', $value)) {
        $url = CRM_Utils_System::url('civicrm/file',
          'reset=1&id='.$value['fileID'].'&eid='.$value['entityID'],
          FALSE, NULL, TRUE, TRUE
        );
        $fileType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File',
          $value['fileID'],
          'mime_type',
          'id'
        );  
        $fileName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File',
          $value['fileID'],
          'uri',
          'id'
        );  
        if ($fileType == 'image/jpeg' ||
            $fileType == 'image/pjpeg' ||
            $fileType == 'image/gif' ||
            $fileType == 'image/x-png' ||
            $fileType == 'image/png'
        ) {
          $files[$key]['displayURL'] = $url;
        }
        else {
          $files[$key]['fileURL'] = $url;
        }
        $files[$key]['fileName'] = $fileName;
        $files[$key]['id'] = $key;
        $files[$key]['fileID'] = $value['fileID'];
      }
      else {
        $files[$key]['noDisplay'] = TRUE;
      }
      if (CRM_Utils_Array::value($key, $form->_params)) {
        unset($files[$key]);
        if (in_array($form->_params[$key]['type'], array('image/jpeg', 'image/pjpeg', 'image/gif',  'image/x-png', 'image/png'))) {
          $files[$key]['displayURLnew'] = $form->_params[$key]['name'];
        }
        else {
          $files[$key]['fileURLnew'] = $form->_params[$key]['name'];
        }
        preg_match("/[^\/]+$/", $form->_params[$key]['name'], $matches);
        $files[$key]['fileName'] = $matches[0];
      }
    }
    $form->assign('files', $files);
  }

}


