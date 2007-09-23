{**
 * submissionsInEditing.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show editor's submissions in editing.
 *
 * $Id$
 *}
<a name="submissions"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="9" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="15%">{translate key="article.authors"}</td>
		<td width="25%">{translate key="article.title"}</td>
		<td width="10%">{translate key="submission.copyedit"}</td>
		<td width="10%">{translate key="submission.layout"}</td>
		<td width="10%">{translate key="submissions.proof"}</td>
		<td width="5%">{translate key="article.sectionEditor"}</td>
	</tr>
	<tr>
		<td colspan="9" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=submissions item=submission}
	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}
	<tr valign="top" {if $submission->getFastTracked()} class="fastTracked"{/if}>
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionEditing" path=$submission->getArticleId()}" class="action">{$submission->getArticleTitle()|strip_unsafe_html|truncate:40:"..."}</a></td>
		<td>{if $submission->getCopyeditorDateFinalCompleted()}{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{if $layoutAssignment->getDateCompleted()}{$layoutAssignment->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{if $proofAssignment->getDateLayoutEditorCompleted()}{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>
			{assign var="editAssignments" value=$submission->getEditAssignments()}
			{foreach from=$editAssignments item=editAssignment}{$editAssignment->getEditorInitials()} {/foreach}
		</td>
	</tr>
	<tr>
		<td colspan="9" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="9" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="9" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="5" align="left">{page_info iterator=$submissions}</td>
		<td colspan="4" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth section=$section}</td>
	</tr>
{/if}
</table>