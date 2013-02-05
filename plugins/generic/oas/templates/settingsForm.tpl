{**
 * plugins/generic/oas/templates/settingsForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * OA-S plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.oas.settings.oasSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="oasSettings">
	<script type="text/javascript">
		$(function() {ldelim}
			// Attach the form handler.
			$('#oasSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
		{rdelim});
	</script>
	<form class="pkp_form" id="oasSettingsForm" method="post" action="{plugin_url path="settings"}">
		{include file="common/formErrors.tpl"}

		<h3>{translate key="plugins.generic.oas.settings.saltServerSettings"}</h3>

		<div id="description"><p>{translate key="plugins.generic.oas.settings.description"}</p></div>
		<div class="separator"></div>
		<br />

		<table width="100%" class="data">
			<tr valign="top">
				<td class="label">{fieldLabel name="saltApiUsername" required="true" key="plugins.generic.oas.settings.saltApiUsername"}</td>
				<td class="value"><input type="text" name="saltApiUsername" id="saltApiUsername" value="{$saltApiUsername|escape}" size="15" maxlength="50" class="textField" />
					<br />
					<span class="instruct">{translate key="plugins.generic.oas.settings.saltApiUsernameInstructions"}</span>
				</td>
			</tr>
			<tr valign="top">
				<td class="label">{fieldLabel name="saltApiPassword" required="true" key="plugins.generic.oas.settings.saltApiPassword"}</td>
				<td class="value"><input type="password" name="saltApiPassword" id="saltApiPassword" value="{$saltApiPassword|escape}" size="15" maxlength="25" class="textField" />
					<br />
					<span class="instruct">{translate key="plugins.generic.oas.settings.saltApiPasswordInstructions"}</span>
				</td>
			</tr>
		</table>

		<br/>

		<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>

		<br/>
		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		<br/>
	</form>
</div>
{include file="common/footer.tpl"}
