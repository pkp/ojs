{**
 * sections.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of sections in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.sections"}
{assign var="pageId" value="manager.sections.sections"}
{include file="common/header.tpl"}

<table width="100%">
<tr class="heading">
	<td>{translate key="manager.sections.sectionTitle"}</td>
	<td>{translate key="manager.sections.sectionAbbrev"}</td>
	<td></td>
	<td></td>
	<td></td>
</tr>
{foreach from=$sections item=section}
<tr class="{cycle values="row,rowAlt"}">
	<td width="100%"><a href="{$pageUrl}/manager/editSection/{$section->getSectionId()}">{$section->getTitle()}</a></td>
	<td>{$section->getAbbrev()}</td>
	<td><a href="#" onclick="return confirmAction('{$pageUrl}/manager/deleteSection/{$section->getSectionId()}', '{translate|escape:"javascript" key="manager.sections.confirmDelete"}')" class="tableAction">{translate key="common.delete"}</a></td>
	<td><a href="{$pageUrl}/manager/editSection/{$section->getSectionId()}" class="tableAction">{translate key="common.edit"}</a></td>
	<td><nobr><a href="{$pageUrl}/manager/moveSection?d=u&amp;sectionId={$section->getSectionId()}">&uarr;</a> <a href="{$pageUrl}/manager/moveSection?d=d&amp;sectionId={$section->getSectionId()}">&darr;</a></nobr></td>
</tr>
{foreachelse}
<tr>
<td colspan="5" class="noResults">{translate key="manager.sections.noneCreated"}</td>
</tr>
{/foreach}
</table>

<a href="{$pageUrl}/manager/createSection" class="tableButton">{translate key="manager.sections.create"}</a>

{include file="common/footer.tpl"}