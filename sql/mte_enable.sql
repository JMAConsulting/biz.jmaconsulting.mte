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
-- enable activity type
UPDATE civicrm_option_value cov
INNER JOIN civicrm_option_group cog ON cog.id = cov.option_group_id
SET cov.is_active = 1
WHERE cog.name IN ('activity_type', 'mandrill_secret') AND cov.name IN('Mandrill Email Bounce', 'Mandrill Email Click', 'Mandrill Email Open', 'Mandrill Email Sent','Secret Code');

UPDATE civicrm_option_group
SET is_active = 1
WHERE name  = 'mandrill_secret';