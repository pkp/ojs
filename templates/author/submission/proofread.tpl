{**
 * proofread.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's proofreading table.
 *
 * $Id$
 *}

<a name="proofread"></a>
<h3>{translate key="submission.proofreading"}</h3>

{if $useProofreaders}
<p>{translate key="user.role.proofreader"}:
&nbsp; {if $proofAssignment->getProofreaderId()}{$proofAssignment->getProofreaderFullName()}{else}{translate key="common.none"}{/if}</p>
{/if}

{if $currentJournal->getSetting('proofInstructions')}
<h4>{translate key="submission.proofread.instructions"}</h4>
<p>{$currentJournal->getSetting('proofInstructions')|nl2br}</p>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="40%" colspan="2">&nbsp;</td>
		<td width="20%" class="heading">{translate key="submission.request"}</td>
		<td width="20%" class="heading">{translate key="submission.underway"}</td>
		<td width="20%" class="heading">{translate key="submission.complete"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="35%">{translate key="user.role.author"}</td>
		<td>{$proofAssignment->getDateAuthorNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateAuthorUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
				<td>
			{if not $proofAssignment->getDateAuthorNotified() or $proofAssignment->getDateAuthorCompleted()}
				{icon name="mail" disabled="disabled"}
			{else}
				{icon name="mail" url="$requestPageUrl/authorProofreadingComplete?articleId=`$submission->getArticleId()`"}
			{/if}
						{$proofAssignment->getDateAuthorCompleted()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td>2.</td>
		<td>{translate key="user.role.proofreader"}</td>
		<td>{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td>3.</td>
		<td>{translate key="user.role.layoutEditor"}</td>
		<td>{$proofAssignment->getDateLayoutEditorNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateLayoutEditorUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateLayoutEditorCompleted()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
</table>

{translate key="submission.proofread.corrections"}
{if $submission->getMostRecentProofreadComment()}
        {assign var="comment" value=$submission->getMostRecentProofreadComment()}
        <a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
        <a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}
