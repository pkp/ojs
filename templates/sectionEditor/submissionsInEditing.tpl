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

<h3>{translate key="editor.submissions.activeAssignments"}</h3>
<p>{translate key="editor.submissions.sectionEditor"}:&nbsp;{$sectionEditor}</p>

<table width="100%" class="listing">
	<tr><td colspan="8" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="9%">{translate key="editor.submissions.submitMMDD"}</td>
		<td width="6%">{translate key="editor.submissions.sec"}</td>
		<td>{translate key="article.authors"}</td>
		<td width="25%">{translate key="article.title"}</td>
		<td width="10%">{translate key="editor.submissions.copyedit"}</td>
		<td width="10%">{translate key="editor.submissions.galley"}</td>
		<td width="9%">{translate key="editor.submissions.proof"}</td>
	</tr>
	<tr><td colspan="8" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}

	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}
	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submissionEditing/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
		<td>{if $submission->getCopyeditorDateFinalCompleted()}{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
		<td>{if $layoutAssignment->getDateCompleted()}{$layoutAssignment->getDateCompleted()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateMonthDay}{else}&mdash;{/if}</td>
	</tr>
	<tr>
		<td colspan="8" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="8" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="8" class="bottomseparator"></td>
	<tr>
{/foreach}
</table>
