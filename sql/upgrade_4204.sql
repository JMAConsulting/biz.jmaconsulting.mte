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
-- MTE-18
SELECT @civimail := id FROM civicrm_navigation WHERE name = 'System Settings';

SELECT @outbound_mail := weight FROM civicrm_navigation WHERE parent_id = @civimail and name = 'Outbound Email';

UPDATE civicrm_navigation 
SET weight = weight + 1
WHERE parent_id = @civimail and weight > @outbound_mail;

INSERT INTO civicrm_navigation (domain_id, label, name, url, permission, permission_operator, parent_id, is_active, has_separator, weight)
VALUES (1, 'Mandrill Smtp Settings', 'mandrill_smtp_settings', 'civicrm/mte/smtp?reset=1', 'access CiviCRM,administer CiviCRM', 'AND', @civimail, 1, NULL, @outbound_mail + 1);

-- MTE-19
CREATE TABLE IF NOT EXISTS `civicrm_mandrill_activity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mailing_queue_id` int(10) unsigned NOT NULL COMMENT 'FK to Mailing Queue',
  `activity_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Activity',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_mandrill_activity_mailing_queue_id` (`mailing_queue_id`),
  KEY `FK_civicrm_mandrill_activity_activity_id` (`activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

ALTER TABLE `civicrm_mandrill_activity`
  ADD CONSTRAINT `FK_civicrm_mandrill_activity_mailing_queue_id` FOREIGN KEY (`mailing_queue_id`) REFERENCES `civicrm_mailing_event_queue` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_mandrill_activity_activity_id` FOREIGN KEY (`activity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE CASCADE;

-- move activity_id to new table from civicrm_mailing_event_queue
INSERT INTO civicrm_mandrill_activity (mailing_queue_id, activity_id)
SELECT cq.id, cq.activity_id FROM civicrm_mailing_event_queue cq
INNER JOIN civicrm_mailing_job cb ON cb.id = cq.job_id
WHERE cb.job_type = "Special: All transactional emails being sent through Mandrill" AND activity_id IS NOT NULL;

-- Drop column in civicrm_mailing_event_queue as activity_id of type email 
ALTER TABLE `civicrm_mailing_event_queue`
DROP FOREIGN KEY FK_civicrm_mailing_event_queue_activity_id,
DROP INDEX FK_civicrm_mailing_event_queue_activity_id;

-- Drop activity_id column
ALTER TABLE `civicrm_mailing_event_queue` 
  DROP `activity_id`;
