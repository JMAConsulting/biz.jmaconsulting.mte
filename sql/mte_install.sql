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
