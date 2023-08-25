{**
 * templates/frontend/pages/indexJournal.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display the index page for a journal
 *
 * @uses $currentJournal Journal This journal
 * @uses $journalDescription string Journal description from HTML text editor
 * @uses $homepageImage object Image to be displayed on the homepage
 * @uses $additionalHomeContent string Arbitrary input from HTML text editor
 * @uses $announcements array List of announcements
 * @uses $numAnnouncementsHomepage int Number of announcements to display on the
 *       homepage
 * @uses $issue Issue Current issue
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$currentJournal->getLocalizedName()}

<div class="page_index_journal">

	{call_hook name="Templates::Index::journal"}

	{if !$activeTheme->getOption('useHomepageImageAsHeader') && $homepageImage}
		<div class="homepage_image">
			<img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}"{if $homepageImage.altText} alt="{$homepageImage.altText|escape}"{/if}>
		</div>
	{/if}

	{* Journal Description *}
	{if $activeTheme->getOption('showDescriptionInJournalIndex')}
		<section class="homepage_about">
			<a id="homepageAbout"></a>
			<h2>{translate key="about.aboutContext"}</h2>
			{$currentContext->getLocalizedData('description')}
		</section>
	{/if}

	{include file="frontend/objects/announcements_list.tpl" numAnnouncements=$numAnnouncementsHomepage}

	{* Latest issue *}
	{if $issue}
		<section class="current_issue">
			<a id="homepageIssue"></a>
			<h2>
				{translate key="journal.currentIssue"}
			</h2>
			<div class="current_issue_title">
				{$issue->getIssueIdentification()|strip_unsafe_html}
			</div>
			{include file="frontend/objects/issue_toc.tpl" heading="h3"}
			<a href="{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="issue" op="archive"}" class="read_more">
				{translate key="journal.viewAllIssues"}
			</a>
		</section>
	{/if}

	{* Additional Homepage Content *}
	{if $additionalHomeContent}
		<div class="additional_content">
			{$additionalHomeContent}
		</div>
	{/if}
</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
