{*
 +--------------------------------------------------------------------+
 | Mandrill Transactional Email extension			      |
 | integrates CiviCRM's non-bulk email with the Mandrill service      |
 +--------------------------------------------------------------------+
 | Copyright (C) 2012-2015 JMA Consulting                             |
 +--------------------------------------------------------------------+
 | This program is free software: you can redistribute it and/or      | 
 | modify  it under the terms of the GNU Affero General Public        |
 | License as published by the Free Software Foundation, either       |
 | version 3 of the   License, or (at your option) any later version. |
 |                                                                    |
 | This program is distributed in the hope that it will be useful,    |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of     |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
 | GNU Affero General Public License for more details.                |
 |                                                                    | 
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program.  If not,                          |
 | see <http://www.gnu.org/licenses/>.                                |
 |                            					      |
 | Support:https://github.com/JMAConsulting/biz.jmaconsulting.mte/issues      
 |       						              |
 | Contact: info@jmaconsulting.biz      			      |
 |         JMA Consulting      					      |
 |          215 Spadina Ave, Ste 400      			      |
 |          Toronto, ON        	 				      |
 |          Canada   M5T 2C7       				      |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-smtp-form-block">
<div id="help">
There are two types of emails sent from CiviCRM: bulk emails sent through the CiviMail component, and transactional emails that covers everything else. The Mandrill Email extension can be used to send  email for either or both of these. Some other extensions only look after the delivery of CiviMail emails.
</div>
<div id="bySMTP" class="mailoption">
  <fieldset>
    <legend>{ts}SMTP Configuration{/ts}</legend>
    <table class="form-layout-compressed">
      <tr class="crm-smtp-form-block-smtpServer">
        <td class="label">{$form.smtpServer.label}</td>
        <td>{$form.smtpServer.html}<br  />
        <span class="description">{ts}Enter the SMTP server (machine) name. EXAMPLE: smtp.example.com{/ts}</span>
        </td>
       </tr>
       <tr class="crm-smtp-form-block-smtpPort">
         <td class="label">{$form.smtpPort.label}</td>
         <td>{$form.smtpPort.html}<br />
         <span class="description">{ts}The standard SMTP port is 25. You should only change that value if your SMTP server is running on a non-standard port.{/ts}</span>
        </td>
       </tr>
       <tr class="crm-smtp-form-block-smtpAuth">
         <td class="label">{$form.smtpAuth.label}</td>
         <td>{$form.smtpAuth.html}<br />
         <span class="description">{ts}Does your SMTP server require authentication (user name + password)?{/ts}</span>
         </td>
       </tr>
       <tr class="crm-smtp-form-block-smtpUsername">
         <td class="label">{$form.smtpUsername.label}</td>
         <td>{$form.smtpUsername.html}</td>
       </tr>
       <tr class="crm-smtp-form-block-smtpPassword">
         <td class="label">{$form.smtpPassword.label}</td>
         <td>{$form.smtpPassword.html}<br />
         <span class="description">{ts}If your SMTP server requires authentication, enter your Username and Password here.{/ts}</span>
         </td>
       </tr>
       <tr class="crm-smtp-form-block-used_for">
         <td class="label">{$form.used_for.label}</td>
         <td>{$form.used_for.html}<br />
         <span class="description">{ts}{/ts}</span>
         </td>
       </tr>
       <tr class="crm-smtp-form-block-enable">
         <td class="label">{$form.is_active.label}</td>
         <td>{$form.is_active.html}<br />
         </td>
       </tr>
       <tr class="crm-smtp-form-block-mandril_post_url">
         <td class="label">{$form.mandril_post_url.label}</td>
         <td>{$form.mandril_post_url.html}</td>
       </tr>   
       <tr class="crm-smtp-form-block-notify_group">
         <td class="label">{$form.group_id.label}</td>
         <td>{$form.group_id.html}
         </br><span class="description">{ts}Group to notify for hard and soft bounces.{/ts}</span>
         </td>
       </tr>
    </table>
  </fieldset>
</div>
<div class="spacer"></div>
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl"}
  <span class="place-left">&nbsp;</span>
  <span class="crm-button crm-button-type-next crm-button_qf_Smtp_refresh_test">{$form._qf_MandrillSmtpSetting_refresh_test.html}</span>
</div>
</div>