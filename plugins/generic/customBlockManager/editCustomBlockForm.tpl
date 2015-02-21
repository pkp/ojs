{**
 * plugins/generic/customBlockManager/editCustomBlockForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editing a custom sidebar block
 *
 *}
{assign var="pageTitle" value="plugins.generic.customBlock.editContent"}
{include file="common/header.tpl"}
<br />
<form method="post" id="editCustomBlockForm" action="{plugin_url path="save"}" >
{include file="common/formErrors.tpl"}
{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{plugin_url|assign:"editCustomBlockFormUrl" page="manager" op="plugin" path="edit" escape=false}
			{form_language_chooser form="editCustomBlockForm" url=$editCustomBlockFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<br />
<table class="data" width="100%">
	<tr>
		<td width="20%" class="label" valign="top">{fieldLabel required="true" name="blockContent" key="plugins.generic.customBlock.content"}</td>
		<td>
		<textarea name="blockContent[{$formLocale|escape}]" cols="30" rows="30">{$blockContent[$formLocale]|escape}</textarea>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{plugin_url path="settings"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
