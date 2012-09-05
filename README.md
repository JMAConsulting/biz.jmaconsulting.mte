biz.jmaconsulting.mte
=====================

Mandrill Transactional Emails Extension for CiviCRM

See https://github.com/JMAConsulting/biz.jmaconsulting.mte/wiki/About-mte-%28Mandrill-Transactional-Emails%29

Installation:

Installation instructions for Mandrill Transactional Emails for CiviCRM 4.2+
============================================================================

* Setup Mandrill
  * Register for or login to your Mandrill account at https://mandrillapp.com/login/
  * Create API key as follows:
  * Click on Settings (Gear icon top-right) >> SMTP & API Credentials.
  * Click +New API Key
  * Note SMTP Credentials, or leave window tab open for a few steps.
* Setup From Email address
2.1. If you have not already done so, go to:
2.2. Administer >> Communications >> From Email Addresses.
2.3. Enter a from address you would like to display to users for CiviCRM emails, for example, info@yourorg.org.
3. Setup Outbound Email
3.1. Administer >> System Settings >> Outbound Email (SMTP/Sendmail).
3.2. Click on SMTP.
3.3. Enter the following:
3.3.1. SMTP Server: smtp.mandrillapp.com
3.3.2. SMTP Port: 587
3.3.3. Authentication: Yes
3.3.4. SMTP Username: (from step 1 above, e.g. mail@yourorg.org)
3.3.5. SMTP Password: (copy and paste API Key from step 1 above, e.g. 12345678-abcd-1234-efgh-123456789012)
3.4. Click Save & Send Test Email
3.5. Check that you received the test email.
4. Setup Extensions Directory 
4.1. If you have not already done so, go to Administer >> System Settings >> Directories
4.1.1. Set an appropriate value for CiviCRM Extensions Directory. For example, for Drupal, /path/to/drupalroot/sites/all/modules/Extensions/
4.1.2. In a different window, ensure the directory exists and is readable by your web server process.
4.2.1. If the Custom PHP Path Directory is blank, set it to the same value as CiviCRM Extensions Directory from 4.1.1 followed by biz.jmaconsulting.mte/, for example, /path/to/drupalroot/sites/all/modules/Extensions/bi.jmaconsulting.mte/ 
4.2.2. If there is a path already entered in the Custom PHP Path Directory, copy Extensions/biz.jmaconsulting.mte/CRM/Utils/Hook.php to custom_php/CRM/Utils/Hook.php
4.2.3. This 4.2 requirement is temporary and will be removed in future versions after a change to CiviCRM core code.
4.3. Click Save.
5. Setup Extensions Resources URL
5.1. If you have not already done so, go to Administer >> System Settings >> Resource URLs
5.1.1. Beside Extension Resource URL, enter an appropriate values such as http://yourorg.org/sites/all/modules/Extensions/
5.2. Click Save.
6. Install Mandrill Transactional Emails extension
6.1. Go to Administer >> Customize Data and Screens >> Manage Extensions.
6.2. If you do not see Mandrill Transactional Emails in the list of extensions, download it and unzip it into the extensions direction setup in 4.1.1 above, then return to this page.
6.3. Beside Mandrill Transactional Emails, click Install.
6.4. Review the information, then click Install.
7. Create user account that will callback from Mandrill to CiviCRM
7.1. Create a user in your CMS (for example Drupal) that has the *access civicrm* permission.
8. Copy value of site key
8.1. Open civicrm.settings.php (in Drupal this file is generally located at /path/to/docroot/sites/default/civicrm.settings.php) in an editor.
8.2. Copy the value of CIVICRM_SITE_KEY excluding the single quotes to a convenient place to be used in the next step.
9. Configure Webhooks for Mandrill
9.1. To complete this step successfully, your website must be accessible from the Internet so that Mandrill can post to it. So if you are running a test site locally on your laptop that other computers cannot access, this step will fail.
9.2. Login or go to your Mandrill account at https://mandrillapp.com/login/
9.3. Click on Settings (ie the Gear icon on the top-right) >> Webhooks
9.4. Click on +Add a Webhook
9.5. Click to enable all entries _except_ Message Is Sent, including:
9.5.1. Message Is Soft-bounced
9.5.2. Message Is Clicked
9.5.3. Message Recipient Unsubscribe
9.5.4. Message Is Bounced
9.5.5. Message Is Opened
9.5.6. Message Is Marked As Spam
9.5.7. Message Is Rejected
9.6. In Post to URL, enter the resource URL from 5 above, followed by biz.jmaconsulting.mte/CRM/Mte/Page/callback.php. For example: http://yourorg.org/civicrm/mte/callback?name=username&pass=password&key=civicrm-site-key where username and password are from step 7 above and the civicrm-site-key is from step 8 above.
10. Test by doing an action in CiviCRM that sends out a non-bulk email.

