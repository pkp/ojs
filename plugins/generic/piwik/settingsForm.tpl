{**
 * plugins/generic/piwik/settingsForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#piwikSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="piwikSettingsForm" method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="piwikUrl" required="true" key="plugins.generic.piwik.manager.settings.piwikUrl"}</td>
		<td class="value"><input type="text" name="piwikUrl" id="piwikUrl" value="{if $piwikUrl}{$piwikUrl|escape}{else}http://{/if}" size="30" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.piwik.manager.settings.piwikUrlInstructions"}</span>
	</td>
	</tr>
	<tr>
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
