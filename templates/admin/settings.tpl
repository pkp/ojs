{**
 * settings.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site settings form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.siteSettings"}
{assign var="pageId" value="admin.settings"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/admin/saveSettings">
<div class="form">
	{include file="common/formErrors.tpl"}

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="title"}{translate key="admin.settings.siteTitle"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="intro"}{translate key="admin.settings.introduction"}:{/formLabel}</td>
	<td class="formField"><textarea name="intro" cols="60" rows="10" class="textArea">{$intro|escape}</textarea></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="redirect"}{translate key="admin.settings.journalRedirect"}:{/formLabel}</td>
	<td class="formLabelRightPlain"><select name="redirect" size="1" class="selectMenu">{html_options options=$redirectOptions selected=$redirect}</select></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="admin.settings.journalRedirectInstructions"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="about"}{translate key="admin.settings.aboutDescription"}:{/formLabel}</td>
	<td class="formField"><textarea name="about" cols="60" rows="10" class="textArea">{$about|escape}</textarea></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="contactName"}{translate key="admin.settings.contactName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="contactName" value="{$contactName|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="contactEmail"}{translate key="admin.settings.contactEmail"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="contactEmail" value="{$contactEmail|escape}" size="40" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="minPasswordLength"}{translate key="admin.settings.minPasswordLength"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="minPasswordLength" value="{$minPasswordLength|escape}" size="4" maxlength="2" class="textField" /> {translate key="admin.settings.passwordCharacters"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/admin'" /></td>
</tr>
</table>

</div>
</form>

{include file="common/footer.tpl"}
