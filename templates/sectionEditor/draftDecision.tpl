{**
 * templates/sectionEditor/draftDecision.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission review.
 *
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.review" id=$submission->getId()}{assign var="pageCrumbTitle" value="submission.review"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	<li class="current"><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	<li><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
</ul>

<h3>{translate key="editor.draft.adecision"}</h3>
<div>
	<p>{translate key="editor.draft.email"}</p>
	<form method="post" action="{url op="draftDecision" path=$submission->getId()}">
		<label for="editor">{translate key="editor.draft.emailSenior"}</label><br />
		<select id="editor" name="editor" class="selectMenu">
			{iterate from=editors item=user}
			{assign var="userid" value=$user->getId()}
			<option value="{$user->getId()}">{$user->getFullName(true)|escape}</option>
			{/iterate}
		</select>
		<br /><br />
		<label for="subject">{translate key="email.subject"}</label><br />
		<input id="subject" name="subject" value="{$title|escape}" size="60" maxlength="200" class="textField" type="text">
		<br /><br />
		<label for="body">{translate key="email.body"}</label><br />
		<textarea name="body" cols="60" rows="15" class="textArea">{$email->getBody()|escape}</textarea>
		<br /><br />
		<label for="note">{translate key="editor.draft.emailNote"}</label><br />
		<textarea name="note" cols="60" rows="5" class="textArea"></textarea>
		<input type="hidden" name="decision" value="{$decision}" />
		<input type="hidden" name="articleId" value="{$submission->getId()}" />
		<br /><br />
		<input type="submit" name="submit_draft" value="{translate key="editor.draft.record"}" class="button" />
	</form>
</div>
{include file="common/footer.tpl"}