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
}