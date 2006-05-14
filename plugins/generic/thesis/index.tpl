{**
 * index.tpl
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of thesis abstract titles. 
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.generic.thesis.theses"}
{include file="common/header.tpl"}

<table width="100%" class="listing">
	<tr>
		<td colspan="2">{$thesisIntroduction|nl2br}</td>
	</tr>
	<tr>
		<td colspan="2">
			<ul class="plain">
				<li>&#187; <a href="{url op="submit"}">{translate key="plugins.generic.thesis.submitLink"}</a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=theses item=thesis}
	<tr valign="top">
		<td width="80%">{$thesis->getTitle()|escape}</td>
		<td width="20%" align="right"><a class="file" href="{url op="view" path=$thesis->getThesisId()}">{translate key="plugins.generic.thesis.view"}</a></td>
	</tr>
	<tr valign="top">
		<td colspan="2" style="padding-left: 30px;font-style: italic;">{$thesis->getStudentFullName()|escape}<br />{$thesis->getDepartment()|escape}, {$thesis->getUniversity()|escape}
		</td>
	</tr>
	<tr>
		<td colspan="2" class="{if $theses->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $theses->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="plugins.generic.thesis.noneExist"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$theses}</td>
		<td align="right">{page_links name="theses" iterator=$theses}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
