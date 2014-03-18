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

require_once 'mte.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function mte_civicrm_config(&$config) {
  _mte_civix_civicrm_config($config);
  if ($config->userFramework == 'Joomla' && 'civicrm/ajax/mte/callback' == $_GET['task']) {
    $_SESSION['mte_temp'] = 1; 
  }
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function mte_civicrm_xmlMenu(&$files) {
  _mte_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function mte_civicrm_install() {
  _mte_civix_civicrm_install();
  $mailingParams = array(
    'subject' => '***All Transactional Emails***',
    'url_tracking' => TRUE,
    'forward_replies' => FALSE,
    'auto_responder' => FALSE,
    'open_tracking' => TRUE,
    'is_completed' => FALSE,
  );

  //create entry in civicrm_mailing
  $mailing = CRM_Mailing_BAO_Mailing::add($mailingParams, CRM_Core_DAO::$_nullArray);

  //add entry in civicrm_mailing_job
  //MTE-17
  $civiVersion = CRM_Core_Config::singleton()->civiVersion;
  if (version_compare('4.4alpha1', $civiVersion) > 0) {
    $jobCLassName = 'CRM_Mailing_DAO_Job';
  }
  else {
    $jobCLassName = 'CRM_Mailing_DAO_MailingJob';    
  }
  $saveJob = new $jobCLassName();
  $saveJob->start_date = $saveJob->end_date = date('YmdHis');
  $saveJob->status = 'Complete';
  $saveJob->job_type = "Special: All transactional emails being sent through Mandrill";
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
  return TRUE;
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function mte_civicrm_uninstall() {
  return _mte_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function mte_civicrm_enable() {
  foreach (glob(__DIR__ . '/sql/*_enable.sql') as $file) {
    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
  }
  return _mte_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function mte_civicrm_disable() {
  foreach (glob(__DIR__ . '/sql/*_disable.sql') as $file) {
    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
  }
  return _mte_civix_civicrm_disable();
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
function mte_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mte_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function mte_civicrm_managed(&$entities) {
  return _mte_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_alterMailer( )
 * To alter mailer settings i.e use mandrill smtp settings for transactional mails
 */
function mte_civicrm_alterMailer(&$mailer, $driver, $params) {
  $isTransactionalMail = CRM_Core_Smarty::singleton()->get_template_vars('isTransactionalMail', 1);

  if ($isTransactionalMail) {
    mte_getmailer($mailer, $params);    
  } 
}

function mte_getmailer(&$mailer, &$params = array()) {
  $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mandrill_smtp_settings'
  );
  
  if (CRM_Utils_array::value('is_active', $mailingBackend)) {
    $params['host'] = $mailingBackend['smtpServer'];
    $params['port'] = $mailingBackend['smtpPort'];
    $params['username'] = trim($mailingBackend['smtpUsername']);
    $params['password'] = CRM_Utils_Crypt::decrypt($mailingBackend['smtpPassword']);
    $params['auth'] = ($mailingBackend['smtpAuth']) ? TRUE : FALSE;
    $mailer = Mail::factory('smtp', $params);
    CRM_Core_Smarty::singleton()->assign('isTransactionalMail', 0);
  }
}

/**
 * Implementation of hook_civicrm_alterMailParams( )
 * To send headers in mail and also create activity
 */
function mte_civicrm_alterMailParams(&$params) {
  $session   = CRM_Core_Session::singleton();
  $userID = $session->get('userID');
  $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
  if (!$userID) {
    $config = CRM_Core_Config::singleton();
    if (version_compare($config->civiVersion, '4.3.alpha1') < 0) {
      //FIX: source id for version less that 4.3
      $matches = array();
      preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $params['from'], $matches);
      if (!empty($matches)) {
        $userID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $matches[0], 'contact_id', 'email');
        if (!$userID) {
          $userID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $params['toEmail'], 'contact_id', 'email');
        }
      }
    }
    else {
      $userID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', CRM_Core_Config::domainID(), 'contact_id');
    }
  }

  $activityParams = array( 
    'source_contact_id' => $userID,
    'activity_type_id' => array_search('Mandrill Email Sent', $activityTypes),
    'subject' => CRM_Utils_Array::value('subject', $params) ? $params['subject'] : CRM_Utils_Array::value('Subject', $params),
    'activity_date_time' => date('YmdHis'),
    'status_id' => 1,
    'priority_id' => 1,
    'version' => 3,
    'details' => $params['html'],
  );
  $result = civicrm_api( 'activity','create',$activityParams );
  if(CRM_Utils_Array::value('id', $result)){
    $params['activityId'] = $result['id'];
    $params['headers']['X-MC-Metadata'] = '{"CiviCRM_Mandrill_id": "'.$result['id'].'" }';
    CRM_Core_Smarty::singleton()->assign('isTransactionalMail', 1);
    if (!method_exists(CRM_Utils_Hook::singleton(), 'alterMail')) {
      $mailer = & CRM_Core_Config::getMailer();
      mte_getmailer($mailer);
    }
  }
}

/**
 * Implementation of hook_civicrm_postEmailSend( )
 * To update the status of activity created in hook_civicrm_alterMailParams.
 */
function mte_civicrm_postEmailSend(&$params) {
  if(CRM_Utils_Array::value('activityId', $params)){
    $activityParams = array( 
      'id' => $params['activityId'],
      'status_id' => 2,
      'version' => 3,
      'target_contact_id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $params['toEmail'], 'contact_id', 'email'), 
    );
    $result = civicrm_api( 'activity','create',$activityParams );
  }
}