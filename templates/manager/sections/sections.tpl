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

{assign var="pageTitle" value="section.sections"}
{include file="common/header.tpl"}
<br/>
<table width="100%" class="listing">
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="60%">{translate key="section.title"}</td>
		<td width="25%">{translate key="section.abbreviation"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
	{foreach from=$sections item=section name=sections}
	<tr valign="top">
		<td>{$section->getTitle()}</td>
		<td>{$section->getAbbrev()}</td>
		<td align="right">
			<nobr>
			<a href="{$pageUrl}/manager/editSection/{$section->getSectionId()}" class="action">{translate key="common.edit"}</a>
			<a href="{$pageUrl}/manager/deleteSection/{$section->getSectionId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.sections.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			<a href="{$pageUrl}/manager/moveSection?d=u&amp;sectionId={$section->getSectionId()}">&uarr;</a> <a href="{$pageUrl}/manager/moveSection?d=d&amp;sectionId={$section->getSectionId()}">&darr;</a>
			</nobr>
		</td>
	</tr>
	<tr>
		<td colspan="3" class="{if $smarty.foreach.sections.last}end{/if}separator">&nbsp;</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.sections.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{/foreach}
</table>
<a class="action" href="{$pageUrl}/manager/createSection">{translate key="manager.sections.create"}</a>

{include file="common/footer.tpl"}
