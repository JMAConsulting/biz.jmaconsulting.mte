{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-smtp-form-block">
<div id="help">


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
         </br><span class="description">{ts}Group to notify for hard and soft bounce.{/ts}</span>
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