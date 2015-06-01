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

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Mte_Form_MandrillSmtpSetting extends CRM_Admin_Form_Setting {
  
  protected $_testButtonName;

  /**
   * Build the form object.
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Mandrill SMTP Settings - Outbound Mail'));
    $this->add('text', 'smtpServer', ts('SMTP Server'), NULL, TRUE);
    $this->add('text', 'smtpPort', ts('SMTP Port'), NULL, TRUE);
    $this->addYesNo('smtpAuth', ts('Authentication?'), NULL, TRUE);
    $this->addElement('text', 'smtpUsername', ts('SMTP Username'));
    $this->addElement('password', 'smtpPassword', ts('SMTP Password'));

    $this->_testButtonName = $this->getButtonName('refresh', 'test');

    $this->add('submit', $this->_testButtonName, ts('Save & Send Test Email'));
    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $options = array(
      'Transactional Emails' => 1,
      'CiviMail Bulk Mailings' => 2
    );
    $this->addCheckBox('used_for', ts('Used For?'), $options,
      NULL, NULL, NULL, NULL,
      array('&nbsp;&nbsp;', '&nbsp;&nbsp;', '<br/>') 
    );
    
    $element = $this->add('text', 'mandril_post_url', ts('Mandrill Post to URL'));
    $element->freeze();
    
    // add select for groups
    $this->add('select', 'group_id', ts('Group to notify'), array('' => ts('- any group -')) + CRM_Core_PseudoConstant::group());
    $this->addFormRule(array('CRM_Mte_Form_MandrillSmtpSetting', 'formRule'));
    parent::buildQuickForm();
  }
  
  /**
   * global validation rules for the form
   *
   * @param   array  $fields   posted values of the form
   *
   * @return  array  list of errors to be posted back to the form
   * @static
   * @access  public
   */
  static function formRule($fields) {
    if (!empty($fields['smtpAuth'])) {
      if (empty($fields['smtpUsername'])) {
        $errors['smtpUsername'] = 'If your SMTP server requires authentication please provide a valid user name.';
      }
      if (empty($fields['smtpPassword'])) {
        $errors['smtpPassword'] = 'If your SMTP server requires authentication, please provide a password.';
      }
    }
    return empty($errors) ? TRUE : $errors;
  }
  
  /**
   * Process the form submission.
   */
  function postProcess() {
    $formValues = $this->exportValues();
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_testButtonName) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
      list($toDisplayName, $toEmail, $toDoNotEmail) = CRM_Contact_BAO_Contact::getContactDetails($userID);
      
      //get the default domain email address.CRM-4250
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();
      
      if (!$domainEmailAddress || $domainEmailAddress == 'info@EXAMPLE.ORG') {
        $fixUrl = CRM_Utils_System::url("civicrm/admin/domain", 'action=update&reset=1');
        CRM_Core_Error::fatal(ts('The site administrator needs to enter a valid \'FROM Email Address\' in <a href="%1">Administer CiviCRM &raquo; Communications &raquo; FROM Email Addresses</a>. The email address used may need to be a valid mail account with your email service provider.', array(1 => $fixUrl)));
      }
      
      if (!$toEmail) {
        CRM_Core_Error::statusBounce(ts('Cannot send a test email because your user record does not have a valid email address.'));
      }
      
      if (!trim($toDisplayName)) {
        $toDisplayName = $toEmail;
      }
      
      $testMailStatusMsg = ts('Sending test email. FROM: %1 TO: %2.<br />', array(1 => $domainEmailAddress, 2 => $toEmail));
      
      $params = array();
      $message = "SMTP settings are correct.";
      
      $params['host'] = $formValues['smtpServer'];
      $params['port'] = $formValues['smtpPort'];
      
      if ($formValues['smtpAuth']) {
        $params['username'] = $formValues['smtpUsername'];
        $params['password'] = $formValues['smtpPassword'];
        $params['auth']     = TRUE;
      }
      else {
        $params['auth'] = FALSE;
      }
      
      // set the localhost value, CRM-3153, CRM-9332
      $params['localhost'] = $_SERVER['SERVER_NAME'];
      
      // also set the timeout value, lets set it to 30 seconds
      // CRM-7510, CRM-9332
      $params['timeout'] = 30;
      
      $mailerName = 'smtp';
      
      $headers = array(
        'From' => '"' . $domainEmailName . '" <' . $domainEmailAddress . '>',
        'To' => '"' . $toDisplayName . '"' . "<$toEmail>",
        'Subject' => "Test for SMTP settings",
      );
      
      $mailer = Mail::factory($mailerName, $params);
      $config = CRM_Core_Config::singleton();
      if (property_exists($config, 'civiVersion')) {
        $civiVersion = $config->civiVersion;
      }
      else {
        $civiVersion = CRM_Core_BAO_Domain::version();
      }
      if (version_compare('4.5alpha1', $civiVersion) > 0) {
        CRM_Core_Error::ignoreException();
      }
      else {
        $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
      }
      $result = $mailer->send($toEmail, $headers, $message);
      
      if (version_compare('4.5alpha1', $civiVersion) > 0) {
        CRM_Core_Error::setCallback();
      }
      else {
        unset($errorScope);
      }
      if (!is_a($result, 'PEAR_Error')) {
        CRM_Core_Session::setStatus($testMailStatusMsg . ts('Your %1 settings are correct. A test email has been sent to your email address.', array(1 => strtoupper($mailerName))), ts("Mail Sent"), "success");
      }
      else {
        $message = CRM_Utils_Mail::errorMessage($mailer, $result);
        CRM_Core_Session::setStatus($testMailStatusMsg . ts('Oops. Your %1 settings are incorrect. No test mail has been sent.', array(1 => strtoupper($mailerName))) . $message, ts("Mail Not Sent"), "error");
      }
    }
        
    // if password is present, encrypt it
    if (!empty($formValues['smtpPassword'])) {
      $formValues['smtpPassword'] = CRM_Utils_Crypt::encrypt($formValues['smtpPassword']);
    }
    CRM_Core_BAO_Setting::setItem($formValues,
      CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mandrill_smtp_settings'
    );
  }

  /**
   * This function sets the default values for the form.
   * default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    if (!$this->_defaults) {
      $this->_defaults = array();

      $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mandrill_smtp_settings'
      );
      if (!empty($mailingBackend)) {
        $this->_defaults = $mailingBackend;
        
        if (!empty($this->_defaults['smtpPassword'])) {
          $this->_defaults['smtpPassword'] = CRM_Utils_Crypt::decrypt($this->_defaults['smtpPassword']);
        }
      }
      $mandrillSecret = CRM_Core_OptionGroup::values('mandrill_secret', TRUE);
      $this->_defaults['mandril_post_url'] = CRM_Utils_System::url('civicrm/ajax/mte/callback', 
        "mandrillSecret={$mandrillSecret['Secret Code']}", TRUE, NULL, FALSE, TRUE);
    }
    return $this->_defaults;
  }
}