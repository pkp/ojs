{**
 * selectUser.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List a set of users and allow one to be selected.
 *
 * $Id$
 *}
{include file="common/header.tpl"}

<h3>{translate key=$pageSubTitle}</h3>
<form name="submit" method="post" action="{url op=$actionHandler path=$articleId}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url op=$actionHandler path=$articleId searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter}</strong>{else}{$letter}{/if}</a> {/foreach}<a href="{url op=$actionHandler path=$articleId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<a name="users"></a>

<table width="100%" class="listing">
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="30%">{translate key="user.name"}</td>
	<td width="20%">{translate key="submissions.completed}</td>
	<td width="20%">{translate key="submissions.active"}</td>
	<td width="20%">{translate key="editor.submissions.lastAssigned"}</td>
	<td width="10%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{iterate from=users item=user}
{assign var="userid" value=$user->getUserId()}
{assign var="stats" value=$statistics[$userid]}
<tr valign="top">
	<td><a class="action" href="{url op="userProfile" path=$userid}">{$user->getFullName(true)|escape}</a></td>
	<td>{if $stats.complete}{$stats.complete}{else}0{/if}</td>
	<td>{if $stats.incomplete}{$stats.incomplete}{else}0{/if}</td>
	<td>{if $stats.last_assigned}{$stats.last_assigned|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	<td>
		{if $currentUser != $userid}
			<a href="{url op=$actionHandler path=$articleId|to_array:$userid}" class="action">{translate key="common.assign"}</a>
		{else}
			{translate key="common.alreadyAssigned"}</a>
		{/if}
	</td>
</tr>
<tr><td colspan="5" class="{if $users->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="5" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr><td colspan="5" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$users}</td>
		<td colspan="3" align="right">{page_links anchor="users" name="users" iterator=$users searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth}</td>
	</tr>
{/if}
</table>
{if $backLink}
<a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
