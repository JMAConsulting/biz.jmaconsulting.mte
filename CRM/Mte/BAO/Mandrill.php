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
class CRM_Mte_BAO_Mandrill extends CRM_Core_DAO {
  
  /**
   * static cache for pseudoconstant arrays
   * @var array
   * @static
   */
  private static $_contacts;

  
  /**
   * static cache to hold Activity id for bulk mailing
   * @var array
   * @static
   */
  public static $_mailingActivityId = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /*
   * function to process mandill call backs
   *
   * @access public
   * @static
   *
   * @param array $response - array of response from Mandrill
   *
   */
  public static function processMandrillCalls($reponse) {
    $events = array('open','click','hard_bounce','soft_bounce','spam','reject');
    $bounceType = array();
        
    //MTE-17
    $config = CRM_Core_Config::singleton();
    if (property_exists($config, 'civiVersion')) {
      $civiVersion = $config->civiVersion;
    }
    else {
      $civiVersion = CRM_Core_BAO_Domain::version();
    }
        
    if (version_compare('4.4alpha1', $civiVersion) > 0) {
      $jobCLassName = 'CRM_Mailing_DAO_Job';
    }
    else {
      $jobCLassName = 'CRM_Mailing_DAO_MailingJob';    
    }
    
    foreach ($reponse as $value) {
      //changes done to check if email exists in response array
      if (in_array($value['event'], $events) && CRM_Utils_Array::value('email', $value['msg'])) {
        
        $metaData = CRM_Utils_Array::value('metadata', $value['msg']) ? CRM_Utils_Array::value('CiviCRM_Mandrill_id', $value['msg']['metadata']) : NULL;
        $header = self::extractHeader($metaData);
        $mail = self::getMailing($header, $jobCLassName);
        $contacts = array();
        
        if ($mail->find(TRUE)) {
          if (($value['event'] == 'click' && $mail->url_tracking === FALSE) 
            || ($value['event'] == 'open' && $mail->open_tracking === FALSE)
          ) {
            continue;
          }
          $emails = self::retrieveEmailContactId($value['msg']['email']);
          if (!CRM_Utils_Array::value('contact_id', $emails['email'])) {
            continue;
          }
          $value['mailing_id'] = $mail->id;          
          // IF no activity id in header then create new activity
          if (empty($header[0])) {
            self::createActivity($value, NULL, $header);
          }
          if (empty($header[2])) {
            $params = array(
              'job_id' => CRM_Core_DAO::getFieldValue($jobCLassName, $mail->id, 'id', 'mailing_id'),
              'contact_id' => $emails['email']['contact_id'],
              'email_id' => $emails['email']['id'],
            );
            $eventQueue = CRM_Mailing_Event_BAO_Queue::create($params);
            $eventQueueID = $eventQueue->id;
            $hash = $eventQueue->hash;
            $jobId = $params['job_id'];
          }
          else {
            $eventQueueID = $header[3];
            $hash = explode('@', $header[4]);
            $hash = $hash[0];
            $jobId = $header[2];
          }
          if ($eventQueueID) {
            $mandrillActivtyParams = array(
              'mailing_queue_id' => $eventQueueID,
              'activity_id' => $header[0],
              );
            CRM_Mte_BAO_MandrillActivity::create($mandrillActivtyParams);
          }
          $msgBody = '';
          if (!empty($header[0])) { 
            $msgBody = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $header[0], 'details');
          }
          $value['mail_body'] = $msgBody;
          
          $bType = ucfirst(preg_replace('/_\w+/', '', $value['event']));
          $assignedContacts = array();
          switch ($value['event']) {
          case 'open':
            $oe = new CRM_Mailing_Event_BAO_Opened();
            $oe->event_queue_id = $eventQueueID;
            $oe->time_stamp = date('YmdHis', $value['ts']);
            $oe->save();
            break;
                
          case 'click':
            if (CRM_Utils_Array::value(1, $header) == 'b') {
              break;
            }
            $tracker = new CRM_Mailing_BAO_TrackableURL();
            $tracker->url = $value['url'];
            $tracker->mailing_id = $mail->id;
            if (!$tracker->find(TRUE)) {
              $tracker->save();
            }
            $open = new CRM_Mailing_Event_BAO_TrackableURLOpen();
            $open->event_queue_id = $eventQueueID;
            $open->trackable_url_id = $tracker->id;
            $open->time_stamp = date('YmdHis', $value['ts']);
            $open->save();
            break;
                
          case 'hard_bounce':
          case 'soft_bounce':
          case 'spam':
          case 'reject':
            if (empty($bounceType)) {
              CRM_Core_PseudoConstant::populate($bounceType, 'CRM_Mailing_DAO_BounceType', TRUE, 'id', NULL, NULL, NULL, 'name');
            }
            
            //Delete queue in delivered since this email is not successfull
            $delivered = new CRM_Mailing_Event_BAO_Delivered();
            $delivered->event_queue_id = $eventQueueID;
            if ($delivered->find(TRUE)) {
              $delivered->delete();
            }
            $bounceParams = array(
              'time_stamp' => date('YmdHis', $value['ts']),
              'event_queue_id' => $eventQueueID,
              'bounce_type_id' => $bounceType["Mandrill $bType"],
              'job_id' => $jobId,
              'hash' => $hash,
            );
            $bounceParams['bounce_reason'] = CRM_Utils_Array::value('bounce_description', $value['msg']);
            if (empty($bounceParams['bounce_reason'])) {
              $bounceParams['bounce_reason'] = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_BounceType', $bounceType["Mandrill $bType"], 'description');
            }
            CRM_Mailing_Event_BAO_Bounce::create($bounceParams);
                  
            if (substr($value['event'], -7) == '_bounce') {
              $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
                'mandrill_smtp_settings'
              );
              if (CRM_Utils_Array::value('group_id', $mailingBackend)) {
                list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
                $mailBody = ts('The following email failed to be delivered due to a') . " {$bType} Bounce :</br>
To: {$value['msg']['email']} </br>
From: {$value['msg']['sender']} </br>
Subject: {$value['msg']['subject']}</br>
Message Body: {$msgBody}" ;
                $mailParams = array(
                  'groupName' => 'Mandrill bounce notification',
                  'from' => '"' . $domainEmailName . '" <' . $domainEmailAddress . '>',
                  'subject' => ts('Mandrill Bounce Notification'),
                  'text' => $mailBody,
                  'html' => $mailBody,
                );
                $query = "SELECT ce.email, cc.sort_name, cgc.contact_id FROM civicrm_contact cc
INNER JOIN civicrm_group_contact cgc ON cgc.contact_id = cc.id
INNER JOIN civicrm_email ce ON ce.contact_id = cc.id
WHERE cc.is_deleted = 0 AND cc.is_deceased = 0 AND cgc.group_id = {$mailingBackend['group_id']} AND ce.is_primary = 1 AND ce.email <> %1";
                $queryParam = array(1 => array($value['msg']['email'], 'String'));
                $dao = CRM_Core_DAO::executeQuery($query, $queryParam);
                while ($dao->fetch()) {
                  $mailParams['toName'] = $dao->sort_name;
                  $mailParams['toEmail'] = $dao->email;
                  CRM_Utils_Mail::send($mailParams);
                  $value['assignee_contact_id'][] = $dao->contact_id;
                }
              }
            }
            $bType = 'Bounce';
            break;
          }
              
          // create activity for click and open event
          if ($value['event'] == 'open' || $value['event'] == 'click' || $bType == 'Bounce') {
            self::createActivity($value, $bType, $header);
          }
        }
      }
    }
  }
  
  /* Function to retrieve email details of sender and to
   * 
   * @access public
   * @static
   *
   * @param $email string email id
   * @param $checkUnique to check unique email id i.e no more then 1 contact for a email ID.
   *
   * @return array - array refrence of email details
   */
  public static function retrieveEmailContactId($email, $checkUnique = FALSE) {
    if(!$email) {
      return FALSE;
    }
    $cacheKey = $email . $checkUnique;
    if (CRM_Utils_Array::value($cacheKey, self::$_contacts)) {
      return self::$_contacts[$cacheKey];
    }
    
    $emails['email'] = null;
    $params = array( 
      'email' => $email,
      'version' => 3,
    );
    $result = civicrm_api('email', 'get', $params);
    
    //if contact not found then create new one
    if (!$result['count']) {
      $contactParams = array(
        'contact_type' => 'Individual',
        'email' => $email,
      );
      civicrm_api3('contact', 'create', $contactParams);
      $result = civicrm_api('email', 'get', $params);      
    }
    
    // changes done for bad data, sometimes there are multiple emails but without contact id   
    foreach ($result['values'] as $emailId => $emailValue) {
      if (CRM_Utils_Array::value('contact_id', $emailValue)) {
        if (CRM_Utils_Array::value('email', $emails) && $checkUnique) {
          return FALSE;
        }
        if (!CRM_Utils_Array::value('email', $emails)) {
          $emails['email'] = $emailValue;
        }
        if (!$checkUnique) {
          $emails['contactIds'][] = $emailValue['contact_id'];
        }
      }
    }
    self::$_contacts[$cacheKey] = $emails;
    return $emails;
  }

  /*
   * Function to extract meta data of Header
   *
   * @access public
   * @static
   *
   * @param string $metaData - MetaData from Mandrill Header
   *
   * @return array of Meta data
   */
  public static function extractHeader($metaData) {
    if (!$metaData) {
      return array();
    }    
    return explode(CRM_Core_Config::singleton()->verpSeparator, $metaData); 
  }
  
  /*
   * Function to generate Mailing Class
   *
   * @access public
   * @static
   *
   * @param array $header - MetaData from Mandrill Header
   *
   * @return object of CRM_Mailing_DAO_Mailing()
   */
  public static function getMailing(&$header, $jobClass) {    
    $mail = new CRM_Mailing_DAO_Mailing();
    $mail->domain_id = CRM_Core_Config::domainID();
    if (CRM_Utils_Array::value(1, $header)) {
      $mail->id = CRM_Core_DAO::getFieldValue($jobClass, $header[2], 'mailing_id');
    }
    else {  
      $mail->subject = "***All Transactional Emails***";
      $mail->url_tracking = TRUE;
      $mail->forward_replies = FALSE;
      $mail->auto_responder = FALSE;
      $mail->open_tracking = TRUE;
    }
    
    return $mail;
  }

  /*
   * Function to create Activity
   *
   * @access public
   * @static
   *
   * @param array $value - array of response from Mandrill
   *
   * @param string $context - MetaData from Mandrill Header
   *
   * @param array $header - MetaData from Mandrill Header
   *
   */
  public static function createActivity($value, $context = NULL, &$header = array()) {
    $sourceContactId = self::retrieveEmailContactId($value['msg']['sender'], TRUE);
    if (!CRM_Utils_Array::value('contact_id', $sourceContactId['email'])) {
      continue;
    }
    $emails = self::retrieveEmailContactId($value['msg']['email']);
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    $subject = CRM_Utils_Array::value('subject', $value['msg']) ? $value['msg']['subject'] : "Mandrill Email $bType";
    if ($context) {
      $typeId = array_search("Mandrill Email $context", $activityTypes);
    }
    else {
      $typeId = array_search("Mandrill Email Sent", $activityTypes);
      $subject = ts('Email sent from Mandrill App: ') . $subject;
    }
    $activityParams = array( 
      'source_contact_id' => $sourceContactId['email']['contact_id'],
      'activity_type_id' => $typeId,
      'subject' => $subject,
      'activity_date_time' => date('YmdHis'),
      'status_id' => 2,
      'priority_id' => 1,
      'version' => 3,
      'target_contact_id' => array_unique($emails['contactIds']),
      'source_record_id' => CRM_Utils_Array::value('mailing_id', $value),
      'details' => CRM_Utils_Array::value('mail_body', $value),
    );
    
    if (CRM_Utils_Array::value('assignee_contact_id', $value)) {
      $activityParams['assignee_contact_id'] = array_unique($value['assignee_contact_id']);
    }
    if (!empty($header[0])) {
      $activityParams['original_id'] = $header[0];
    }
    
    $result = civicrm_api('activity', 'create', $activityParams);
    if (empty($header) && !empty($result['id'])) {
      $header[0] = $result['id'];
    }
  }
}

