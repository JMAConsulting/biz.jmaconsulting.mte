biz.jmaconsulting.mte version 2.0 for CiviCRM 4.2, 4.3, 4.4, 4.5, 4.6
=====================================================================

Mandrill Emails Extension for CiviCRM

See https://github.com/JMAConsulting/biz.jmaconsulting.mte/wiki/About-Mandrill-Emails-Extension-for-CiviCRM

Installation instructions for Mandrill Emails Extension
=======================================================

* Setup Mandrill
  * Register for or login to your Mandrill account at https://mandrillapp.com/login/
  * Create API key as follows:
  * Click on Settings (Gear icon top-right) >> SMTP & API Credentials.
  * Click +New API Key
  * Note SMTP Credentials, or leave window tab open for a few steps.
* Setup Extensions Directory 
  * If you have not already done so, go to Administer >> System Settings >> Directories
    * Set an appropriate value for CiviCRM Extensions Directory. For example, for Drupal, /path/to/drupalroot/sites/all/modules/Extensions/
    * In a different window, ensure the directory exists and is readable by your web server process.
  * Click Save.
* Setup Extensions Resources URL
  * If you have not already done so, go to Administer >> System Settings >> Resource URLs
    * Beside Extension Resource URL, enter an appropriate values such as http://yourorg.org/sites/all/modules/Extensions/
  * Click Save.
* Install Mandrill Email extension
  * Go to Administer >> Customize Data and Screens >> Manage Extensions.
  * Click on Add tab.
  * If you do not see Mandrill Emails in the list of extensions, download it and unzip it into the extensions direction setup above, then return to this page.
  * Beside Mandrill Emails, click Download.
  * Review the information, then click Download and Install.
* Setup From Email address
  * If you have not already done so, go to:
  * Administer >> Communications >> From Email Addresses.
  * Enter a from address you would like to display to users for CiviCRM emails, for example, info@yourorg.org.
* Setup Outbound Email
  * Administer >> System Settings >> Mandrill Smtp Settings.
  * Enter the following:
    * SMTP Server: smtp.mandrillapp.com
    * SMTP Port: 587
    * Authentication: Yes
    * SMTP Username: (from first step above, e.g. mail@yourorg.org)
    * SMTP Password: (copy and paste API Key from first step above, e.g. 12345678-abcd-1234-efgh-123456789012)
    * Choose one or both of Transactional Emails and/or CiviCRM Bulk Mailings
    * Ensure Enabled? is enabled
    * Optional (not recommended): if you want a group of contacts to receive emails when a hard or soft bounce is received, select it as the Group to notify.
  * Click Save & Send Test Email
  * Note the value of the field Mandrill Post URL, for example by copying and pasting it into a file for use later in this setup.
  * Check that you received the test email.
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
  * In Post to URL, enter the resource URL that you saved in the previous step above. For example: http://yourorg.org/civicrm/ajax/mte/callback?mandrillSecret=108d3af8a77fc502 
* Test by doing an action in CiviCRM that sends out a non-bulk email.
* This should not be necessary, but if you would like to change the secret code that is used to ensure secure communication from Mandrill to your instance of CiviCRM, you can change the secret as follows:
  * Go to Administer > System Settings > Option Groups.
  * Beside Mandrill Secret, click on Options.
  * Click edit beside Secret Code.
  * Change the value, then click Save.
  * Navigate to Administer >> System Settings >> Mandrill Smtp Settings, click on SMTP, copy the value of Mandrill Post URL. Note that this step is necessary since it also activates the use of this callback URL.
  * Use that new value to configure the Post to URL value of the webhook for Mandrill.

Disable Instructions
====================
Disabling the extension turns off its functionality but does not remove the software, and the database tables and data created by the extension.
* Go to Administer >> System Settings >> Manage Extensions (or for CiviCRM versions <=4.4, Administer >> Customize Data and Screens >> Manage Extensions).
* Beside Mandrill Emails on the Extensions tab, click Disable.
* On the confirmation page, click Disable.

Disable and Uninstall Instructions
======================
Uninstalling the extension removes the changes it made to your database and the data that was inserted into your database to track emails sent via the extension to Mandrill. 
* Disable the extension following the instructions above.
* On the Administer >> System Settings >> Manage Extensions (or for CiviCRM versions <=4.4, Administer >> Customize Data and Screens >> Manage Extensions), Extensions tab, beside Mandrill Emails extension, click Disable, and on the confirmation page, click Disable. This removes the functionality from the UI and stops email from being delivered via Mandrill, and for any callbacks from Mandrill for previously sent mail to be recorded.
* To continue to send mail from CiviCRM that was previously sent via Mandrill Emails, ensure that your settings are correct at Administer >> System Settings > Outbound Email (SMTP/Sendmail).
* If desired, on the Extensions tab beside Mandrill Emails, click Uninstall. This destroys database tables created by the Mandrill Emails extension when it was installed, and removes the software for the extension from the server.
