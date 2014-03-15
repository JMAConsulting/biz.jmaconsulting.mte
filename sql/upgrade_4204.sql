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
SELECT @civimail := id FROM civicrm_navigation WHERE name = 'CiviMail';

SELECT @maxWeight := max(weight) + 1 FROM civicrm_navigation WHERE parent_id = @civimail;

INSERT INTO civicrm_navigation (domain_id, label, name, url, permission, permission_operator, parent_id, is_active, has_separator, weight)
VALUES (1, 'Mandrill Smtp Settings', 'mandrill_smtp_settings', 'civicrm/mte/smtp?reset=1', 'access CiviCRM,administer CiviCRM', 'AND', @civimail, 1, 2, @maxWeight);