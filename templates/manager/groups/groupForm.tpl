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

<form method="post" action="{url op="updateGroup"}">
{if $group}
	<input type="hidden" name="groupId" value="{$group->getGroupId()}"/>
{/if}

{include file="common/formErrors.tpl"}
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="title" required="true" key="manager.groups.title"}</td>
	<td width="80%" class="value"><input type="text" name="title" value="{$title|escape}" size="35" maxlength="80" id="title" class="textField" /></td>
</tr>

{if $alternateLocale1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="titleAlt1" required="true" key="manager.groups.title"} ({$languageToggleLocales.$alternateLocale1|escape})</td>
		<td width="80%" class="value"><input type="text" name="titleAlt1" value="{$titleAlt1|escape}" size="35" maxlength="80" id="titleAlt1" class="textField" /></td>
	</tr>
{/if}

{if $alternateLocale2}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="titleAlt2" required="true" key="manager.groups.title"} ({$languageToggleLocales.$alternateLocale2|escape})</td>
		<td width="80%" class="value"><input type="text" name="titleAlt2" value="{$titleAlt2|escape}" size="35" maxlength="80" id="titleAlt2" class="textField" /></td>
	</tr>
{/if}

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
