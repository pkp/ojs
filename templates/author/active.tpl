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

<div id="hitlistTitles">
	<table>
		<tr>
			<td width="5%" align="center">{translate key="submissions.id"}</td>
			<td width="12%" align="center">{translate key="submissions.submitted"}</td>
			<td width="6%" align="center">{translate key="submissions.sec"}</td>
			<td align="center">{translate key="submissions.authors"}</td>
			<td width="35%" align="center">{translate key="submissions.title"}</td>
			<td width="12%" align="center">{translate key="submissions.status"}</td>
		</tr>
	</table>
</div>

{foreach from=$submissions item=submission}
<div class="hitlistRecord">
	<table>
		{assign var="articleId" value=$submission->getArticleId()}
		{assign var="progress" value=$submission->getSubmissionProgress()}

		<tr class="{cycle values="row,rowAlt"}">
			{if $progress == 0}
				<td width="5%" align="center"><a href="{$requestPageUrl}/submission/{$articleId}">{$articleId}</a></td>
			{else}
				<td width="5%" align="center"><a href="{$pageUrl}/author/submit/{$progress}?articleId={$articleId}">{$articleId}</a></td>
			{/if}
			<td width="12%" align="center">{if $submission->getDateSubmitted()}{$submission->getDateSubmitted()|date_format:$dateFormatShort}{else}&mdash;{/if}</td>
			<td width="6%" align="center">{$submission->getSectionAbbrev()}</td>
			<td>
				{foreach from=$submission->getAuthors() item=author name=authorList}
					{$author->getLastName()}{if !$smarty.foreach.authorList.last},{/if}
				{/foreach}
			</td>
			{if $progress == 0}
				<td width="35%"><a href="{$requestPageUrl}/submission/{$articleId}">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
				<td width="12%" align="center">
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
				<td width="35%"><a href="{$pageUrl}/author/submit/{$progress}?articleId={$articleId}">{$submission->getArticleTitle()|truncate:60:"..."}</a></td>
				<td width="12%" align="center">{translate key="submissions.incomplete"}<br /><a href="{$pageUrl}/author/deleteSubmission/{$articleId}" onclick="return confirm('{translate|escape:"javascript" key="author.submissions.confirmDelete"}')" class="tableAction">{translate key="common.delete"}</a></td>
			{/if}

		</tr>
	</table>
</div>

{foreachelse}

<div class="hitlistNoRecords">
{translate key="editor.submissions.noSubmissions"}
</div>

{/foreach}
