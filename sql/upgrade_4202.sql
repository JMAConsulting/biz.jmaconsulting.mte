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

-- MTE-14 add option group Mandrill Secret
INSERT INTO `civicrm_option_group` (name, title, description, is_reserved, is_active)
VALUES ('mandrill_secret', 'Mandrill Secret', 'Mandrill Secret', '1', 1);
SET @optionGroupId := LAST_INSERT_ID();

INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `weight`, `description`) 
VALUES (@optionGroupId, 'Secret Code', SUBSTRING(MD5(RAND()) FROM 1 FOR 16), 'Secret Code', 1, 'Mandrill Email Sent');
       