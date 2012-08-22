<?php
/**
 * Mandrill Transactional Email extension integrates CiviCRM's non-bulk email 
 * with the Mandrill service
 * 
 * Copyright (C) 2012 JMA Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Support: https://github.com/JMAConsulting/biz.jmaconsulting.mte/issues
 * 
 * Contact: info@jmaconsulting.biz
 *          JMA Consulting
 *          215 Spadina Ave, Ste 400
 *          Toronto, ON  
 *          Canada   M5T 2C7
 */

/**
 * Collection of upgrade steps
 */
class CRM_Mte_Upgrader extends CRM_Mte_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed
   */
  public function install() {
    CRM_Core_Invoke::rebuildMenuAndCaches( );
    $mailingParams = array(
      'subject' => '***All Transactional Emails***',
      'url_tracking' => TRUE,
      'forward_replies' => FALSE,
      'auto_responder' => FALSE,
      'open_tracking' => TRUE,
      'is_completed' => FALSE,
    );

    //create entry in civicrm_mailing
    $mailing = CRM_Mailing_BAO_Mailing::add($mailingParams, $ids);

    //add entry in civicrm_mailing_job
    $saveJob             = new CRM_Mailing_DAO_Job();
    $saveJob->start_date = $saveJob->end_date = date('YmdHis');
    $saveJob->status     = 'Completed';
    $saveJob->job_type   = "Special: All transactional emails being sent through Mandrill";
    $saveJob->mailing_id = $mailing->id;
    $saveJob->save();

    // create mailing bounce type
    $mailingBounceType = array(
      '1' => array ( 
        'name' => 'Mandrill Hard',
        'description' => 'Mandrill hard bounce',
        'hold_threshold' => 1,
      ),
      '2' => array ( 
        'name' => 'Mandrill Soft',
        'description' => 'Mandrill soft bounce',
       'hold_threshold' => 3,
      ),
      '3' => array ( 
        'name' => 'Mandrill Spam',
        'description' => 'User marked a transactional email sent via Mandrill as spam',
        'hold_threshold' => 1,
      ),
      '4' => array ( 
        'name' => 'Mandrill Reject',
        'description' => 'Mandrill rejected delivery to this email address',
        'hold_threshold' => 1,
      ),
    );

    foreach ($mailingBounceType as $value) {
      $bounceType = new CRM_Mailing_DAO_BounceType();
      $bounceType->copyValues($value);
      if(!$bounceType->find(true)) {
        $bounceType->save();
      }
    }
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled
   */
  public function uninstall() {
   // TODO: remove Activities created by mte, mtee bounce types 
   // and the mte mailing job and mailing 
  	$this->executeSqlFile('sql/mte_uninstall.sql');
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
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


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
