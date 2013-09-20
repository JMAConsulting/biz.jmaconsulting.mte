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

-- Drop column in civicrm_mailing_event_queue as activity_id of type email 
ALTER TABLE `civicrm_mailing_event_queue`
DROP FOREIGN KEY FK_civicrm_mailing_event_queue_activity_id,
DROP INDEX FK_civicrm_mailing_event_queue_activity_id;

-- Drop activity_id column
ALTER TABLE `civicrm_mailing_event_queue` 
  DROP `activity_id`;

-- Delete data for mandrill extensions
DELETE FROM `civicrm_mailing` 
WHERE `civicrm_mailing`.`subject` = '***All Transactional Emails***' AND `civicrm_mailing`.`url_tracking` = 1 AND `civicrm_mailing`.`forward_replies` = 0 AND `civicrm_mailing`.`auto_responder` = 0
AND `civicrm_mailing`.`open_tracking` = 1 AND `civicrm_mailing`.`is_completed` = 0;

DELETE FROM `civicrm_mailing_job` WHERE `job_type` = 'Special: All transactional emails being sent through Mandrill';

DELETE FROM `civicrm_mailing_bounce_type` WHERE `name` in ('Mandrill Hard', 'Mandrill Soft', 'Mandrill Spam', 'Mandrill Reject');

-- Change enum for name in civicrm_mailing_bounce_type to remove all Mandrill bounce types
ALTER TABLE `civicrm_mailing_bounce_type` 
  CHANGE `name` `name` ENUM( 'AOL', 'Away', 'DNS', 'Host', 'Inactive', 'Invalid', 'Loop', 'Quota', 'Relay', 'Spam', 'Syntax', 'Unknown' ) 
    CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of bounce';

-- Delete all the activities and 'Mandrill Email Sent' activity type
DELETE  `civicrm_activity`, `civicrm_option_value`
FROM `civicrm_activity`, `civicrm_option_group`, `civicrm_option_value` 
WHERE `civicrm_option_group`.`name` = 'activity_type' 
AND `civicrm_option_group`.`id` = `civicrm_option_value`.`option_group_id`
AND `civicrm_option_value`.`name` IN ('Mandrill Email Sent', 'Mandrill Email Open', 'Mandrill Email Click', 'Mandrill Email Bounce')
AND `civicrm_activity`.`activity_type_id` = `civicrm_option_value`.`value`;

-- MTE-14
DELETE cg, cv FROM civicrm_option_group cg
INNER JOIN civicrm_option_value cv ON cg.id = cv.option_group_id
WHERE cg.name = 'mandrill_secret';
