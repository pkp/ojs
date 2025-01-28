{**
 * templates/frontend/pages/submissions.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view the editorial team.
 *
 * @uses $currentContext Journal|Press The current journal or press
 * @uses $submissionChecklist string Requirements for submissions
 *}
{include file="frontend/components/header.tpl" pageTitle="about.submissions"}

<main class="container main__content" id="immersion_content_main">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				<h1 class="main__title">
					<span>{translate key="about.submissions"}</span>
				</h1>
			</header>

			{* Login/register prompt *}
			{if $isUserLoggedIn}
				{capture assign="newSubmission"}<a
					href="{url page="submission" op="wizard"}">{translate key="about.onlineSubmissions.newSubmission"}</a>{/capture}
				{capture assign="viewSubmissions"}<a
					href="{url page="submissions"}">{translate key="about.onlineSubmissions.viewSubmissions"}</a>{/capture}
				<div class="cmp_notification">
					{translate key="about.onlineSubmissions.submissionActions" newSubmission=$newSubmission viewSubmissions=$viewSubmissions}
				</div>
			{else}
				{capture assign="login"}<a
					href="{url page="login"}">{translate key="about.onlineSubmissions.login"}</a>{/capture}
				{capture assign="register"}<a
					href="{url page="user" op="register"}">{translate key="about.onlineSubmissions.register"}</a>{/capture}
				<div class="cmp_notification">
					{translate key="about.onlineSubmissions.registrationRequired" login=$login register=$register}
				</div>
			{/if}

			{if $currentContext->getLocalizedData('authorGuidelines')}
				<div class="author_guidelines" id="authorGuidelines">
					<h2>
						{translate key="about.authorGuidelines"}
						{include file="frontend/components/editLink.tpl" page="management" op="settings" path="workflow" anchor="submission/instructions" sectionTitleKey="about.authorGuidelines"}
					</h2>
					{$currentContext->getLocalizedData('authorGuidelines')}
				</div>
			{/if}

			{if $submissionChecklist}
				<div class="submission_checklist">
					<h2>
						{translate key="about.submissionPreparationChecklist"}
						{include file="frontend/components/editLink.tpl" page="management" op="settings" path="workflow" anchor="submission/instructions" sectionTitleKey="about.submissionPreparationChecklist"}
					</h2>
					{$submissionChecklist}
				</div>
			{/if}

			{foreach from=$sections item="section"}
				{if $section->getLocalizedPolicy()}
					<div class="section_policy">
						<h2>{$section->getLocalizedTitle()|escape}</h2>
						{$section->getLocalizedPolicy()}
						{if $isUserLoggedIn}
							{capture assign="sectionSubmissionUrl"}{url page="submission" op="wizard" sectionId=$section->getId()}{/capture}
							<p>
                                {translate key="about.onlineSubmissions.submitToSection" name=$section->getLocalizedTitle() url=$sectionSubmissionUrl}
							</p>
						{/if}
					</div>
				{/if}
			{/foreach}

			{if $currentContext->getLocalizedData('copyrightNotice')}
				<div class="copyright_notice">
					<h2>
						{translate key="about.copyrightNotice"}
						{include file="frontend/components/editLink.tpl" page="management" op="settings" path="workflow" anchor="submission/authorGuidelines" sectionTitleKey="about.copyrightNotice"}
					</h2>
					{$currentContext->getLocalizedData('copyrightNotice')}
				</div>
			{/if}

			{if $currentContext->getLocalizedData('privacyStatement')}
				<div class="privacy_statement">
					<h2>
						{translate key="about.privacyStatement"}
						{include file="frontend/components/editLink.tpl" page="management" op="settings" path="website" anchor="setup/privacy" sectionTitleKey="about.privacyStatement"}
					</h2>
					{$currentContext->getLocalizedData('privacyStatement')}
				</div>
			{/if}
		</div>
	</div> <!-- .row -->
</main><!-- .main__content -->

{include file="frontend/components/footer.tpl"}
