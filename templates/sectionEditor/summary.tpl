{**
 * submission.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of a submission.
 *
 * FIXME: Editor decision values need to be localized.
 * DO: Reviewer comments need to be implemented.
 *
 * $Id$
 *}

{assign var="pageTitle" value="submission.submission"}
{assign var="pageId" value="sectionEditor.summary"}
{include file="common/header.tpl"}

<ul id="tabnav">
	<li><a href="{$requestPageUrl}/summary/{$submission->getArticleId()}" class="active">{translate key="submission.summary"}</a></li>
	<li><a href="{$requestPageUrl}/submission/{$submission->getArticleId()}">{translate key="submission.submission"}</a></li>
	<li><a href="{$requestPageUrl}/submissionReview/{$submission->getArticleId()}" >{translate key="submission.submissionReview"}</a></li>
	<li><a href="{$requestPageUrl}/submissionEditing/{$submission->getArticleId()}">{translate key="submission.submissionEditing"}</a></li>
	<li><a href="{$requestPageUrl}/submissionHistory/{$submission->getArticleId()}">{translate key="submission.submissionHistory"}</a></li>
</ul>
<ul id="subnav">
</ul>

<div class="tableContainer">
<table width="100%">
<tr class="heading">
	<td>{translate key="submission.submission"}</td>
</tr>
<tr>
	<td>
		<table class="plain" width="100%">
			<tr>
				<td>
					<div>{translate key="article.submissionId"}: {$submission->getArticleId()}</div>
					<div>{translate key="article.title"}: <strong>{$submission->getArticleTitle()}</strong></div>
					<div>{translate key="article.authors"}: {foreach from=$submission->getAuthors() item=author key=key}{if $key neq 0},{/if} {$author->getFullName()}{/foreach}</div>
					<div>{translate key="article.section"}: {$submission->getSectionTitle()}</div>
					<div>{translate key="article.sectionEditor"}: {if $editor}{$editor->getEditorFullName()}{else}{translate key="common.none"}{/if}
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</div>
{include file="common/footer.tpl"}
