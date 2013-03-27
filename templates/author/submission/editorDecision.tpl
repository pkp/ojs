{**
 * peerReview.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's editor decision table.
 *
 * $Id$
 *}
<div id="editorDecision">
<br />
<table width="100%">
        <tr>
                <td style="background: yellow; padding: 15px;">
			<p>Authors:</p>
			<ul>
				<li>Click the blue speech bubble icon below to access your decision letter and reviewer comments. Additional files may also be available in the Peer Review section below.</li>
				<li> You may upload a revised draft in the File Versions for Revision Rounds section. Be sure to notify the editor when uploading a new version of your manuscript.</li>
			</ul>
			<p> More detailed information can be found in <a href="https://vimeo.com/33303895" target="_blank">this help video</a>.</p>
		</td>
        </tr>
</table>
<h3>{translate key="submission.editorDecisionReviewerComments"}</h3>

{assign var=authorFiles value=$submission->getAuthorFileRevisions($submission->getCurrentRound())}
{assign var=editorFiles value=$submission->getEditorFileRevisions($submission->getCurrentRound())}

<table width="100%" class="data">
	<tr valign="top">
		<td class="label">{translate key="editor.article.decision"}</td>
		<td>
			{if $lastEditorDecision}
				{assign var="decision" value=$lastEditorDecision.decision}
				{translate key=$editorDecisionOptions.$decision} {$lastEditorDecision.dateDecided|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.notifyEditor"}
		</td>
		<td class="value" width="80%">
			{url|assign:"notifyAuthorUrl" op="emailEditorDecisionComment" articleId=$submission->getArticleId()}
			{icon name="mail" url=$notifyAuthorUrl}
			&nbsp;&nbsp;&nbsp;&nbsp;
			{translate key="submission.editorAuthorRecord"}
			{if $submission->getMostRecentEditorDecisionComment()}
				{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
				<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getArticleId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getArticleId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
			{/if}
		</td>
	</tr>
{** 2013-03-20 eSchol (BLH): Moved code for displaying file versions to a separate file: templates/author/submission/fileVersions.tpl so that we can split things up and move them around on the page. Sections were not making sense as was.**}
</table>
</div>
