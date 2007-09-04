{**
 * settingsForm.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Google Analytics plugin settings
 *
 * $Id$
 *}
{assign var="pageTitle" value="plugins.generic.googleAnalytics.manager.googleAnalyticsSettings"}
{include file="common/header.tpl"}

{translate key="plugins.generic.googleAnalytics.manager.settings.description"}

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="googleAnalyticsSiteId" required="true" key="plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteId"}</td>
		<td width="80%" class="value"><input type="text" name="googleAnalyticsSiteId" id="googleAnalyticsSiteId" value="{$googleAnalyticsSiteId|escape}" size="15" maxlength="25" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteIdInstructions"}</span>
	</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
