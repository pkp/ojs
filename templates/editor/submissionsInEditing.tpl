{**
 * submissionsInEditing.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of submissions in editing.
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
			<td colspan="2">{translate key="editor.submissions.showBy"}:&nbsp;<select name="section" onchange="location.href='{$pageUrl}/editor/index/submissionsInEditing?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select></td>
		</tr>
	</table>
</div>

<div id="hitlistTitles">
	<table>
		<tr>
			<td width="10%" align="center">{translate key="editor.submissions.secEditor"}</td>
			<td width="9%" align="center"><a href="{$pageUrl}/editor/index/submissionsInEditing?sort=submitted&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="editor.submissions.submitMMDD"}</a></td>
			<td width="6%" align="center">{translate key="editor.submissions.sec"}</td>
			<td align="center">{translate key="editor.submissions.authors"}</td>
			<td width="10%" align="center">{translate key="editor.submissions.copyedit"}</td>
			<td width="10%" align="center">{translate key="editor.submissions.galley"}</td>
			<td width="10%" align="center">{translate key="editor.submissions.proof"}</td>
		</tr>
	</table>
</div>

{foreach from=$submissions item=submission}

<div class="hitlistRecord">
	<table>
		{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
		{assign var="proofAssignment" value=$submission->getProofAssignment()}
		{assign var="articleId" value=$submission->getArticleId()}
		{assign var="onclick" value="onclick=\"javascript:loadUrl('$requestPageUrl/submissionEditing/$articleId');\""}
		<tr class="{cycle values="row,rowAlt"}" {$onclick}>
			<td width="10%" align="center">{assign var="editAssignment" value=$submission->getEditor()}{$editAssignment->getEditorFullName()}</td>
			<td width="9%" align="center">{$submission->getDateSubmitted()|date_format:$dateMonthDay}</td>
			<td width="6%" align="center">{$submission->getSectionAbbrev()}</td>
			<td>
				{foreach from=$submission->getAuthors() item=author name=authorList}
					{$author->getLastName()}{if !$smarty.foreach.authorList.last},{/if}
				{/foreach}
			</td>
			<td width="10%" align="center">{if $submission->getCopyeditorDateFinalCompleted()}{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
			<td width="10%" align="center">{if $layoutAssignment->getDateCompleted()}{$layoutAssignment->getDateCompleted()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
			<td width="10%" align="center">{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
		</tr>
	</table>
</div>

{foreachelse}

<div class="hitlistNoRecords">
{translate key="editor.submissions.noSubmissions"}
</div>

{/foreach}
