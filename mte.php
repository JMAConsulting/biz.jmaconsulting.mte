<?php
/**
 * Mandrill Transactional Email extension integrates CiviCRM's non-bulk email 
 * with the Mandrill service
 * 
 * Copyright (C) 2012-2015 JMA Consulting
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
  if ($config->userFramework == 'Joomla' 
    && 'civicrm/ajax/mte/callback' == CRM_Utils_Array::value('task', $_REQUEST)) {
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
  $mailingParams = array(
    'subject' => '***All Transactional Emails***',
    'name' => ts('Transaction Emails'),
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
  $config = CRM_Core_Config::singleton();
  if (property_exists($config, 'civiVersion')) {
    $civiVersion = $config->civiVersion;
  }
  else {
    $civiVersion = CRM_Core_BAO_Domain::version();
  }
  
  $jobCLassName = 'CRM_Mailing_DAO_MailingJob';
  if (version_compare('4.4alpha1', $civiVersion) > 0) {
    $jobCLassName = 'CRM_Mailing_DAO_Job';
  }
  
  $changeENUM = FALSE;
  if (version_compare('4.5alpha1', $civiVersion) > 0) {
    $changeENUM = TRUE;
  }
  CRM_Core_Smarty::singleton()->assign('changeENUM', $changeENUM);
  _mte_civix_civicrm_install();
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
  mte_enableDisableNavigationMenu(2);

  // Change enum for name in civicrm_mailing_bounce_type to remove all Mandrill bounce types
  $config = CRM_Core_Config::singleton();
  if (property_exists($config, 'civiVersion')) {
    $civiVersion = $config->civiVersion;
  }
  else {
    $civiVersion = CRM_Core_BAO_Domain::version();
  }
  
  if (version_compare('4.5alpha1', $civiVersion) > 0) {
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_mailing_bounce_type` 
  CHANGE `name` `name` ENUM( 'AOL', 'Away', 'DNS', 'Host', 'Inactive', 'Invalid', 'Loop', 'Quota', 'Relay', 'Spam', 'Syntax', 'Unknown' ) 
    CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of bounce';");
  }
  return _mte_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function mte_civicrm_enable() {
  mte_enableDisableNavigationMenu(1);
  return _mte_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function mte_civicrm_disable() {
  mte_enableDisableNavigationMenu(0);  
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
  $alterMailer = CRM_Core_Smarty::singleton()->get_template_vars('alterMailer', 1);
  if (!$alterMailer) {
    $alterMailer = mte_checkSettings('civimail');
  }
  if ($alterMailer == 1) {
    mte_getmailer($mailer, $params);
  } 
}

/*
 * Function to over-ride mailer settings with Mandrill Smtp settings
 *
 * @param object $mailer  -- Object of Mailer defined in OutBound Email Settings
 * @param array  $params  -- Array of params
 *
 */
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
    CRM_Core_Smarty::singleton()->assign('alterMailer', 0);
  }
}

/**
 * Implementation of hook_civicrm_alterMailParams( )
 * To send headers in mail and also create activity
 */
function mte_civicrm_alterMailParams(&$params, $context = NULL) {
  if ($context != 'civimail') {
    CRM_Core_Smarty::singleton()->assign('alterMailer', 'ignore');
  }
  if (!mte_checkSettings($context)) {
    return FALSE;
  }
  $session = CRM_Core_Session::singleton();
  $userID = $session->get('userID');
  $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
  $params['toEmail'] = trim($params['toEmail']); // BRES-103 Prevent silent failure when emails with whitespaces are used.
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

  if ($context == 'civimail' && CRM_Mte_BAO_Mandrill::$_mailingActivityId) {
    $activityParams = array( 
      'id' => CRM_Mte_BAO_Mandrill::$_mailingActivityId,
      'target_contact_id' => mte_targetContactIds($params),
      'deleteActivityTarget' => FALSE,
      'version' => 3,
    );
  }
  else {
    $activityParams = array( 
      'source_contact_id' => $userID,
      'activity_type_id' => array_search('Mandrill Email Sent', $activityTypes),
      'subject' => CRM_Utils_Array::value('subject', $params) ? $params['subject'] : CRM_Utils_Array::value('Subject', $params),
      'activity_date_time' => date('YmdHis'),
      'status_id' => 2,
      'priority_id' => 1,
      'version' => 3,
      'details' => CRM_Utils_Array::value('html', $params, $params['text']),
      'target_contact_id' => mte_targetContactIds($params), 
    );
    if (!empty($params['job_id'])) {
      $jobCLassName = 'CRM_Mailing_DAO_MailingJob';
      if (version_compare('4.4alpha1', CRM_Core_Config::singleton()->civiVersion) > 0) {
        $jobCLassName = 'CRM_Mailing_DAO_Job';
      }
      $activityParams['source_record_id'] = CRM_Core_DAO::getFieldValue($jobCLassName, $params['job_id'], 'mailing_id');
    }
  }
  $result = civicrm_api('activity', 'create', $activityParams);
  if(CRM_Utils_Array::value('id', $result)){
    $params['activityId'] = $mandrillHeader = $result['id'];
    // include verp in header incase of bulk mailing
    if ($context == 'civimail') {
      $mandrillHeader .= CRM_Core_Config::singleton()->verpSeparator . CRM_Utils_Array::value('Return-Path', $params);
    }
    else {
      mte_createQueue($mandrillHeader, $params['toEmail']);
      $params['mandrillHeader'] = $mandrillHeader;
    }
    $params['headers']['X-MC-Metadata'] = '{"CiviCRM_Mandrill_id": "' . $mandrillHeader . '" }';
    CRM_Core_Smarty::singleton()->assign('alterMailer', 1);
    if ($context == 'civimail' && !CRM_Mte_BAO_Mandrill::$_mailingActivityId) {
      CRM_Mte_BAO_Mandrill::$_mailingActivityId = $result['id'];
    }
    if (!method_exists(CRM_Utils_Hook::singleton(), 'alterMail')) {
      $mailer = & CRM_Core_Config::getMailer();
      mte_getmailer($mailer);
    }
  }
}

/**
 * MTE-18 and MTE-38
 * function to disable/enable/delete navigation menu
 *
 * @param integer $action 
 *
 */

function mte_enableDisableNavigationMenu($action) {
  $domainID = CRM_Core_Config::domainID();
  
  if ($action < 2) { 
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_option_value cov
       INNER JOIN civicrm_option_group cog ON cog.id = cov.option_group_id
       SET cov.is_active = %1
       WHERE cog.name IN ('activity_type', 'mandrill_secret') 
       AND cov.name IN('Mandrill Email Bounce', 'Mandrill Email Click', 'Mandrill Email Open', 'Mandrill Email Sent','Secret Code')", 
      array(
        1 => array($action, 'Integer'),
      )
    ); 
    
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_option_group
       SET is_active = %1
       WHERE name  = 'mandrill_secret'", 
      array(
        1 => array($action, 'Integer')
      )
    ); 
    
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_navigation SET is_active = %2 WHERE name = 'mandrill_smtp_settings' AND domain_id = %1", 
      array(
        1 => array($domainID, 'Integer'),
        2 => array($action, 'Integer')
      )
    ); 
  }
  else {
    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_navigation  WHERE name = 'mandrill_smtp_settings' AND domain_id = %1", 
      array(
        1 => array($domainID, 'Integer')
      )
    );
  }
}
 
/**
 * Implementation of hook_civicrm_buildForm
 */
function mte_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Admin_Form_Options' && 'mandrill_secret' == $form->getVar('_gName')) {
    $values = $form->getVar('_values');

    if (CRM_Utils_Array::value('name', $values) != 'Secret Code') {
      return FALSE; 
    }
    $form->add('text',
      'value',
      ts('Value'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'),
      TRUE
    );
  }
} 

/*
 * function to check if Mandril enabled for 
 * Civimail v/s Transactional mail
 *
 */
function mte_checkSettings($context) {
  
  $usedFor = 1;
  if ($context == 'civimail') {
    $usedFor = 2;
  }
  
  $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mandrill_smtp_settings'
  );
  
  if (CRM_Utils_array::value('is_active', $mailingBackend) && 
    array_key_exists('used_for', $mailingBackend) 
    && !empty($mailingBackend['used_for'][$usedFor])
  ) {
    return TRUE;
  }
  return FALSE;
}

/*
 *
 * Function to get contact id from emails
 *
 * @param array $params - array of params of emails
 *
 * return array of contact id's
 */
function mte_targetContactIds($params) {
  $emails = array_merge(
    explode(
      ',', 
      $params['toEmail']
    ), 
    explode(
      ',', 
      CRM_Utils_Array::value('cc', $params, '')
    ), 
    explode(
      ',', 
      CRM_Utils_Array::value('bcc', $params, '')
    )
  );
  $targetContactIds = array();
  foreach ($emails as $email) {
    preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $email, $matches);
    if (!empty($matches[0])) {
      $targetContactID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', trim($matches[0]), 'contact_id', 'email');
      if (!$targetContactID) {
        $result = civicrm_api3('contact', 'create', array(
          'contact_type' => 'Individual',
          'email' => trim($matches[0]),
        ));
        $targetContactID = $result['id'];
      }
      $targetContactIds[] = $targetContactID;
    }
  }
  return array_unique($targetContactIds);
}

/**
 * Implementation of hook_civicrm_alterReportVar
 */
function mte_civicrm_alterReportVar($varType, &$var, &$object) {
  $instanceValue = $object->getVar('_instanceValues');
  if (!empty($instanceValue) && 
    in_array(
      $instanceValue['report_id'],
      array(
        'Mailing/bounce',
        'Mailing/opened',
        'Mailing/clicks',
        'mailing/detail',
      )
    )
  ) {
    if ($varType == 'sql') {
      if (array_key_exists('civicrm_mailing_mailing_name', $var->_columnHeaders)) {
        $var->_columnHeaders['civicrm_mandrill_activity_id'] = array(
          'type' => 1,
          'title' => 'activity',
        );
        $var->_columnHeaders['civicrm_mailing_id'] = array(
          'type' => 1,
          'title' => 'mailing id',
        );
        $var->_select .= ' , civicrm_mandrill_activity.activity_id as civicrm_mandrill_activity_id, mailing_civireport.id as civicrm_mailing_id ';
      
        $from = $var->getVar('_from');
        $from .= ' LEFT JOIN civicrm_mandrill_activity ON civicrm_mailing_event_queue.id = civicrm_mandrill_activity.mailing_queue_id';
        $var->setVar('_from', $from); 
        if ($instanceValue['report_id'] == 'Mailing/opened') {
          $var->_columnHeaders['opened_count'] = array(
            'type' => 1,
            'title' => ts('Opened Count'),
          );
          $var->_select .= ' , count(DISTINCT(civicrm_mailing_event_opened.id)) as opened_count';
          $var->_groupBy = ' GROUP BY civicrm_mailing_event_queue.id';
        }
      }
    }
    if ($varType == 'rows') { 
      $mail = new CRM_Mailing_DAO_Mailing();
      $mail->subject = "***All Transactional Emails***";
      $mail->url_tracking = TRUE;
      $mail->forward_replies = FALSE;
      $mail->auto_responder = FALSE;
      $mail->open_tracking = TRUE;
      $mail->find(true);
      if (array_key_exists('civicrm_mailing_mailing_name', $object->_columnHeaders)) {
        foreach ($var as $key => $value) {
          if (!empty($value['civicrm_mandrill_activity_id']) && $mail->id == $value['civicrm_mailing_id']) {
            $var[$key]['civicrm_mailing_mailing_name_link'] = CRM_Utils_System::url(
              'civicrm/activity',
              "reset=1&action=view&cid={$value['civicrm_contact_id']}&id={$value['civicrm_mandrill_activity_id']}"              
            );
            $var[$key]['civicrm_mailing_mailing_name_hover'] = ts('View Transactional Email');          
          }
        }
        unset($object->_columnHeaders['civicrm_mandrill_activity_id'], $object->_columnHeaders['civicrm_mailing_id']);
      }    
    }
  }
}

/*
 * function to create Mailing Queue when a message is about to send
 * 
 * @param string $mandrillHeader  -- Mandrill Header
 * @param string $toEmail         -- To Email Address
 *
 */
function mte_createQueue(&$mandrillHeader, $toEmail) {
  $mail = new CRM_Mailing_DAO_Mailing();
  $mail->subject = "***All Transactional Emails***";
  $mail->url_tracking = TRUE;
  $mail->forward_replies = FALSE;
  $mail->auto_responder = FALSE;
  $mail->open_tracking = TRUE;
  if ($mail->find(TRUE)) {
    $emails = CRM_Mte_BAO_Mandrill::retrieveEmailContactId($toEmail);
    $jobCLassName = 'CRM_Mailing_DAO_MailingJob';
    if (version_compare('4.4alpha1', CRM_Core_Config::singleton()->civiVersion) > 0) {
      $jobCLassName = 'CRM_Mailing_DAO_Job';
    }
    $params = array(
      'job_id' => CRM_Core_DAO::getFieldValue($jobCLassName, $mail->id, 'id', 'mailing_id'),
      'contact_id' => $emails['email']['contact_id'],
      'email_id' => $emails['email']['id'],
    );
    $eventQueue = CRM_Mailing_Event_BAO_Queue::create($params);
    $mandrillHeader = implode(CRM_Core_Config::singleton()->verpSeparator, array($mandrillHeader, 'm', $params['job_id'], $eventQueue->id, $eventQueue->hash));
  }
}

/**
 * Implementation of hook_civicrm_postEmailSend
 */
function mte_civicrm_postEmailSend(&$params) {
  if (!empty($params['mandrillHeader'])) {
    $header = explode(CRM_Core_Config::singleton()->verpSeparator, $params['mandrillHeader']);
    $params = array(
      'job_id' => $header[2],
      'event_queue_id' => $header[3],
      'hash' => $header[4],
    );
    CRM_Mailing_Event_BAO_Delivered::create($params);
    $mandrillActivtyParams = array(
      'mailing_queue_id' => $header[3],
      'activity_id' => $header[0],
    );
    CRM_Mte_BAO_MandrillActivity::create($mandrillActivtyParams);
  }
}