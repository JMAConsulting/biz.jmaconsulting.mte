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

{if $changeENUM}
ALTER TABLE `civicrm_mailing_bounce_type` 
  CHANGE `name` `name` ENUM( 'AOL', 'Away', 'DNS', 'Host', 'Inactive', 'Invalid', 'Loop', 'Quota', 'Relay', 'Spam', 'Syntax', 'Unknown', 
    'Mandrill Hard', 'Mandrill Soft', 'Mandrill Spam', 'Mandrill Reject' ) 
    CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of bounce';
{/if}

-- add new activity type
SELECT @civicrm_activity_type_id := id FROM `civicrm_option_group` WHERE `name` LIKE 'activity_type';

SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @civicrm_activity_type_id;
SELECT @weight := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @civicrm_activity_type_id;

INSERT INTO `civicrm_option_value` (`option_group_id`,  {localize field='label'}label{/localize}, `value`, `name`, `weight`,  {localize field='description'}description{/localize}, is_reserved) 
VALUES (@civicrm_activity_type_id, {localize}'Mandrill Email Sent'{/localize}, @max_val := @max_val+1, 'Mandrill Email Sent', @weight := @weight+1, {localize}'Mandrill Email Sent'{/localize}, 1),
(@civicrm_activity_type_id, {localize}'Mandrill Email Open'{/localize}, @max_val := @max_val+1, 'Mandrill Email Open', @weight := @weight+1, {localize}'Mandrill Email Open'{/localize}, 1),
(@civicrm_activity_type_id, {localize}'Mandrill Email Click'{/localize}, @max_val := @max_val+1, 'Mandrill Email Click', @weight := @weight+1, {localize}'Mandrill Email Click'{/localize}, 1),
(@civicrm_activity_type_id, {localize}'Mandrill Email Bounce'{/localize}, @max_val := @max_val+1, 'Mandrill Email Bounce', @weight := @weight+1, {localize}'Mandrill Email Bounce'{/localize}, 1);

-- MTE-14 add option group Mandrill Secret
INSERT INTO `civicrm_option_group` (name, {localize field='title'}title{/localize}, {localize field='description'}description{/localize}, is_reserved, is_active)
VALUES ('mandrill_secret', {localize}'Mandrill Secret'{/localize}, {localize}'Mandrill Secret'{/localize}, '1', 1);
SET @optionGroupId := LAST_INSERT_ID();

INSERT INTO `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `weight`, {localize field='description'}`description`{/localize}) 
VALUES (@optionGroupId, {localize}'Secret Code'{/localize}, SUBSTRING(MD5(RAND()) FROM 1 FOR 16), 'Secret Code', 1, {localize}'Mandrill Email Sent'{/localize});

-- MTE-18
SELECT @civimail := id FROM civicrm_navigation WHERE name = 'System Settings';

SELECT @outbound_mail := weight FROM civicrm_navigation WHERE parent_id = @civimail and name = 'Outbound Email';

UPDATE civicrm_navigation 
SET weight = weight + 1
WHERE parent_id = @civimail and weight > @outbound_mail;

INSERT INTO civicrm_navigation (domain_id, label, name, url, permission, permission_operator, parent_id, is_active, has_separator, weight)
VALUES ({$domainID}, 'Mandrill Smtp Settings', 'mandrill_smtp_settings', 'civicrm/mte/smtp?reset=1', 'access CiviCRM,administer CiviCRM', 'AND', @civimail, 1, NULL, @outbound_mail + 1);

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
