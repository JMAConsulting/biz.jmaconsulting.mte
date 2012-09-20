biz.jmaconsulting.mte version 1.1 for CiviCRM 4.2.1
===================================================

Mandrill Transactional Emails

See https://github.com/JMAConsulting/biz.jmaconsulting.mte/wiki/About-mte-%28Mandrill-Transactional-Emails%29

Installation instructions for Mandrill Transactional Emails
===========================================================

* Setup Mandrill
  * Register for or login to your Mandrill account at https://mandrillapp.com/login/
  * Create API key as follows:
  * Click on Settings (Gear icon top-right) >> SMTP & API Credentials.
  * Click +New API Key
  * Note SMTP Credentials, or leave window tab open for a few steps.
* Setup From Email address
  * If you have not already done so, go to:
  * Administer >> Communications >> From Email Addresses.
  * Enter a from address you would like to display to users for CiviCRM emails, for example, info@yourorg.org.
* Setup Outbound Email
  * Administer >> System Settings >> Outbound Email (SMTP/Sendmail).
  * Click on SMTP.
  * Enter the following:
    * SMTP Server: smtp.mandrillapp.com
    * SMTP Port: 587
    * Authentication: Yes
    * SMTP Username: (from step 1 above, e.g. mail@yourorg.org)
    * SMTP Password: (copy and paste API Key from step 1 above, e.g. 12345678-abcd-1234-efgh-123456789012)
  * Click Save & Send Test Email
  * Check that you received the test email.
* Setup Extensions Directory 
  * If you have not already done so, go to Administer >> System Settings >> Directories
    * Set an appropriate value for CiviCRM Extensions Directory. For example, for Drupal, /path/to/drupalroot/sites/all/modules/Extensions/
    * In a different window, ensure the directory exists and is readable by your web server process.
  * Click Save.
* Setup Extensions Resources URL
  * If you have not already done so, go to Administer >> System Settings >> Resource URLs
    * Beside Extension Resource URL, enter an appropriate values such as http://yourorg.org/sites/all/modules/Extensions/
  * Click Save.
* Install Mandrill Transactional Emails extension
  * Go to Administer >> Customize Data and Screens >> Manage Extensions.
  * If you do not see Mandrill Transactional Emails in the list of extensions, download it and unzip it into the extensions direction setup in 4.1.1 above, then return to this page.
  * Beside Mandrill Transactional Emails, click Install.
  * Review the information, then click Install.
* Create user account that will callback from Mandrill to CiviCRM
  * Create a user in your CMS (for example Drupal) that has the *access civicrm* permission.
* Copy value of site key
  * Open civicrm.settings.php (in Drupal this file is generally located at /path/to/docroot/sites/default/civicrm.settings.php) in an editor.
  * Copy the value of CIVICRM_SITE_KEY excluding the single quotes to a convenient place to be used in the next step.
* Configure Webhooks for Mandrill
  * To complete this step successfully, your website must be accessible from the Internet so that Mandrill can post to it. So if you are running a test site locally on your laptop that other computers cannot access, this step will fail.
  * Login or go to your Mandrill account at https://mandrillapp.com/login/
  * Click on Settings (ie the Gear icon on the top-right) >> Webhooks
  * Click on +Add a Webhook
  * Click to enable all entries _except_ Message Is Sent, including
    * Message Is Soft-bounced
    * Message Is Clicked
    * Message Recipient Unsubscribe
    * Message Is Bounced
    * Message Is Opened
    * Message Is Marked As Spam
    * Message Is Rejected
  * In Post to URL, enter the resource URL from 5 above, followed by biz.jmaconsulting.mte/CRM/Mte/Page/callback.php. For example: http://yourorg.org/civicrm/mte/callback?name=username&pass=password&key=civicrm-site-key where username and password are from step 7 above and the civicrm-site-key is from step 8 above.
* Test by doing an action in CiviCRM that sends out a non-bulk email.

