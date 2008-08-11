{**
 * settingsForm.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Thesis abstracts plugin settings
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.thesis.manager.settings"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{plugin_url path="theses"}">{translate key="plugins.generic.thesis.manager.theses"}</a></li>
	<li class="current"><a href="{plugin_url path="settings"}">{translate key="plugins.generic.thesis.manager.settings"}</a></li>
</ul>

<br />

<table width="100%" class="listing">
	<tr>
		<td class="headseparator">&nbsp;</td>
	</tr>
	<tr>
		<td>{translate key="plugins.generic.thesis.settings.description"}</td>
	</tr>
	<tr>
		<td class="headseparator">&nbsp;</td>
	</tr>
</table>

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<h4>{translate key="plugins.generic.thesis.settings.submissions"}</h4>
	<script type="text/javascript">
		{literal}
		<!--
			function toggleUploadCode(form) {
				form.uploadCode.disabled = !form.uploadCode.disabled;
			}
		// -->
		{/literal}
	</script>

<p>{translate key="plugins.generic.thesis.settings.uploadCodeDescription"}</p>

<table width="100%" class="data">
<tr valign="top">
	<td class="label"><input type="checkbox" name="enableUploadCode" id="enableUploadCode" value="1" onclick="toggleUploadCode(this.form)"{if $enableUploadCode} checked="checked"{/if} /></td>
	<td class="value">{fieldLabel name="uploadCode" key="plugins.generic.thesis.settings.uploadCode"} <input type="text" name="uploadCode" id="uploadCode"{if not $enableUploadCode} disabled="disabled"{/if} value="{$uploadCode|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
</table>

<div class="separator"></div>

<h4>{translate key="plugins.generic.thesis.settings.publishing"}</h4>
<br/>
<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="thesisOrder" required="true" key="plugins.generic.thesis.settings.order"}</td>
	<td width="80%" class="value"><select name="thesisOrder" id="thesisOrder" class="selectMenu">{html_options options=$validOrder selected=$thesisOrder}</select></td>
</tr>
</table>

<div class="separator"></div>

<h4>{translate key="plugins.generic.thesis.settings.thesisContact"}</h4>
<br/>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="thesisName" required="true" key="user.name"}</td>
		<td width="80%" class="value"><input type="text" name="thesisName" id="thesisName" value="{$thesisName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="thesisEmail" required="true" key="user.email"}</td>
		<td width="80%" class="value"><input type="text" name="thesisEmail" id="thesisEmail" value="{$thesisEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="thesisPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="thesisPhone" id="thesisPhone" value="{$thesisPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="thesisFax" key="user.fax"}</td>
		<td width="80%" class="value"><input type="text" name="thesisFax" id="thesisFax" value="{$thesisFax|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="thesisMailingAddress" key="common.mailingAddress"}</td>
		<td width="80%" class="value"><textarea name="thesisMailingAddress" id="thesisMailingAddress" rows="3" cols="40" class="textArea">{$thesisMailingAddress|escape}</textarea></td>
	</tr>
</table>

<div class="separator"></div>

<h4>{translate key="plugins.generic.thesis.settings.thesisIntroduction"}</h4>
<p>{translate key="plugins.generic.thesis.settings.thesisIntroductionDescription"}</p>
<table width="100%" class="data">
	<tr valign="top">
		<td width="100%" class="value"><textarea name="thesisIntroduction" id="thesisIntroduction" rows="5" cols="60" class="textArea">{$thesisIntroduction|escape}</textarea></td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
