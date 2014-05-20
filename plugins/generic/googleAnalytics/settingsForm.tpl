{**
 * plugins/generic/googleAnalytics/settingsForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Google Analytics plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.googleAnalytics.manager.googleAnalyticsSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="googleAnalyticsSettings">
<div id="description">{translate key="plugins.generic.googleAnalytics.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#gaSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="gaSettingsForm" method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="googleAnalyticsSiteId" required="true" key="plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteId"}</td>
		<td class="value"><input type="text" name="googleAnalyticsSiteId" id="googleAnalyticsSiteId" value="{$googleAnalyticsSiteId|escape}" size="15" maxlength="25" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteIdInstructions"}</span>
	</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="trackingCode-urchin" required="true" key="plugins.generic.googleAnalytics.manager.settings.trackingCode"}</td>
		<td class="value"><input type="radio" name="trackingCode" id="trackingCode-urchin" value="urchin" {if $trackingCode eq "urchin" || $trackingCode eq ""}checked="checked" {/if}/> {translate key="plugins.generic.googleAnalytics.manager.settings.urchin"}</td>
	</tr>
	<tr>
		<td class="label">&nbsp;</td>
		<td class="value"><input type="radio" name="trackingCode" id="trackingCode-ga" value="ga" {if $trackingCode eq "ga"}checked="checked" {/if}/> {translate key="plugins.generic.googleAnalytics.manager.settings.ga"}</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
