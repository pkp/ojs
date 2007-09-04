{**
 * proofread.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's proofreading table.
 *
 * $Id$
 *}
<a name="proofread"></a>
<h3>{translate key="submission.proofreading"}</h3>

{if $useProofreaders}
<table width="100%" class="data">
	<tr>
		<td class="label" width="20%">{translate key="user.role.proofreader"}</td>
		<td class="value" width="80%">{if $proofAssignment->getProofreaderId()}{$proofAssignment->getProofreaderFullName()|escape}{else}{translate key="common.none"}{/if}</td>
	</tr>
</table>
{/if}

<a href="{url op="viewMetadata" path=$proofAssignment->getArticleId()}" class="action" target="_new">{translate key="submission.reviewMetadata"}</a>

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
				{translate|assign:"confirmMessage" key="common.confirmComplete"}
				{url|assign:"url" op="authorProofreadingComplete" articleId=$submission->getArticleId()}
				{icon name="mail" onclick="return confirm('$confirmMessage')" url=$url}
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
        <a href="javascript:openComments('{url op="viewProofreadComments" path=$submission->getArticleId() anchor=$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
        <a href="javascript:openComments('{url op="viewProofreadComments" path=$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>
{/if}

{if $currentJournal->getLocalizedSetting('proofInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{url op="instructions" path="proof"}')" class="action">{translate key="submission.proofread.instructions"}</a>
{/if}
