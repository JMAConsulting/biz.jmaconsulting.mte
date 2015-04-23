<?php

/**
 * Mandrill Transactional Email extension integrates CiviCRM's non-bulk email 
 * with the Mandrill service
 * 
 * Copyright (C) 2012-2015 JMA Consulting
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

class CRM_Mte_Page_callback extends CRM_Core_Page {
	
  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  function run() {
    $secretCode = CRM_Utils_Type::escape($_GET['mandrillSecret'], 'String');
    $mandrillSecret = CRM_Core_OptionGroup::values('mandrill_secret', TRUE);
    if ($secretCode !== $mandrillSecret['Secret Code']) {
      return FALSE;
    }
    
    if (CRM_Utils_Array::value('mandrill_events', $_POST)) {
      $reponse = json_decode($_POST['mandrill_events'], TRUE);
      
      if (is_array($reponse) && !empty($reponse)) {
        CRM_Mte_BAO_Mandrill::processMandrillCalls($reponse);
      }
    }
    CRM_Utils_System::civiExit();
  }
}

