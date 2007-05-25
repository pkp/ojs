{**
 * settingsForm.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Google Scholar plugin settings
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.gateways.googleScholar.displayName"}
{include file="common/header.tpl"}

{url|assign:"directoryUrl" page="gateway" op="plugin" path="GoogleScholarPlugin"}
{translate key="plugins.gateways.googleScholar.settings.description" directoryUrl=$directoryUrl}

<div class="separator">&nbsp;</div>

<h3>{translate key="plugins.gateways.googleScholar.settings"}</h3>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="publisherName" required="true" key="plugins.gateways.googleScholar.settings.publisherName"}</td>
	<td width="80%" class="value"><input type="text" name="publisherName" value="{$publisherName|escape}" size="35" maxlength="80" id="publisherName" class="textField" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="contact" required="true" key="plugins.gateways.googleScholar.settings.contact"}</td>
	<td width="80%" class="value">
		{foreach from=$contact item=thisContact key=key}
			<input type="text" name="contact[]" value="{$thisContact|escape}" size="35" maxlength="80" id="{if $key==0}contact{else}contact-{$key}{/if}" class="textField" />
			{if $contact|@count>1}<input type="submit" name="deleteContact-{$key}" value="{translate key="common.delete"}" class="button"/>{/if}
			<br/>
		{foreachelse}
			<input type="text" name="contact[]" size="35" maxlength="80" id="contact" class="textField" /><br/>
		{/foreach}
		{if $contact|@count<5}
			<input type="submit" name="addContact" class="button" value="{translate key="plugins.gateways.googleScholar.settings.addContact"}"/><br/>
		{/if}
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="publisherLocation" key="plugins.gateways.googleScholar.settings.publisherLocation"}</td>
	<td width="80%" class="value"><input type="text" name="publisherLocation" value="{$publisherLocation|escape}" size="35" maxlength="80" id="publisherLocation" class="textField" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="publisherResultName" key="plugins.gateways.googleScholar.settings.publisherResultName"}</td>
	<td width="80%" class="value"><input type="text" name="publisherResultName" value="{$publisherResultName|escape}" size="35" maxlength="80" id="publisherResultName" class="textField" /></td>
</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/> <input type="button" class="button" onclick="document.location='{plugin_url path="checkData" escape="false"}'" value="{translate key="plugins.gateways.googleScholar.checkData"}"/> <input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location.href='{url|escape:"quotes" page="manager" op="plugins" escape="false"}'"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
