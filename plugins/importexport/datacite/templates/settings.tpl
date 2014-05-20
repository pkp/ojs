{**
 * @file plugins/importexport/datacite/templates/settings.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * DataCite plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.settings"}
{include file="common/header.tpl"}
{/strip}
<div id="dataciteSettings">
	{include file="common/formErrors.tpl"}
	<br />
	<br />

	<div id="description"><b>{translate key="plugins.importexport.datacite.settings.form.description"}</b></div>

	<br />

	<script>
		$(function() {ldelim}
			// Attach the form handler.
			$('#dataciteSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
		{rdelim});
	</script>
	<form class="pkp_form" id="dataciteSettingsForm" method="post" action="{plugin_url path="settings"}">
		<table class="data">
			<tr>
				<td colspan="2">
					<span class="instruct">{translate key="plugins.importexport.datacite.intro"}</span>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="label">{fieldLabel name="username" key="plugins.importexport.datacite.settings.form.username"}</td>
				<td class="value">
					<input type="text" name="username" value="{$username|escape}" size="20" maxlength="50" id="username" class="textField" />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="label">{fieldLabel name="password" key="plugins.importexport.common.settings.form.password"}</td>
				<td class="value">
					<input type="password" name="password" value="{$password|escape}" size="20" maxlength="50" id="password" class="textField" />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
		</table>

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

		<p>
			<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
			&nbsp;
			<input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
		</p>
	</form>

</div>
{include file="common/footer.tpl"}
