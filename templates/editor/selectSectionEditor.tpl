{**
 * selectCopyeditor.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List copyeditors and give the ability to select a copyeditor.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="editor.article.selectSectionEditor"}</div>

<table width="100%">
<tr class="heading">
	<td>{translate key="user.username"}</td>
	<td>{translate key="user.name"}</td>
	<td></td>
</tr>
{foreach from=$sectionEditors item=sectionEditor}
<tr class="{cycle values="row,rowAlt"}">
	<td><a href="{$pageUrl}/editor/assignEditor/{$articleId}/{$sectionEditor->getUserId()}">{$sectionEditor->getUsername()}</a></td>
	<td width="100%">{$sectionEditor->getFullName()}</td>
	<td><a href="{$pageUrl}/editor/assignEditor/{$articleId}/{$sectionEditor->getUserId()}" class="tableAction">{translate key="common.assign"}</a></td>
</tr>
{foreachelse}
<tr>
<td colspan="3" class="noResults">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
