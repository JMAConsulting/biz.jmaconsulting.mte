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
    if (CRM_Utils_Array::value('mandrill_events', $_POST)) {
      $bounceType = array();
      $reponse = json_decode($_POST['mandrill_events'], TRUE);
      if (is_array($reponse)) {
        $events = array('open','click','hard_bounce','soft_bounce','spam','reject');
        foreach ($reponse as $value) {
          if (in_array($value['event'], $events)) {
            $params = array( 
              'email' => $value['msg']['email'],
              'version' => 3,
            );
            $result = civicrm_api( 'email','get',$params );
            
            $mail = new CRM_Mailing_DAO_Mailing();
            $mail->domain_id = CRM_Core_Config::domainID();
            $mail->subject = "***All Transactional Emails***";
            $mail->url_tracking = TRUE;
            $mail->forward_replies = FALSE;
            $mail->auto_responder = FALSE;
            $mail->open_tracking = TRUE;

            if ($mail->find(TRUE) && CRM_Utils_Array::value('values', $result) && !empty($result['values'])){
              $emails = reset($result['values']);
              $params = array(
                'job_id' => CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Job', $mail->id, 'id', 'mailing_id'),
                'contact_id' => $emails['contact_id'],
                'email_id' => $emails['id'],
                'activity_id' => CRM_Utils_Array::value('metadata', $value['msg']) ? CRM_Utils_Array::value('CiviCRM_Mandrill_id', $value['msg']['metadata']) : null
              );
              $eventQueue = CRM_Mailing_Event_BAO_Queue::create($params);
            }
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
            $bType = ucfirst(preg_replace('/_\w+/', '', $value['event']));
            $bounce             = new CRM_Mailing_Event_BAO_Bounce();
            $bounce->time_stamp =  date('YmdHis', $value['ts']);
            $bounce->event_queue_id = $eventQueue->id;
            $bounce->bounce_type_id = $bounceType["Mandrill $bType"];
            $bounce->bounce_reason  = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_BounceType', $bounceType["Mandrill $bType"], 'description');
            $bounce->save();
            break;
            }
          }
        }
      }
    }
   
    CRM_Utils_System::civiExit();
  }
}      