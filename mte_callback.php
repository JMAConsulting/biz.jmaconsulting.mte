<?php

session_start();

// TODO: find and use the correct function or pattern to get the civicrm path
// the extension directory may not be a sibling of it
require_once '../../civicrm/civicrm.config.php';
require_once '../../civicrm/CRM/Core/Config.php';

$config = CRM_Core_Config::singleton();

if (CRM_Utils_Array::value('mandrill_events', $_POST)) {
  require_once 'api/api.php';
  require_once 'CRM/Mailing/DAO/Mailing.php';
  require_once 'CRM/Mailing/Event/BAO/Queue.php';
  require_once 'CRM/Mailing/Event/BAO/Opened.php';
  require_once 'CRM/Mailing/BAO/TrackableURL.php';
  require_once 'CRM/Mailing/Event/BAO/TrackableURLOpen.php';
  require_once 'CRM/Core/PseudoConstant.php';
  require_once 'CRM/Mailing/Event/BAO/Bounce.php';
  $bounceType = array();
  $reponse = json_decode($_POST['mandrill_events'], TRUE);
  if (is_array($reponse)) {
    foreach ($reponse as $value) {
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
          'activity_id' => CRM_Utils_Array::value('metadata', $value) ? CRM_Utils_Array::value('CiviCRM_Mandrill_id', $value['metadata']) : null
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
