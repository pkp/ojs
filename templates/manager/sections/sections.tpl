{**
 * sections.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of sections in journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="section.sections"}
{include file="common/header.tpl"}
<br/>

<a name="sections"></a>

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
{iterate from=sections item=section name=sections}
	<tr valign="top">
		<td>{$section->getSectionTitle()|escape}</td>
		<td>{$section->getSectionAbbrev()|escape}</td>
		<td align="right" class="nowrap">
			<a href="{url op="editSection" path=$section->getSectionId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteSection" path=$section->getSectionId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.sections.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="moveSection" d=u sectionId=$section->getSectionId()}">&uarr;</a>&nbsp;<a href="{url op="moveSection" d=d sectionId=$section->getSectionId()}">&darr;</a>
		</td>
	</tr>
	<tr>
		<td colspan="3" class="{if $sections->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $sections->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.sections.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$sections}</td>
		<td colspan="2" align="right">{page_links anchor="sections" name="sections" iterator=$sections}</td>
	</tr>
{/if}
</table>

<a class="action" href="{url op="createSection"}">{translate key="manager.sections.create"}</a>

{include file="common/footer.tpl"}
