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
  
  /*
   * Webtest to check Add and check Mandrill Settings
   *
   */
  public function testAddMandrillSettings() {
    $this->webtestLogin();
    $this->addMandrillSettings();    
  }
  
  /*
   * function to add/check Mandrill Settings
   *
   */
  public function addMandrillSettings() {
    $this->openCiviPage('mte/smtp', 'reset=1', '_qf_MandrillSmtpSetting_next');
    global $mandrillSettings;
    $mandrillSettings['url'] = $this->getValue('mandril_post_url');
    if ($this->isChecked('is_active')) {
      return;
    }
    $this->openCiviPage('admin/domain', 'reset=1&action=update', '_qf_Domain_next_view');
    $this->type('name', 'test');
    $this->type('email_name', 'test');
    $this->type('email_address', 'test@test.com');
    $this->clickLink('_qf_Domain_next_view');
    $this->openCiviPage('mte/smtp', 'reset=1', '_qf_MandrillSmtpSetting_next');
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
  
  /*
   * Webtest to catch Open and Clicks when a Individual
   * email is send
   *
   */  
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
    $header = $this->_checkActivity('Mandrill Email Sent', $email, $subject, $lname . ', ' . $fname);
    $this->postFakeResponses('open', $email, 'test@test.com', $subject, $header);
    $this->postFakeResponses('click', $email, 'test@test.com', $subject, $header);
    $this->_checkActivity('Mandrill Email Open', $email, $subject, $lname . ', ' . $fname, FALSE);
    $this->_checkActivity('Mandrill Email Click', $email, $subject, $lname . ', ' . $fname, FALSE);
    $this->checkReports('Mail Opened', $lname . ', ' . $fname, 'Opened', 1, $subject);
    $this->checkReports('Mail Bounces', $lname . ', ' . $fname, 'Bounce');
    $this->checkReports('Mail Clickthroughs', $lname . ', ' . $fname, 'Clicks', 1, $subject);
    $this->checkReports('Mailing Details', $lname . ', ' . $fname, 'Detail', 1, NULL, 'Successful');
  }
  
  /*
   * Webtest to Catch bounce for Contribution Receipt
   *
   */
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
    $this->clickLink('_qf_ThankYou_submit_savenext');
    
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
    $header = $this->_checkActivity('Mandrill Email Sent', $email, 'Invoice - ' . $pageTitle, $lname . ', ' . $fname);
    $this->postFakeResponses('hard_bounce', $email, 'test@test.com', 'Invoice - ' . $pageTitle, $header);
    $this->_checkActivity('Mandrill Email Bounce', $email, 'Invoice - ' . $pageTitle, $lname . ', ' . $fname, FALSE);
    $this->checkReports('Mail Opened', $lname . ', ' . $fname, 'Opened');
    $this->checkReports('Mail Bounces', $lname . ', ' . $fname, 'Bounce', 1, 'Invoice - ' . $pageTitle);
    $this->checkReports('Mail Clickthroughs', $lname . ', ' . $fname, 'Clicks');
    $this->checkReports('Mailing Details', $lname . ', ' . $fname, 'Detail', 1, NULL, 'Bounced');
    $this->openCiviPage('contact/search', 'reset=1', 'sort_name');
    $this->type('sort_name', $email);
    $this->clickLink('_qf_Basic_refresh');
    $this->clickLink("xpath=id('Basic')//table//tbody/tr[1]/td[11]/span[1]/a[text()='View']");
    $this->assertTrue($this->isTextPresent('(On Hold)'));
  }
   
  /*
   * Webtest to catch Open and Bounce for Bulk Mailing
   *
   */
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
    $this->openCiviPage("mailing/queue", "reset=1");
    $this->clickLink("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");
    $this->verifyText("xpath=//table//tr[td/a[text()='Successful Deliveries']]/descendant::td[2]", preg_quote("4 (100.00%)"));
    $mailingId = $this->urlArg('mid');
    $header = $this->_checkActivity('Mandrill Email Sent', $contacts[0][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[0][0], TRUE, $mailingId);
    $this->postFakeResponses('open', $contacts[0][1], 'test@test.com', "Test subject {$mailingName} for Webtest", $header);
    $header = $this->_checkActivity('Mandrill Email Sent', $contacts[1][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[1][0], TRUE, $mailingId);
    $this->postFakeResponses('open', $contacts[1][1], 'test@test.com', "Test subject {$mailingName} for Webtest", $header);
    $header = $this->_checkActivity('Mandrill Email Sent', $contacts[2][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[2][0], TRUE, $mailingId);
    $this->postFakeResponses('hard_bounce', $contacts[2][1], 'test@test.com', "Test subject {$mailingName} for Webtest", $header);
    $header = $this->_checkActivity('Mandrill Email Sent', $contacts[3][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[3][0], TRUE, $mailingId);
    $this->postFakeResponses('hard_bounce', $contacts[3][1], 'test@test.com', "Test subject {$mailingName} for Webtest", $header);
    $this->_checkActivity('Mandrill Email Open', $contacts[0][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[0][0], FALSE);
    $this->_checkActivity('Mandrill Email Open', $contacts[1][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[1][0], FALSE);
    $this->_checkActivity('Mandrill Email Bounce', $contacts[2][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[2][0], FALSE);
    $this->_checkActivity('Mandrill Email Bounce', $contacts[3][1], "Test subject {$mailingName} for Webtest", $lname . ', ' . $contacts[3][0], FALSE);
    $this->openCiviPage("mailing/browse/scheduled", "reset=1&scheduled=true");
    $this->clickLink("xpath=//table//tbody/tr[td[1]/text()='Mailing $mailingName Webtest']/descendant::a[text()='Report']");
    $this->verifyText("xpath=//table//tr[td/a[text()='Intended Recipients']]/descendant::td[2]", preg_quote("4"));
    $this->verifyText("xpath=//table//tr[td/a[text()='Successful Deliveries']]/descendant::td[2]", preg_quote("2 (50.00%)"));
    $this->verifyText("xpath=//table//tr[td/a[text()='Tracked Opens']]/descendant::td[2]", preg_quote("2"));
    $this->verifyText("xpath=//table//tr[td/a[text()='Bounces']]/descendant::td[2]", preg_quote("2 (50.00%)"));
  }
  
  /*
   * Webtest to catch opens when email is being send 
   * from mandrill app
   *
   */
  public function testNonCiviMails() {
    $this->webtestLogin();
    $this->addMandrillSettings();    
    $email = 'test' . substr(sha1(rand()), 0, 7) . '@mandrilluser.com';
    $this->postFakeResponses('open', $email, 'test@test-email.com', 'General Email', NULL);
    $this->_checkActivity('Mandrill Email Sent', $email, 'Email sent from Mandrill App: General Email', $email, FALSE);
    $this->_checkActivity('Mandrill Email Open', $email, 'General Email', $email, FALSE);
    
    
    $email = 'test' . substr(sha1(rand()), 0, 7) . '@mandrilluser.com';
    $this->postFakeResponses('hard_bounce', $email, 'test@test-email.com', 'General Email', NULL);
    $this->_checkActivity('Mandrill Email Sent', $email, 'Email sent from Mandrill App: General Email', $email, FALSE);
    $this->_checkActivity('Mandrill Email Bounce', $email, 'General Email', $email, FALSE);
    $this->openCiviPage('contact/search', 'reset=1', 'sort_name');
    $this->type('sort_name', $email);
    $this->clickLink('_qf_Basic_refresh');
    $this->clickLink("xpath=id('Basic')//table//tbody/tr[1]/td[11]/span[1]/a[text()='View']");
    $this->assertTrue($this->isTextPresent('(On Hold)'));
  }
  
  /**
   * Helper function for Check contents in Activity.
   * @param $atype
   * @param $contactName
   *
   *  return string
   */
  public function _checkActivity($atype, $contactName, $subject, $withContact, $isHeader = TRUE, $mailingId = NULL) {
    $this->openCiviPage('activity/search', 'reset=1', '_qf_Search_refresh');
    $this->select('activity_type_id', "label=$atype");
    $this->type('sort_name', $contactName);
    $this->clickLink('_qf_Search_refresh', 'Search');

    // View your Activity
    $this->clickLink("xpath=id('Search')/div[3]/div/div[2]/table/tbody/tr[2]/td[9]/span/a[text()='View']", "_qf_Activity_cancel", FALSE);
    $this->assertTrue($this->isTextPresent($atype));
     $expected = array(
      4 => $subject,
      8 => 'Completed',
      2 =>  $withContact,
      10 => 'Urgent',
    );
    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=id('Activity')/div[2]/table[1]/tbody/tr[$label]/td[2]", preg_quote($value));
    }
    if (!$isHeader) {
      return FALSE;
    }
    $header = $this->urlArg('id', $this->getAttribute("xpath=id('Search')/div[3]/div/div[2]/table/tbody/tr[2]/td[9]/span/a[text()='View']@href"));
    if ($mailingId) {
      $cid = $this->urlArg('cid', $this->getAttribute("xpath=id('Search')/div[3]/div/div[2]/table/tbody/tr[2]/td[9]/span/a[text()='View']@href"));
      $queueId = CRM_Core_DAO::singleValueQuery("SELECT ce.id FROM `civicrm_mailing_event_queue` ce
INNER JOIN civicrm_mailing_job mj ON mj.id = ce.job_id and ce.contact_id = $cid AND mj.mailing_id = $mailingId");
    }
    else {
      $queueId = CRM_Core_DAO::singleValueQuery('SELECT mailing_queue_id FROM civicrm_mandrill_activity WHERE activity_id = ' . $header);
    }
    if ($queueId) {
      $queue = new CRM_Mailing_Event_BAO_Queue();
      $queue->id = $queueId;
      if ($queue->find(TRUE)) {
        $header = implode(CRM_Core_Config::singleton()->verpSeparator, array($header, 'm', $queue->job_id, $queue->id, $queue->hash));
      }
    }
    return $header;
  }

  /*
   * function to create fake post of mandrill
   *
   * @param string $method    -- method name like Hard Bounce, Open etc
   * @param string $email     -- Email Address
   * @param string $fromEmail -- From Email Address
   * @param string $subject   -- Message Subject 
   * @param string $header    -- Mandrill Header
   *
   */
  public function postFakeResponses($method, $email, $fromEmail, $subject, $header) {
    $post[0] = array(
      'event' => $method,
      'ts' =>  strtotime(date('YmdHis')),
      'msg' => array(
        'metadata' => array('CiviCRM_Mandrill_id' => $header),
        'email' => $email,
        'sender' => $fromEmail,
        'subject' => $subject,
      ),
    );
    switch ($method) {
      case 'click':
        $post[0]['url'] = 'http://civicrm.org';
        break;
      case 'hard_bounce':
        $post[0]['msg']['bounce_description'] = 'Not valid Url';
        break;
    }
    
    global $mandrillSettings;
    $url = $mandrillSettings['url'];
    $post = 'mandrill_events=' . json_encode($post);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
  }

  /*
   * Function to check reports after fake mandill post
   * to see if Bounces, Opens, Clicks etc are tracked
   *
   * @param string  $reportName    -- Mailing Report Name
   * @param string  $contactName   -- Contact Name
   * @param string  $name          -- Button Name for report
   * @param integer $count         -- Count of expected result
   * @param string  $subject       -- Subject of message
   * @param integer  $mailingDetail -- Mailing Details
   *
   */
  public function checkReports($reportName, $contactName, $name, $count = 0, $subject = NULL, $mailingDetail = NULL) {
    // Open report list
    $this->openCiviPage('report/list', 'reset=1');    
    // Visit report
    $this->clickLink("xpath=//div[@id='Mail']//table/tbody//tr/td/a/strong[text() = '$reportName']");
    $this->click("xpath=//div[@id='mainTabContainer']/ul/li[3]/a");
    $this->waitForElementPresent('sort_name_value');
    $this->type('sort_name_value', $contactName);
    $this->clickLink("xpath=//div[@id='mainTabContainer']/ul/li[1]/a", 'fields[mailing_name]', FALSE);
    if (!$this->isChecked('fields[mailing_name]')) {
      $this->click('fields[mailing_name]');
    }
    if ($name == 'Bounce' && !$this->isChecked('fields[bounce_reason]')) {
      $this->click('fields[bounce_reason]');      
    }
    // click preview
    $this->clickLink("_qf_{$name}_submit");
    if ($count) {
      $this->assertElementContainsText("xpath=id('$name')/div[3]/table[3]/tbody/tr[1]/td[1]", "$count", 'Count does not match.');
      switch ($name) {
        case 'Bounce':
          $this->assertTrue($this->isTextPresent('Not valid Url'));
          break;
        case 'Clicks':
          $this->assertTrue($this->isTextPresent('http://civicrm.org'));
          break;
      }
      if ($subject) {
        $this->clickLink("xpath=id('$name')/div[3]/table[2]/tbody/tr[1]/td[2]/a");
        $this->assertTrue($this->isTextPresent('Mandrill Email Sent'));
        $this->assertTrue($this->isTextPresent($subject));
      }
      if ($mailingDetail) {
        $this->assertTrue($this->isTextPresent($mailingDetail));
      }
    }
    else {
      $this->assertTrue($this->isTextPresent('None found.'));
    }
  }
}