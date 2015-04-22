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
    
  function testSendContributionEmail() {
    $this->webtestLogin();
    $this->addMandrillSettings();
    $fname = 'Anthony' . substr(sha1(rand()), 0, 7);
    $lname = 'Anderson';
    $email = $fname . $lname . '@test.com';
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(10, 50);
    $hash = substr(sha1(rand()), 0, 7);

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      NULL,
      TRUE,
      TRUE,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      1,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      FALSE,
      FALSE                                                
    );
    $this->openCiviPage('admin/contribute/thankyou', "reset=1&action=update&id=$pageId", '_qf_ThankYou_next');
    $this->click('is_email_receipt');
    $this->type('receipt_from_name', 'Test Email');
    $this->type('receipt_from_email', 'test@test.com');
    $this->click('_qf_ThankYou_submit_savenext');
    
    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&cid=0", "_qf_Main_upload-bottom");
    $this->type("email-5", $email);
    $this->type("first_name", $fname);
    $this->type("last_name", $lname);$streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->_checkActivity('Mandrill Email Sent', $email, 'Invoice - ' . $pageTitle, $lname . ', ' . $fname);
    // FIXME: Add code to check Mandrill callbacks
  }
   
  function testSendBulkEmail() {
    $this->webtestLogin();
    $this->addMandrillSettings();
    //----do create test mailing group
    $this->openCiviPage("group/add", "reset=1", "_qf_Edit_upload");

    // make group name
    $groupName = 'group_' . substr(sha1(rand()), 0, 7);

    // fill group name
    $this->type("title", $groupName);

    // fill description
    $this->type("description", "New mailing group for Webtest");

    // enable Mailing List
    $this->click("group_type[2]");

    // select Visibility as Public Pages
    $this->select("visibility", "value=Public Pages");

    // Clicking save.
    $this->clickLink("_qf_Edit_upload");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "The Group '$groupName' has been saved.");

    //---- create mailing contact and add to mailing Group
    $lname = 'Anderson';
    $contacts = array();
    for ($i = 0; $i < 4; $i++) {
      $fname = 'Anthony' . substr(sha1(rand()), 0, 7);
      $email = $fname . $lname . '@test.com';
      $this->webtestAddContact($fname, $lname, $email);

      // Get contact id from url.
      $contactId = $this->urlArg('cid');

      // go to group tab and add to mailing group
      $this->click("css=li#tab_group a");
      $this->waitForElementPresent("_qf_GroupContact_next");
      $this->select("group_id", "$groupName");
      $this->clickLink("_qf_GroupContact_next", "_qf_GroupContact_next", FALSE);
      $contacts[] = array($fname, $email);
    }
    // configure default mail-box
    $this->setupDefaultMailbox();

    $this->openCiviPage("a/#/mailing/new");

    //-------select recipients----------
    $tokens = ' {domain.address}{action.optOutUrl}';

    // fill mailing name
    $mailingName = substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent("xpath=//input[@name='mailingName']");
    $this->type("xpath=//input[@name='mailingName']", "Mailing $mailingName Webtest");

    // Add the test mailing group
    $this->select2("s2id_crmUiId_8", $groupName, TRUE);

    // do check count for Recipient
    $this->waitForTextPresent("~4 recipient");

    // fill subject for mailing
    $this->type("xpath=//input[@name='subject']", "Test subject {$mailingName} for Webtest");

    // HTML format message
    $HTMLMessage = "This is HTML formatted content for Mailing {$mailingName} Webtest.";
    $this->fillRichTextField("crmUiId_1", $HTMLMessage . $tokens); 
    $this->click("xpath=//div[@class='crm-wizard-buttons']/button[text()='Next']");    
    $this->waitForTextPresent("Mailing $mailingName Webtest");
    $this->assertChecked("xpath=//input[@id='schedule-send-now']");
    // click next with nominal content
    $this->click("xpath=//center/a/div[text()='Submit Mailing']");
    $this->waitForTextPresent("Find Mailings");
    // directly send schedule mailing -- not working right now
    $this->openCiviPage("mailing/queue", "reset=1");// verify successful deliveries
    $this->clickLink("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");
    $this->verifyText("xpath=//table//tr[td/a[text()='Successful Deliveries']]/descendant::td[2]", preg_quote("4 (100.00%)"));
    $this->_checkActivity('Mandrill Email Sent', $contacts[0][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[0][0]);
    // FIXME: Add code to check Mandrill callbacks    
  }
  
  /**
   * Helper function for Check contents in Activity.
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