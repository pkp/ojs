{**
 * submissionsInEditing.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show editor's submissions in editing.
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr>
		<td colspan="9" class="headseparator"></td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="submissions.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="15%">{translate key="submissions.authors"}</td>
		<td width="25%">{translate key="submissions.title"}</td>
		<td width="10%">{translate key="submissions.copyedit"}</td>
		<td width="10%">{translate key="submissions.layout"}</td>
		<td width="10%">{translate key="submissions.proof"}</td>
		<td width="5%">{translate key="submissions.sectionEditor"}</td>
	</tr>
	<tr>
		<td colspan="9" class="headseparator"></td>
	</tr>
	
	{foreach name="submissions" from=$submissions item=submission}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		<td><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}" class="action">{$submission->getTitle()|truncate:40:"..."}</a></td>
		<td>{if $submission->getCopyeditorDateFinalCompleted()}{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{if $layoutAssignment->getDateCompleted()}{$layoutAssignment->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{assign var="editAssignment" value=$submission->getEditor()}{$editAssignment->getEditorLastName()|truncate:3:""}{** FIXME Use initials **}</td>
	</tr>
	<tr>
		<td colspan="9" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="9" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="9" class="endseparator"></td>
	</tr>
	{/foreach}

</table>
