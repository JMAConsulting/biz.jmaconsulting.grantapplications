<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * This class introduces component to the system and provides all the
 * information about it. It needs to extend CRM_Core_Component_Info
 * abstract class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Grant_Info extends CRM_Core_Component_Info {

  /**
   * @inheritDoc
   */
  protected $keyword = 'grant';

  /**
   * @inheritDoc
   * @return array
   */
  public function getInfo() {
    return array(
      'name' => 'CiviGrant',
      'translatedName' => ts('CiviGrant'),
      'title' => 'CiviCRM Grant Management Engine',
      'path' => 'CRM_Grant_',
      'search' => 1,
      'showActivitiesInCore' => 1,
    );
  }


  /**
   * @inheritDoc
   * @param bool $getAllUnconditionally
   * @param bool $descriptions
   *   Whether to return permission descriptions
   *
   * @return array
   */
  public function getPermissions($getAllUnconditionally = FALSE, $descriptions = FALSE) {
    $permissions = array(
      'access CiviGrant' => array(
        ts('access CiviGrant'),
        ts('View all grants'),
      ),
      'edit grants' => array(
        ts('edit grants'),
        ts('Create and update grants'),
      ),
      'submit online grant application' => array(
        ts('submit online grant application'),
      ),
      'delete in CiviGrant' => array(
        ts('delete in CiviGrant'),
        ts('Delete grants'),
      ),
    );

    if (!$descriptions) {
      foreach ($permissions as $name => $attr) {
        $permissions[$name] = array_shift($attr);
      }
    }

    return $permissions;
  }

  /**
   * @inheritDoc
   * @return null
   */
  /**
   * @return array
   */
  public function getAnonymousPermissionWarnings() {
    return array(
      'access CiviGrant',
    );
  }

  /**
   * @inheritDoc
   * Provides information about user dashboard element
   * offered by this component.
   *
   * @return array|null
   *   collection of required dashboard settings,
   *                    null if no element offered
   */
  /**
   * @return array|null
   */
  public function getUserDashboardElement() {
    return array(
      'name' => ts('Grant'),
      'title' => ts('Your Grant(s)'),
      'perm' => array('submit online grant application'),
      'weight' => 50,
    );
  }

  /**
   * @inheritDoc
   * Provides information about user dashboard element
   * offered by this component.
   *
   * @return array|null
   *   collection of required dashboard settings,
   *                    null if no element offered
   */
  /**
   * @return array|null
   */
  public function registerTab() {
    return array(
      'title' => ts('Grants'),
      'url' => 'grant',
      'weight' => 50,
    );
  }

  /**
   * @inheritDoc
   * Provides information about advanced search pane
   * offered by this component.
   *
   * @return array|null
   *   collection of required pane settings,
   *                    null if no element offered
   */
  /**
   * @return array|null
   */
  public function registerAdvancedSearchPane() {
    return array(
      'title' => ts('Grants'),
      'weight' => 50,
    );
  }

  /**
   * @inheritDoc
   * Provides potential activity types that this
   * component might want to register in activity history.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array|null
   *   collection of activity types
   */
  /**
   * @return array|null
   */
  public function getActivityTypes() {
    return NULL;
  }

  /**
   * add shortcut to Create New.
   * @param $shortCuts
   */
  public function creatNewShortcut(&$shortCuts) {
    if (CRM_Core_Permission::check('access CiviGrant') &&
      CRM_Core_Permission::check('edit grants')
    ) {
      $shortCuts = array_merge($shortCuts, array(
        array(
          'path' => 'civicrm/grant/add',
          'query' => "reset=1&action=add&context=standalone",
          'ref' => 'new-grant',
          'title' => ts('Grant'),
        ),
      ));
    }
  }

}
