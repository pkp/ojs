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

{assign var="pageTitle" value="submission.layoutEditor"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key=$pageSubTitle}</div>

<br />

<table width="100%">
<tr class="heading">
	<td>{translate key="user.username"}</td>
	<td>{translate key="user.name"}</td>
	<td></td>
</tr>
{foreach from=$users item=user}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$requestPageUrl}/{$actionHandler}/{$articleId}/{$user->getUserId()}">{$user->getUsername()}</a></td>
	<td width="100%">{$user->getFullName(true)}</td>
	<td><a href="{$requestPageUrl}/{$actionHandler}/{$articleId}/{$user->getUserId()}" class="tableAction">{translate key="common.assign"}</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="3" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{if $backLink}
&#187; <a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
