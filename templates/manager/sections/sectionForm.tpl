{**
 * sectionForm.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a journal section.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.sections"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/manager/updateSection">
{if $sectionId}
<input type="hidden" name="sectionId" value="{$sectionId}" />
{/if}

<div class="form">
	{include file="common/formErrors.tpl"}

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="title"}{translate key="manager.sections.sectionTitle"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="abbrev"}{translate key="manager.sections.sectionAbbrev"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="abbrev" value="{$abbrev|escape}" size="20" maxlength="20" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/sections'" /></td>
</tr>
</table>

</div>
</form>

{include file="common/footer.tpl"}