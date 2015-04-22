<?php

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * FIXME
 */
class WebTest_Mte_MteTest extends CiviSeleniumTestCase {
  function setUp() {
    parent::setUp();
    global $mandrillSettings;
    $mandrillSettings = array(
      'username' => 'pradeep.nayak@jmaconsulting.biz',
      'password' => '42bAi-rn5IyYRkRupiw-CQ',      
    );
  }
  
  public function testAddMandrillSettings() {
    $this->webtestLogin();
    $this->addMandrillSettings();    
  }

  public function addMandrillSettings() {
    $this->openCiviPage('mte/smtp', 'reset=1', '_qf_MandrillSmtpSetting_next');
    if ($this->isChecked('is_active')) {
      return;
    }
    global $mandrillSettings;
    $this->type('smtpServer', 'smtp.mandrillapp.com');
    $this->type('smtpPort', '587');
    $this->type('smtpUsername', $mandrillSettings['username']);
    $this->type('smtpPassword', $mandrillSettings['password']);
    $this->click('used_for[1]');
    $this->click('used_for[2]');
    $this->click('is_active');
    $this->click('smtpAuth', 1);
    $this->clickLink('_qf_MandrillSmtpSetting_refresh_test', '_qf_MandrillSmtpSetting_refresh_test');
    $this->assertElementContainsText('css=.notify-content', 'Your SMTP settings are correct. A test email has been sent to your email address.');
  }
  
  function testSendIndividualEmail() {
    $this->webtestLogin();
    $this->addMandrillSettings();
    $fname = 'Anthony' . substr(sha1(rand()), 0, 7);
    $lname = 'Anderson';
    $email = $fname . $lname . '@test.com';
    $this->webtestAddContact($fname, $lname, $email);
    // Go for Ckeck Your Editor, Click on Send Mail
    $this->click("//a[@id='crm-contact-actions-link']/span");
    $this->clickLink('link=Send an Email', 'subject', FALSE);
    
    $this->click('subject');
    $subject = 'Subject_' . substr(sha1(rand()), 0, 7);
    $this->type('subject', $subject);
    // Is signature correct? in Editor
    $this->fillRichTextField('html_message');
    $this->click('_qf_Email_upload-top');
    $this->waitForElementPresent("//a[@id='crm-contact-actions-link']/span");
    $this->_checkActivity('Mandrill Email Sent', $email, $subject, $lname . ', ' . $fname);
    // FIXME: Add code to check Mandrill callbacks
  }
  
  
  /**
   * Helper function for Check Signature in Activity.
   * @param $atype
   * @param $contactName
   */
  public function _checkActivity($atype, $contactName, $subject, $withContact) {
    $this->openCiviPage('activity/search', 'reset=1', '_qf_Search_refresh');
    $this->select('activity_type_id', "label=$atype");
    $this->type('sort_name', $contactName);
    $this->clickLink('_qf_Search_refresh', 'Search');

    // View your Activity
    $this->clickLink("xpath=id('Search')/div[3]/div/div[2]/table/tbody/tr[2]/td[9]/span/a[text()='View']", "_qf_Activity_cancel", FALSE);
     $expected = array(
      4 => $subject,
      8 => 'Completed',
      2 =>  $withContact,
      10 => 'Urgent',
    );
    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=id('Activity')/div[2]/table[1]/tbody/tr[$label]/td[2]", preg_quote($value));
    }
  }
}