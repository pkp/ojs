{**
 * selectReviewer.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List reviewers and give the ability to select a reviewer.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="submission.selectReviewer"}</div>

<table width="100%">
<tr class="heading">
	<td>{translate key="user.username"}</td>
	<td>{translate key="user.name"}</td>
	<td></td>
</tr>
{foreach from=$reviewers item=reviewer}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/editor/selectReviewer/{$articleId}/{$reviewer->getUserId()}">{$reviewer->getUsername()}</a></td>
	<td width="100%">{$reviewer->getFullName()}</td>
	<td><a href="{$pageUrl}/editor/selectReviewer/{$articleId}/{$reviewer->getUserId()}" class="tableAction">Assign</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="3" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
