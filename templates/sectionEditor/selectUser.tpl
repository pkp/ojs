{**
 * selectUser.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List a set of users and allow one to be selected.
 *
 * $Id$
 *}

{assign var="start" value="A"|ord}

{include file="common/header.tpl"}
<h3>{translate key=$pageSubTitle}</h3>
<form name="submit" method="post" action="{$requestPageUrl}/{$actionHandler}/{$articleId}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains">{translate key="form.contains"}</option>
		<option value="is">{translate key="form.is"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{section loop=26 name=letters}<a href="{$requestPageUrl}/{$actionHandler}/{$articleId}?search_initial={$smarty.section.letters.index+$start|chr}">{$smarty.section.letters.index+$start|chr}</a> {/section}</p>

<table width="100%" class="listing">
<tr><td colspan="5" class="headseparator"></tr>
<tr class="heading" valign="bottom">
	<td width="30%">{translate key="user.name"}</td>
	<td width="20%">{translate key="submissions.completed}</td>
	<td width="20%">{translate key="submissions.active"}</td>
	<td width="20%">{translate key="editor.submissions.lastAssigned"}</td>
	<td width="10%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="5" class="headseparator"></tr>
{iterate from=$users item=user}
{assign var="userid" value=$user->getUserId()}
{assign var="stats" value=$statistics[$userid]}
<tr valign="top">
	<td><a class="action" href="{$requestPageUrl}/userProfile/{$userid}">{$user->getFullName(true)}</a></td>
	<td>{if $stats.complete}{$stats.complete}{else}0{/if}</td>
	<td>{if $stats.incomplete}{$stats.incomplete}{else}0{/if}</td>
	<td>{if $stats.last_assigned}{$stats.last_assigned|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	<td><a href="{$requestPageUrl}/{$actionHandler}/{$articleId}/{$userid}" class="action">{translate key="common.assign"}</a></td>
</tr>
<tr><td colspan="5" class="{if $users->eof()}end{/if}separator"></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="5" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr><td colspan="5" class="endseparator"></tr>
	</table>
{else}
	</table>
	{page_links name="users" page=$users->getPage() pageCount=$users->getPageCount()}
	<br /><br />
{/if}
{if $backLink}
<a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
