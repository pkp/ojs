{**
 * active.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of active submissions.
 *
 * $Id$
 *}

<table class="listing">
	<tr><td colspan="6" class="headseparator"></td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="12%">{translate key="submissions.submitted"}</td>
		<td width="6%">{translate key="submissions.sec"}</td>
		<td>{translate key="article.authors"}</td>
		<td width="35%">{translate key="article.title"}</td>
		<td width="12%">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator"></td></tr>

{foreach name=submissions from=$submissions item=submission}
	{assign var="articleId" value=$submission->getArticleId()}
	{assign var="progress" value=$submission->getSubmissionProgress()}

	<tr valign="top">
		<td>{$articleId}</td>
		<td>{if $submission->getDateSubmitted()}{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{$submission->getSectionAbbrev()}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."}</td>
		{if $progress == 0}
			<td><a href="{$requestPageUrl}/submission/{$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
			<td>
				{foreach from=$submission->getDecisions() item=decisions}
					{foreach from=$decisions item=decision name=decisionList}
						{if $smarty.foreach.decisionList.last}
							{if $decision.decision == 1}
								{translate key="submissions.editing"}										
							{else}
								{translate key="submissions.review"}										
							{/if}
						{/if}
					{foreachelse}
						{translate key="submissions.review"}
					{/foreach}
				{/foreach}
			</td>
		{else}
			<td><a href="{$pageUrl}/author/submit/{$progress}?articleId={$articleId}" class="action">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
			<td>{translate key="submissions.incomplete"}<br /><a href="{$pageUrl}/author/deleteSubmission/{$articleId}" onclick="return confirm('{translate|escape:"javascript" key="author.submissions.confirmDelete"}')">{translate key="common.delete"}</a></td>
		{/if}

	</tr>

	<tr>
		<td colspan="6" class="{if $smarty.foreach.submissions.last}end{/if}separator"></td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator"></td>
	</tr>
	{/foreach}

</table>

