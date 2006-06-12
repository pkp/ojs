{**
 * announcementTypeForm.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Announcement type form under journal management.
 *
 * $Id$
 *}

{assign var="pageCrumbTitle" value="$announcementTypeTitle"}
{if $typeId}
	{assign var="pageTitle" value="manager.announcementTypes.edit"}
{else}
	{assign var="pageTitle" value="manager.announcementTypes.create"}
{/if}

{assign var="pageId" value="manager.announcementTypes.announcementTypeForm"}
{include file="common/header.tpl"}

<br/>

<form method="post" action="{url op="updateAnnouncementType"}">
{if $typeId}
<input type="hidden" name="typeId" value="{$typeId}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="typeName" required="true" key="manager.announcementTypes.form.typeName"}</td>
	<td width="80%" class="value"><input type="text" name="typeName" value="{$typeName|escape}" size="40" id="title" maxlength="80" class="textField" /></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $typeId}<input type="submit" name="createAnother" value="{translate key="manager.announcementTypes.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="announcementTypes" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
