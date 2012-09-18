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


-- add new activity type of Mandrill Email Open and Mandrill Email Click.

SELECT @civicrm_activity_type_id := id FROM `civicrm_option_group` WHERE `name` LIKE 'activity_type';

SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @civicrm_activity_type_id;
SELECT @weight  := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @civicrm_activity_type_id;

INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `weight`, `description`) 
VALUES (@civicrm_activity_type_id, 'Mandrill Email Open', @max_val := @max_val+1, 'Mandrill Email Open', @weight := @weight+1, 'Mandrill Email Open'),
(@civicrm_activity_type_id, 'Mandrill Email Click', @max_val := @max_val+1, 'Mandrill Email Click', @weight := @weight+1, 'Mandrill Email Click');
