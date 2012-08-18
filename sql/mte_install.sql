-- Change enum for name in civicrm_mailing_bounce_type to add new Mandrill bounce types
-- TODO: change this enum to a pseudo-FK to option_value / option_group tables
ALTER TABLE `civicrm_mailing_bounce_type` 
  CHANGE `name` `name` ENUM( 'AOL', 'Away', 'DNS', 'Host', 'Inactive', 'Invalid', 'Loop', 'Quota', 'Relay', 'Spam', 'Syntax', 'Unknown', 
    'Mandrill Hard', 'Mandrill Soft', 'Mandrill Spam', 'Mandrill Reject' ) 
    CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of bounce';

-- Add column in civicrm_mailing_event_queue as activity_id of type email 
ALTER TABLE `civicrm_mailing_event_queue` 
  ADD `activity_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Activity id of activity type email and bulk mail.';

ALTER TABLE `civicrm_mailing_event_queue`
  ADD CONSTRAINT `FK_civicrm_mailing_event_queue_activity_id` FOREIGN KEY (`activity_id`) REFERENCES `civicrm_activity` (`id`);
