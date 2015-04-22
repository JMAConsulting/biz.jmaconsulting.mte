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
  /* public function install() { */
  /*   CRM_Core_Invoke::rebuildMenuAndCaches(); */
  /* } */

  /**
   * Example: Run an external SQL script when the module is uninstalled
   */
  // public function uninstall() {
  // }

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
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } 

  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_4202() {
    $this->ctx->log->info('Applying update 4202');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4202.sql');
    
    // rebuild menu items
    CRM_Core_Menu::store();
    
    $mandrillSecret = CRM_Core_OptionGroup::values('mandrill_secret', TRUE);
    $url = CRM_Utils_System::url('civicrm/ajax/mte/callback', "mandrillSecret={$mandrillSecret['Secret Code']}", TRUE, NULL, FALSE, TRUE);
   
    CRM_Core_Session::setStatus(ts("The URL that Mandrill needs to post to has changed during this upgrade. You need to reconfigure the webhook for Mandrill to use the following URL in Post to URL: $url"));
    return TRUE;
  } 
  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_4203() {
    $this->ctx->log->info('Applying update 4203');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4203.sql');
    return TRUE;
  } 

  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_4204() {
    $this->ctx->log->info('Applying update 4204');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4204.sql');
    
    // rebuild menu items
    CRM_Core_Menu::store();
    $params = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );
    
    if ($params['outBound_option'] == 0 
      && CRM_Utils_Array::value('smtpServer', $params) == 'smtp.mandrillapp.com') {
      unset($params['qfKey'], $params['entryURL'], $params['sendmail_path'], $params['sendmail_args'], $params['outBound_option']);
      $params['is_active'] = 1;
      CRM_Core_BAO_Setting::setItem($params,
        CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mandrill_smtp_settings'
      );
    }
    return TRUE;
  } 

  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_4514() {
    $this->ctx->log->info('Applying update for version 1.5');
    
    $config = CRM_Core_Config::singleton();
    if (property_exists($config, 'civiVersion')) {
      $civiVersion = $config->civiVersion;
    }
    else {
      $civiVersion = CRM_Core_BAO_Domain::version();
    }
    
    if (!(version_compare('4.5alpha1', $civiVersion) > 0)) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_mailing_bounce_type` CHANGE `name` `name` VARCHAR( 24 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of bounce'");
      $mandrillBounceType = array(
        'Mandrill Har' => 'Mandrill Hard',
        'Mandrill Sof' => 'Mandrill Soft',
        'Mandrill Spa' => 'Mandrill Spam',
        'Mandrill Rej' => 'Mandrill Reject',
      );
      foreach($mandrillBounceType as $errorType => $bounceType) {
        CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_mailing_bounce_type SET name = %2 WHERE name = %1',
          array(
            1 => array($errorType, 'String'),
            2 => array($bounceType, 'String'),
          )
        );
      }
    }
    return TRUE;
  } 

  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_4620() {
    $this->ctx->log->info('Applying update for version 2.0');
    
    $mail = new CRM_Mailing_DAO_Mailing();
    $mail->domain_id = CRM_Core_Config::domainID();
    $mail->subject = "***All Transactional Emails***";
    $mail->url_tracking = TRUE;
    $mail->forward_replies = FALSE;
    $mail->auto_responder = FALSE;
    $mail->open_tracking = TRUE;
    if ($mail->find(TRUE)) {
      $mail->name = ts('Transaction Emails');
      $mail->save();
    }
    $url = CRM_Utils_System::url('civicrm/mte/smtp', 'reset=1', TRUE, NULL, FALSE, TRUE);
    CRM_Core_Session::setStatus(ts("Update the <a href={$url}>Mandrill settings</a> to configure it to use for Transactional Email and/or Civi Bulk Mail."));
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
