{**
 * selectSectionEditor.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List copyeditors and give the ability to select a copyeditor.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{include file="common/header.tpl"}

<h3>{translate key="editor.article.selectSectionEditor"}</h3>

<table width="100%" class="listing">
<tr><td colspan="3" class="headseparator"></tr>
<tr valign="top">
	<td class="heading" width="20%">{translate key="user.username"}</td>
	<td class="heading" width="60%">{translate key="user.name"}</td>
	<td class="heading" width="20%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="3" class="headseparator"></tr>
{foreach from=$sectionEditors item=sectionEditor name=editors}
<tr valign="top">
	<td><a class="action" href="{$pageUrl}/editor/assignEditor/{$articleId}/{$sectionEditor->getUserId()}">{$sectionEditor->getUsername()}</a></td>
	<td>{$sectionEditor->getFullName()}</td>
	<td><a class="action" href="{$pageUrl}/editor/assignEditor/{$articleId}/{$sectionEditor->getUserId()}">{translate key="common.assign"}</a></td>
</tr>
<tr><td colspan="3" class="{if $smarty.foreach.editors.last}end{/if}separator"></tr>
{foreachelse}
<tr>
<td colspan="3" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
</tr>
{/foreach}
</table>

{include file="common/footer.tpl"}
