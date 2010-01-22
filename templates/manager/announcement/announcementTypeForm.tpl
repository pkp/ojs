{**
 * announcementTypeForm.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
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

<form name="announcementType" method="post" action="{url op="updateAnnouncementType"}">
{if $typeId}
<input type="hidden" name="typeId" value="{$typeId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $typeId}{url|assign:"announcementTypeUrl" op="editAnnouncementType" path=$typeId escape=false}
			{else}{url|assign:"announcementTypeUrl" op="createAnnouncementType" escape=false}
			{/if}
			{form_language_chooser form="announcementType" url=$announcementTypeUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="name" required="true" key="manager.announcementTypes.form.typeName"}</td>
	<td width="80%" class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="40" id="name" maxlength="80" class="textField" /></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $typeId}<input type="submit" name="createAnother" value="{translate key="manager.announcementTypes.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="announcementTypes" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
