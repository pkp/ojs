{**
 * selectUser.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List a set of users and allow one to be selected.
 *
 * $Id$
 *}
{assign var=pageTitle value="manager.groups.membership.addMember"}
{include file="common/header.tpl"}

<form name="submit" method="post" action="{url op="addMembership" path=$group->getGroupId()}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url path=$group->getGroupId() searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url path=$group->getGroupId()}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<a name="users"></a>

<table width="100%" class="listing">
<tr><td colspan="2" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="80%">{translate key="user.name"}</td>
	<td width="20%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="2" class="headseparator">&nbsp;</td></tr>
{iterate from=users item=user}
{assign var="userid" value=$user->getUserId()}
<tr valign="top">
	<td><a class="action" href="{url op="userProfile" path=$userid}">{$user->getFullName(true)|escape}</a></td>
	<td>
		<a href="{url op="addMembership" path=$group->getGroupId()|to_array:$user->getUserId()}" class="action">{translate key="manager.groups.membership.addMember"}</a>
	</td>
</tr>
<tr><td colspan="2" class="{if $users->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="2" class="nodata">{translate key="manager.groups.membership.noUsers"}</td>
	</tr>
	<tr><td colspan="2" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$users}</td>
		<td align="right">{page_links anchor="users" name="users" iterator=$users searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth}</td>
	</tr>
{/if}
</table>
{if $backLink}
<a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
