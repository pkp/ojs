{**
 * proofread.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the proofreading table.
 *
 * $Id$
 *}

<a name="proofread"></a>
<h3>{translate key="submission.proofreading"}</h3>

{if $useProofreaders}
<p>{translate key="user.role.proofreader"}:
{if $proofAssignment->getProofreaderId()}&nbsp; {$proofAssignment->getProofreaderFullName()}{/if}
&nbsp; <a href="{$requestPageUrl}/selectProofreader/{$submission->getArticleId()}" class="action">{translate key="editor.article.selectProofreader"}</a></p>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="28%" colspan="2"></td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="18%" class="heading">{translate key="submission.underway"}</td>
		<td width="18%" class="heading">{translate key="submission.complete"}</td>
		<td width="18%" class="heading">{translate key="submission.acknowledge"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="23%">{translate key="submission.proofread.authorProof"}</td>
		<td>
			{if !$proofAssignment->getDateAuthorCompleted()}
				{icon name="mail" url="$requestPageUrl/notifyAuthorProofreader?articleId=`$submission->getArticleId()`"}
			{else}
				{icon name="mail" disabled="disable"}
			{/if}
			{$proofAssignment->getDateAuthorNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
				{$proofAssignment->getDateAuthorUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{$proofAssignment->getDateAuthorCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{if $proofAssignment->getDateAuthorCompleted() && !$proofAssignment->getDateAuthorAcknowledged()}
				{icon name="mail" url="$requestPageUrl/thankCopyeditor?articleId=`$submission->getArticleId()`"}
			{else}
				{icon name="mail" disabled="disable"}
			{/if}
			{$proofAssignment->getDateAuthorAcknowledged()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td width="5%">2.</td>
		<td width="23%">{translate key="submission.proofread.proofreadProof"}</td>
		<td>
			{if $useProofreaders}
				{if $proofAssignment->getProofreaderId() && $proofAssignment->getDateAuthorCompleted() && !$proofAssignment->getDateProofreaderCompleted()}
					{icon name="mail" url="$requestPageUrl/notifyProofreader?articleId=`$submission->getArticleId()`"}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
			{else}
				{if !$proofAssignment->getDateProofreaderNotified()}
					<a href="{$requestPageUrl}/editorInitiateProofreader?articleId={$submission->getArticleId()}" class="action">{translate key="editor.article.initiate"}</a>
				{/if}
			{/if}
			{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
			{if $useProofreaders}
					{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if !$useProofreaders && !$proofAssignment->getDateProofreaderCompleted() && $proofAssignment->getDateProofreaderNotified()}
				<a href="{$requestPageUrl}/editorCompleteProofreader/articleId?articleId={$submission->getArticleId()}" class="action">{translate key="submission.complete"}</a>
			{/if}
			{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{if $useProofreaders}
				{if $proofAssignment->getDateProofreaderCompleted() && !$proofAssignment->getDateProofreaderAcknowledged()}
					{icon name="mail" url="$requestPageUrl/thankProofreader?articleId=`$submission->getArticleId()`"}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$proofAssignment->getDateAuthorAcknowledged()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
	</tr>
	<tr>
		<td width="5%">3.</td>
		<td width="23%">{translate key="submission.proofread.layoutProof"}</td>
		<td>
			{if $useLayoutEditors}
				{if $layoutAssignment->getEditorId() && $proofAssignment->getDateProofreaderCompleted() && !$proofAssignment->getDateLayoutEditorCompleted()}
					{icon name="mail" url="$requestPageUrl/notifyLayoutEditorProofreader?articleId=`$submission->getArticleId()`"}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
			{else}
				{if !$proofAssignment->getDateLayoutEditorNotified()}
					<a href="{$requestPageUrl}/editorInitiateLayoutEditor?articleId={$submission->getArticleId()}" class="action">{translate key="editor.article.initiate"}</a>
				{/if}
			{/if}
				{$proofAssignment->getDateLayoutEditorNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
			{if $useLayoutEditors}
				{$proofAssignment->getDateLayoutEditorUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{if $useLayoutEditors}
				{if $proofAssignment->getDateLayoutEditorCompleted() && !$proofAssignment->getDateLayoutEditorAcknowledged()}
					{icon name="mail" url="$requestPageUrl/thankLayoutEditorProofreader?articleId=`$submission->getArticleId()`"}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$proofAssignment->getDateLayoutEditorAcknowledged()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
	</tr>
</table>

<p>{translate key="submission.proofread.corrections"}
{if $submission->getMostRecentProofreadComment()}
	{assign var="comment" value=$submission->getMostRecentProofreadComment()}
	<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}#{$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{$requestPageUrl}/viewProofreadComments/{$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}</p>

<div class="separator"></div>

{if $proofAssignment->getDateSchedulingQueue()}
{translate key="editor.article.placeSubmissionInSchedulingQueue"} {$proofAssignment->getDateSchedulingQueue()|date_format:$dateFormatShort}
{else}
<form method="post" action="{$requestPageUrl}/queueForScheduling/{$submission->getArticleId()}">
{translate key="editor.article.placeSubmissionInSchedulingQueue"} 
<input type="submit" value="{translate key="editor.article.scheduleSubmission"}"{if !$submissionAccepted} disabled="disabled"{/if} class="button defaultButton" />
</form>
{/if}
