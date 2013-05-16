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

class CRM_Mte_Page_callback extends CRM_Core_Page {
	
  function run() {
    $secretCode = CRM_Utils_Type::escape($_GET['mandrillSecret'], 'String');
    $mandrillSecret = CRM_Core_OptionGroup::values('mandrill_secret', TRUE);
    if ($secretCode != $mandrillSecret['Secret Code']) {
      return FALSE;
    }
    if (CRM_Utils_Array::value('mandrill_events', $_POST)) {
      $bounceType = array();
      $reponse = json_decode($_POST['mandrill_events'], TRUE);
      if (is_array($reponse)) {
        $events = array('open','click','hard_bounce','soft_bounce','spam','reject');
        foreach ($reponse as $value) {
          //changes done to check if email exists in response array
          if (in_array($value['event'], $events) && CRM_Utils_Array::value('email', $value['msg'])) {
           
            $mail = new CRM_Mailing_DAO_Mailing();
            $mail->domain_id = CRM_Core_Config::domainID();
            $mail->subject = "***All Transactional Emails***";
            $mail->url_tracking = TRUE;
            $mail->forward_replies = FALSE;
            $mail->auto_responder = FALSE;
            $mail->open_tracking = TRUE;
            
            $contacts = array();
            if ($mail->find(TRUE)) {
              
              $emails = self::retrieveEmailContactId($value['msg']['email']);
              
              if (!CRM_Utils_Array::value('contact_id', $emails['email'])) {
                continue;
              }
              $params = array(
                'job_id' => CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Job', $mail->id, 'id', 'mailing_id'),
                'contact_id' => $emails['email']['contact_id'],
                'email_id' => $emails['email']['id'],
                'activity_id' => CRM_Utils_Array::value('metadata', $value['msg']) ? CRM_Utils_Array::value('CiviCRM_Mandrill_id', $value['msg']['metadata']) : null
              );
              $eventQueue = CRM_Mailing_Event_BAO_Queue::create($params);
              $bType = ucfirst(preg_replace('/_\w+/', '', $value['event']));
              switch ($value['event']) {
              case 'open':
                $oe                 = new CRM_Mailing_Event_BAO_Opened();
                $oe->event_queue_id = $eventQueue->id;
                $oe->time_stamp     = date('YmdHis', $value['ts']);
                $oe->save();
                break;
                
              case 'click':
                $tracker = new CRM_Mailing_BAO_TrackableURL();
                $tracker->url = $value['url'];
                $tracker->mailing_id = $mail->id;
                if (!$tracker->find(TRUE)) {
                  $tracker->save();
                }
                $open = new CRM_Mailing_Event_BAO_TrackableURLOpen();
                $open->event_queue_id = $eventQueue->id;
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
                $bounce             = new CRM_Mailing_Event_BAO_Bounce();
                $bounce->time_stamp =  date('YmdHis', $value['ts']);
                $bounce->event_queue_id = $eventQueue->id;
                $bounce->bounce_type_id = $bounceType["Mandrill $bType"];
                $bounce->bounce_reason  = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_BounceType', $bounceType["Mandrill $bType"], 'description');
                $bounce->save();
                break;
              }
              
              // create activity for click and open event
              if ($value['event'] == 'open' || $value['event'] == 'click') {
                $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
                $sourceContactId = self::retrieveEmailContactId($value['msg']['sender'], TRUE);
                if (!CRM_Utils_Array::value('contact_id', $sourceContactId['email'])) {
                  continue;
                }
                $activityParams = array( 
                  'source_contact_id' => $sourceContactId['email']['contact_id'],
                  'activity_type_id' => array_search("Mandrill Email $bType", $activityTypes),
                  'subject' => CRM_Utils_Array::value('subject', $value['msg']) ? $value['msg']['subject'] : "Mandrill Email $bType",
                  'activity_date_time' => date('YmdHis'),
                  'status_id' => 1,
                  'priority_id' => 1,
                  'version' => 3,
                  'target_contact_id' => $emails['contactIds'],
                );
                civicrm_api( 'activity','create',$activityParams );
              }
            }
          }
        }
      }
    }
    CRM_Utils_System::civiExit();
  }

  /* Function to retrieve email details of sender and to
   * 
   * $email string email id
   * $checkUnique to check unique email id i.e no more then 1 contact for a email ID.
   *
   */
  function retrieveEmailContactId($email, $checkUnique = FALSE) {
    if(!$email) {
      return FALSE;
    }
    $emails['email'] = null;
    $params = array( 
      'email' => $email,
      'version' => 3,
    );
    $result = civicrm_api( 'email','get',$params );
    
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
    return $emails;
  }
}

