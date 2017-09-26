{**
 * settingsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Contributed by Lepidus Tecnologia
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * GOST Citation plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.citationFormats.GOST.manager.GOSTCitationSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="GOSTCitationSettings">
<div id="description">{translate key="plugins.citationFormats.GOST.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#setupForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" name="setupForm" id="setupForm" method="post" action="{plugin_url path="settings"}">
{csrf}
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locales">
<table class="data">
	<tr>
		<td class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td class="value">
			{plugin_url|assign:"setupFormUrl" path="settings" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<br/>

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="location" key="plugins.citationFormats.GOST.manager.settings.location"}</td>
		<td class="value"><input type="text" name="location[{$formLocale|escape}]" id="location" value="{$location[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>


{include file="common/footer.tpl"}
