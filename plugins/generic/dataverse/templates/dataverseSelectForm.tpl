{**
 * plugins/generic/dataverse/dataverseSelectForm.tpl
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
  <li><a href="{plugin_url path="connect"}">{translate key="plugins.generic.dataverse.settings.connect"}</a></li>
  <li class="current"><a href="{plugin_url path="select"}">{translate key="plugins.generic.dataverse.settings.selectDataverse"}</a></li>
  <li><a href="{plugin_url path="settings"}">{translate key="plugins.generic.dataverse.settings"}</a></li>
</ul>

<div style="margin: 1em 0;">

  <form method="post" action="{plugin_url path="select"}" id="dvSelectForm">
    {include file="common/formErrors.tpl"}
    <table width="100%" class="data">
      <tr valign="top">
        <td class="label">{fieldLabel name="dataverse" required="true" key="plugins.generic.dataverse.settings.dataverse"}</td>
        <td class="value">
          {html_options name="dataverse" id="dataverse" options=$dataverses selected=$dataverseUri}
       </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>{translate key="plugins.generic.dataverse.settings.dataverseDescription"}</td>
      </tr>
    </table>
    <div style="margin: 1em 0">
      <input type="submit" class="button defaultButton" name="save" value="{translate key="common.saveAndContinue"}"  /> 
      <input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location='{plugin_url path=""}';"/>
    </div>
  </form>
  <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
