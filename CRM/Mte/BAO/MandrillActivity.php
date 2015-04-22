<?php
/**
 * Mandrill Transactional Email extension integrates CiviCRM's non-bulk email 
 * with the Mandrill service
 * 
 * Copyright (C) 2015 JMA Consulting
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
class CRM_Mte_BAO_MandrillActivity extends CRM_Mte_DAO_MandrillActivity {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * 
   * @param array     The values of the new MandrillActivity
   *
   * @return object   The new MandrillActivity mapping
   * @access public
   * @static
   */
  public static function &create(&$params) {
    $dao = new CRM_Mte_DAO_MandrillActivity();
    $dao->copyValues($params);
    if (!$dao->find(TRUE)) {
      $dao->save();
    }
    return $dao;
  }
}

