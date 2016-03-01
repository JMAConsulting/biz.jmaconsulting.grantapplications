<?php

/**
 * Collection of upgrade steps
 */
class CRM_Grantapplications_Upgrader extends CRM_Grantapplications_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed
   *
  public function install() {
    $this->executeSqlFile('sql/myinstall.sql');
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_4301() {
    $this->ctx->log->info('Applying update 4301');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4301.sql');
    return TRUE;
  }

  /**
   * Upgrade to add on behalf module data
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_4700() {
    $this->ctx->log->info('Applying update 4700');
    $this->addTask(ts('Migrate \'on behalf of\' information to module_data'), 'migrateOnBehalfOfInfo');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4700.sql');
    return TRUE;
  }

  /**
   * Migrate on-behalf information to uf_join.module_data as on-behalf columns will be dropped
   * on DB upgrade
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *   TRUE for success
   */
  public static function migrateOnBehalfOfInfo() {
    $ufGroupDAO = new CRM_Core_DAO_UFJoin();
    $ufGroupDAO->module = 'OnBehalf';
    $ufGroupDAO->find(TRUE);

    $query = "SELECT cp.*, uj.id as join_id
      FROM civicrm_grant_app_page cp
      INNER JOIN civicrm_uf_join uj ON uj.entity_id = cp.id AND uj.module = 'OnBehalf'";
    $dao = CRM_Core_DAO::executeQuery($query);

    if ($dao->N) {
      $domain = new CRM_Core_DAO_Domain();
      $domain->find(TRUE);
      while ($dao->fetch()) {
        $onBehalfParams['on_behalf'] = array('is_for_organization' => $dao->is_for_organization);
        if ($domain->locales) {
          $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
          foreach ($locales as $locale) {
            $for_organization = "for_organization_{$locale}";
            $onBehalfParams['on_behalf'] += array(
              $locale => array(
                'for_organization' => $dao->$for_organization,
              ),
            );
          }
        }
        else {
          $onBehalfParams['on_behalf'] += array(
            'default' => array(
              'for_organization' => $dao->for_organization,
            ),
          );
        }
        $ufJoinParam = array(
          'id' => $dao->join_id,
          'module' => 'on_behalf',
          'module_data' => json_encode($onBehalfParams),
        );
        CRM_Core_BAO_UFJoin::create($ufJoinParam);
      }
    }

    return TRUE;
  }


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
