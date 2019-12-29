{**
 * templates/submission/form/complete.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * The submission process has been completed; notify the author.
 *}
<h2>{translate key="submission.submit.submissionComplete"}</h2>
<p>{translate key="submission.submit.submissionCompleteThanks" contextName=$context->getLocalizedName()}</p>
<div class="separator"></div>


<h3>{translate key="submission.submit.whatNext"}</h3>

{if $authorCanNotPublish || $errors}
	{if $authorCanNotPublish}
		<p>{translate key="author.submit.authorCanNotPublish"}</p>
	{/if}
	{if $errors}
		<p>{translate key="author.submit.publishRequirements"}</p>
		{$errors}
	{/if}	
	<p>{translate key="submission.submit.whatNext.forNow"}</p>
	<ul class="plain">
		<li><a href={$reviewSubmissionUrl}>{translate key="submission.submit.whatNext.review"}</a></li>
		<li><a href={url page="submission" op="wizard"}>{translate key="submission.submit.whatNext.create"}</a></li>
		<li><a href={url page="submissions" anchor="submissions"}>{translate key="submission.submit.whatNext.return"}</a></li>
	</ul>
{else}
		<p>{translate key="author.submit.authorCanPublish"}</p>

		<a id="proceedToPublish" href="{$reviewSubmissionUrl}" class="pkp_button">
			<span class="fa fa-arrow-right" aria-hidden="true"></span>
			{translate key="author.submit.proceedToPublish"}
		</a>
{/if}



