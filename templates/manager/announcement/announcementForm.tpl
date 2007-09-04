{**
 * announcementForm.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Announcement form under journal management.
 *
 * $Id$
 *}
{assign var="pageCrumbTitle" value="$announcementTitle"}
{if $announcementId}
{assign var="pageTitle" value="manager.announcements.edit"}
{else}
{assign var="pageTitle" value="manager.announcements.create"}
{/if}
{assign var="pageId" value="manager.announcement.announcementForm"}
{include file="common/header.tpl"}

<br/>

<form name="announcementForm" method="post" action="{url op="updateAnnouncement"}">
{if $announcementId}
<input type="hidden" name="announcementId" value="{$announcementId}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" required="true" key="common.language"}</td>
		<td width="80%" class="value">
			{if $typeId}{url|assign:"announcementUrl" op="editAnnouncement" path=$announcementId}
			{else}{url|assign:"announcementUrl" op="createAnnouncement"}
			{/if}
			{form_language_chooser form="announcementForm" url=$announcementUrl}
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="typeId" key="manager.announcements.form.typeId"}</td>
	<td width="80%" class="value"><select name="typeId" id="typeId" class="selectMenu" />
		<option value=""></option>
		{iterate from=announcementTypes item=announcementType}
		<option value="{$announcementType->getTypeId()}"{if $typeId == $announcementType->getTypeId()} selected="selected"{/if}>{$announcementType->getAnnouncementTypeName()|escape}</option>
		{/iterate} 
	</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="title" required="true" key="manager.announcements.form.title"}</td>
	<td class="value"><input type="text" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" size="40" id="title" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="descriptionShort" required="true" key="manager.announcements.form.descriptionShort"}</td>
	<td class="value"><textarea name="descriptionShort[{$formLocale|escape}]" id="descriptionShort" cols="40" rows="6" class="textArea" />{$descriptionShort[$formLocale]|escape}</textarea>
		<br />
		<span class="instruct">{translate key="manager.announcements.form.descriptionShortInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" required="true" key="manager.announcements.form.description"}</td>
	<td class="value"><textarea name="description[{$formLocale|escape}]" id="description" cols="40" rows="6" class="textArea" />{$description[$formLocale]|escape}</textarea>
		<br />
		<span class="instruct">{translate key="manager.announcements.form.descriptionInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateExpire" key="manager.announcements.form.dateExpire"}</td>
	<td class="value">
		{if $dateExpire != null}
			{html_select_date prefix="dateExpire" all_extra="class=\"selectMenu\"" end_year="$yearOffsetFuture" year_empty="" month_empty="" day_empty="" time="$dateExpire"}
		{else}
			{html_select_date prefix="dateExpire" all_extra="class=\"selectMenu\"" end_year="$yearOffsetFuture" year_empty="" month_empty="" day_empty="" time="-00-00"}
		{/if}
		<br />
		<span class="instruct">{translate key="manager.announcements.form.dateExpireInstructions"}</span>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $announcementId}<input type="submit" name="createAnother" value="{translate key="manager.announcements.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="announcements" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
