{**
 * submissionsUnassigned.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of submissions unassigned.
 *
 * $Id$
 *}

<div id="summary">
	<table>
		<tr>
			<td>{translate key="editor.submissions.activeAssignments"}</td>
			<td align="right">{translate key="editor.submissions.editor}:&nbsp;{$editor}</td>
		</tr>
		<tr>
			<td colspan="2">{translate key="editor.submissions.showBy"}:&nbsp;<select name="section" onchange="location.href='{$pageUrl}/editor/index/submissionsUnassigned?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select></td>
		</tr>
	</table>
</div>

<div id="hitlistTitles">
	<table>
		<tr>
			<td width="5%" align="center">{translate key="common.id"}</td>
			<td width="11%" align="center"><a href="{$pageUrl}/editor/index/submissionsUnassigned?sort=submitted&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="editor.submissions.submitted"}</a></td>
			<td width="6%" align="center">{translate key="editor.submissions.sec"}</td>
			<td align="center">{translate key="editor.submissions.authors"}</td>
			<td width="40%" align="center">{translate key="editor.submissions.title"}</td>
		</tr>
	</table>
</div>

{foreach from=$submissions item=submission}

<div class="hitlistRecord">
	{assign var="articleId" value=$submission->getArticleId()}
	<table>
		<tr class="{cycle values="row,rowAlt"}">
			<td width="5%" align="center"><a href="{$requestPageUrl}/submission/{$articleId}">{$submission->getArticleId()}</a></td>
			<td width="11%" align="center">{$submission->getDateSubmitted()|date_format:$dateFormatShort}</td>
			<td width="6%" align="center">{$submission->getSectionAbbrev()}</td>
			<td>
				{foreach from=$submission->getAuthors() item=author name=authorList}
					{$author->getLastName()}{if !$smarty.foreach.authorList.last},{/if}
				{/foreach}
			</td>
			<td width="40%"><a href="{$requestPageUrl}/submission/{$articleId}">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		</tr>
	</table>
</div>

{foreachelse}

<div class="hitlistNoRecords">
{translate key="editor.submissions.noSubmissions"}
</div>

{/foreach}
