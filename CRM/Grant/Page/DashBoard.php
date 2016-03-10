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
 * $Id$
 *
 */

/**
 * This is page is for Grant Dashboard
 */
class CRM_Grant_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   */
  public function preProcess() {
    $admin = CRM_Core_Permission::check('administer CiviCRM');

    $grantSummary = CRM_Grant_BAO_Grant::getGrantSummary($admin);

    $this->assign('grantAdmin', $admin);
    $this->assign('grantSummary', $grantSummary);
  }
  
  public function browse($action = NULL) {
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
      
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $this, FALSE, 'browse'
    );
  
    $this->preProcess();

    $breadCrumb = array(array('title' => ts('Add Grant Application Page'),
      'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(),
      'reset=1'
     ),
    ));
    // what action to take ?
    if ($action & CRM_Core_Action::ADD) {
       $session = CRM_Core_Session::singleton();
       $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/grant/apply/settings',
         'action=add&reset=1'
       ));
   

      $controller = new CRM_Grant_Controller_GrantPage(NULL, $action);
      CRM_Utils_System::setTitle(ts('Manage Grant Application Page'));
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
      return $controller->run();
    }

    if ($action & CRM_Core_Action::DELETE) {
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
        'reset=1&action=browse'
      ));

      $id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
      );
    
      $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_GrantPage_Delete',
        'Delete Grant Application Page',
        CRM_Core_Action::DELETE
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }else {
      $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_Search', ts('grants'), NULL);
      $controller->setEmbedded(TRUE);
      $controller->reset();
      $controller->set('limit', 10);
      $controller->set('force', 1);
      $controller->set('context', 'search');
      $controller->process();
      $controller->run();
      $this->browse();
    }
    return parent::run();
  }

}
