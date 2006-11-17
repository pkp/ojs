{**
 * settings.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site settings form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.siteSettings"}
{include file="common/header.tpl"}

<form name="settings" method="post" action="{url op="saveSettings"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" key="admin.settings.siteTitle" required="true"}</td>
		<td width="80%" class="value"><input type="text" id="title" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="intro" key="admin.settings.introduction"}</td>
		<td class="value"><textarea name="intro" id="intro" cols="40" rows="10" class="textArea">{$intro|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="redirect" key="admin.settings.journalRedirect"}</td>
		<td class="value"><select name="redirect" id="redirect" size="1" class="selectMenu">{html_options options=$redirectOptions selected=$redirect}</select></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="admin.settings.journalRedirectInstructions"}</span></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="about" key="admin.settings.aboutDescription"}</td>
		<td class="value"><textarea name="about" id="about" cols="40" rows="10" class="textArea">{$about|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="contactName" key="admin.settings.contactName" required="true"}</td>
		<td class="value"><input type="text" id="contactName" name="contactName" value="{$contactName|escape}" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="contactEmail" key="admin.settings.contactEmail" required="true"}</td>
		<td class="value"><input type="text" id="contactEmail" name="contactEmail" value="{$contactEmail|escape}" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="minPasswordLength" key="admin.settings.minPasswordLength" required="true"}</td>
		<td class="value"><input type="text" id="minPasswordLength" name="minPasswordLength" value="{$minPasswordLength|escape}" size="4" maxlength="2" class="textField" /> {translate key="admin.settings.passwordCharacters"}</td>
	</tr>
	<tr>
		<td width="20%" valign="top" class="label">{translate key="admin.settings.siteStyleSheet"}</td>
		<td width="80%" valign="top" class="value">
			<input type="file" name="siteStyleSheet" class="uploadField" /> <input type="submit" name="uploadSiteStyleSheet" value="{translate key="common.upload"}" class="button" />
			{if $siteStyleFileExists}
				<br />
				{translate key="common.fileName"}: <a href="{$publicFilesDir}/{$styleFilename}" class="file">{$originalStyleFilename|escape}</a> {$dateStyleFileUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteSiteStyleSheet" value="{translate key="common.delete"}" class="button" />
			{/if}
		</td>
	</tr>


</table>

<br />

<h4>{translate key="admin.settings.oaiRegistration"}</h4>

{url|assign:"oaiUrl" page="oai"}
{url|assign:"siteUrl" page="index"}
<p>{translate key="admin.settings.oaiRegistrationDescription" siteUrl=$siteUrl oaiUrl=$oaiUrl}</p>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="admin" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
