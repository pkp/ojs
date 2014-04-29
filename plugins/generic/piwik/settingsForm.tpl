{**
 * plugins/generic/piwik/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Piwik plugin settings
 *
 *}

{assign var="pageTitle" value="plugins.generic.piwik.manager.piwikSettings"}
{include file="common/header.tpl"}

{translate key="plugins.generic.piwik.manager.settings.description"}

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="piwikUrl" required="true" key="plugins.generic.piwik.manager.settings.piwikUrl"}</td>
		<td width="80%" class="value"><input type="text" name="piwikUrl" id="piwikUrl" value="{if $piwikUrl}{$piwikUrl|escape}{else}http://{/if}" size="30" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.piwik.manager.settings.piwikUrlInstructions"}</span>
	</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="piwikSiteId" required="true" key="plugins.generic.piwik.manager.settings.piwikSiteId"}</td>
		<td class="value"><input type="text" name="piwikSiteId" id="piwikSiteId" value="{$piwikSiteId|escape}" size="10" maxlength="10" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.piwik.manager.settings.piwikSiteIdInstructions"}</span>
	</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
