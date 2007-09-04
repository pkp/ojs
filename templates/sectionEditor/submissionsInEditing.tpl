{**
 * submissionsInEditing.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show section editor's submissions in editing.
 *
 * $Id$
 *}
<a name="submissions"></a>

<table width="100%" class="listing">
	<tr><td colspan="8" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.sec"}</td>
		<td width="20%">{translate key="article.authors"}</td>
		<td width="25%">{translate key="article.title"}</td>
		<td width="10%">{translate key="submission.copyedit"}</td>
		<td width="10%">{translate key="submission.layout"}</td>
		<td width="10%">{translate key="submissions.proof"}</td>
	</tr>
	<tr><td colspan="8" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}

	{assign var="layoutAssignment" value=$submission->getLayoutAssignment()}
	{assign var="proofAssignment" value=$submission->getProofAssignment()}
	{assign var="articleId" value=$submission->getArticleId()}
	<tr valign="top">
		<td>{$submission->getArticleId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getSectionAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionEditing" path=$articleId}" class="action">{$submission->getArticleTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
		<td>{$submission->getCopyeditorDateFinalCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
		<td>{$layoutAssignment->getDateCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatTrunc|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="8" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="8" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="8" class="endseparator">&nbsp;</td>
	<tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="4" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth section=$section}</td>
	</tr>
{/if}
</table>
