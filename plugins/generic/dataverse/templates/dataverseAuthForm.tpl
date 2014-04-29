{**
 * plugins/generic/dataverse/dataverseAuthForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Connect to Dataverse Network
 *
 *}
{strip}
  {assign var="pageTitle" value="plugins.generic.dataverse.displayName"}
  {include file="common/header.tpl"}
{/strip}

<ul class="menu">
  <li class="current"><a href="{plugin_url path="connect"}">{translate key="plugins.generic.dataverse.settings.connect"}</a></li>
  <li><a href="{plugin_url path="select"}">{translate key="plugins.generic.dataverse.settings.selectDataverse"}</a></li>
  <li><a href="{plugin_url path="settings"}">{translate key="plugins.generic.dataverse.settings"}</a></li>
</ul>

<div style="margin: 1em 0;">

  <form method="post" action="{plugin_url path="connect"}" id="dvnForm">
    {include file="common/formErrors.tpl"}

    <table width="100%" class="data">
      <tr valign="top">
        <td class="label">{fieldLabel name="dvnUri" required="true" key="plugins.generic.dataverse.settings.dvnUri"}</td>
        <td class="value"><input type="text" name="dvnUri" id="dvnUri" value="{$dvnUri|escape}" size="40" maxlength="90" class="textField"/></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>{translate key="plugins.generic.dataverse.settings.dvnUriDescription"}</td>
      </tr>
      <tr valign="top">
        <td class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
        <td class="value"><input type="text" name="username" id="username" value="{$username|escape}" size="20" maxlength="90" class="textField" /></td>
      </tr>      
      <tr>
        <td>&nbsp;</td>
        <td>{translate key="plugins.generic.dataverse.settings.usernameDescription"}</td>
      </tr>
      <tr valign="top">
        <td class="label">{fieldLabel name="password" required="true" key="user.password"}</td>
        <td class="value">
          <input type="password" name="password" id="password" value="{$password|escape}" size="20" maxlength="90" class="textField"/>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>{translate key="plugins.generic.dataverse.settings.passwordDescription"}</td>
      </tr>
    </table>
    <input type="submit" class="button defaultButton" name="save" value="{translate key="common.saveAndContinue"}"  /> 
    <input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location='{plugin_url path=""}';"/>
  </form>
  <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
