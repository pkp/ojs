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

{include file="common/header.tpl"}

<h3>{translate key=$pageSubTitle}</h3>

<table width="100%" class="listing">
<tr><td colspan="6" class="headseparator"></tr>
<tr valign="top">
	<td class="heading" width="20%">{translate key="user.username"}</td>
	<td class="heading" width="30%">{translate key="user.name"}</td>
	<td class="heading" width="10%">{translate key="editor.submissions.activeAssignments"}</td>
	<td class="heading" width="10%">{translate key="editor.submissions.lastAssigned"}</td>
	<td class="heading" width="10%">{translate key="copyeditor.completedAssignments"}</td>
	<td class="heading" width="20%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="6" class="headseparator"></tr>
{foreach from=$users item=user name=users}
{assign var="userid" value=$user->getUserId()}
{assign var="stats" value=$statistics[$userid]}
<tr valign="top">
	<td><a href="{$requestPageUrl}/userProfile/{$userid}">{$user->getUsername()}</a></td>
	<td>{$user->getFullName(true)}</td>
	<td>{$stats.incomplete}</td>
	<td>{if $stats.last_assigned}{$stats.last_assigned|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
	<td>{$stats.complete}</td>
	<td><a href="{$requestPageUrl}/{$actionHandler}/{$articleId}/{$userid}" class="action">{translate key="common.assign"}</a></td>
</tr>
<tr><td colspan="6" class="{if $smarty.foreach.users.last}end{/if}separator"></tr>
{foreachelse}
<tr>
<td colspan="6" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>
{if $backLink}
<a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
