{**
 * plugins/generic/alm/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ALM plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.alm.displayName"}
{include file="common/header.tpl"}
{/strip}
<div id="almPlugin">
<div id="description">{translate key="plugins.generic.alm.description"}</div>

<div class="separator">&nbsp;</div>

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="100%" colspan=2>
			{translate key="plugins.generic.alm.settings.apiKey.description"}
		</td>
	</tr>
	<tr>
		<td colspan="2">
			&nbsp;
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{fieldLabel required="true" key="plugins.generic.alm.settings.apiKey"}</td>
		<td width="60%" class="value"><input type="text" name="apiKey" value="{$apiKey|escape}" id="apiKey" size="40" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="100%" colspan=2>
			{translate key="plugins.generic.alm.settings.depositUrl.description"}
		</td>
	</tr>
	<tr valign="top">
		<td width="40%" class="label">{fieldLabel required="true" key="plugins.generic.alm.settings.depositUrl"}</td>
		<td width="60%" class="value"><input type="text" name="depositUrl" value="{$depositUrl|escape}" id="depositUrl" size="40" maxlength="120" class="textField" /></td>
	</tr>
</table>

{translate key="plugins.generic.alm.settings.ipAddress"  ip=$smarty.server.SERVER_ADDR}

<br/>
<br/>
<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
