{**
 * groupForm.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Group form under journal management.
 *
 * $Id$
 *}

{assign var="pageId" value="manager.groups.groupForm"}
{assign var="pageCrumbTitle" value=$pageTitle}
{include file="common/header.tpl"}

{if $group}
	<ul class="menu">
		<li class="current"><a href="{url op="editGroup" path=$group->getGroupId()}">{translate key="manager.groups.editTitle"}</a></li>
		<li><a href="{url op="groupMembership" path=$group->getGroupId()}">{translate key="manager.groups.membership}</a></li>
	</ul>
{/if}

<br/>

<form name="groupForm" method="post" action="{url op="updateGroup"}">
{if $group}
	<input type="hidden" name="groupId" value="{$group->getGroupId()}"/>
{/if}

{include file="common/formErrors.tpl"}
<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" required="true" key="common.language"}</td>
		<td width="80%" class="value">
			{if $group}{url|assign:"groupFormUrl" op="editGroup" path=$group->getGroupId()}
			{else}{url|assign:"groupFormUrl" op="createGroup"}
			{/if}
			{form_language_chooser form="groupForm" url=$groupFormUrl}
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="title" required="true" key="manager.groups.title"}</td>
	<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" size="35" maxlength="80" id="title" class="textField" /></td>
</tr>

<tr valign="top">
	<td width="20%" class="label">{translate key="common.type"}</td>
	<td width="80%" class="value">
		{foreach from=$groupContextOptions item=groupContextOptionKey key=groupContextOptionValue}
			<input type="radio" name="context" value="{$groupContextOptionValue|escape}" {if $context == $groupContextOptionValue}checked="true" {/if} id="context-{$groupContextOptionValue|escape}" />&nbsp;
			{fieldLabel name="context-`$groupContextOptionValue`" key=$groupContextOptionKey}<br />
		{/foreach}
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="groups" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
