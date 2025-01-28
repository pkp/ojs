{**
 * templates/frontend/pages/issue.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display a landing page for a single issue. It will show the table of contents
 *  (toc) or a cover image, with a click through to the toc.
 *
 * @uses $issue Issue The issue
 * @uses $issueIdentification string Label for this issue, consisting of one or
 *       more of the volume, number, year and title, depending on settings
 * @uses $issueGalleys array Galleys for the entire issue
 * @uses $primaryGenreIds array List of file genre IDs for primary types
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$issueIdentification|escape}

<main id="immersion_content_main">
	<section class="issue{if !$issue} issue__empty{/if}">

	{* Display a message if no current issue exists *}
	{if !$issue}
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			{include file="frontend/components/notification.tpl" type="warning" messageKey="current.noCurrentIssueDesc"}
		</div>

	{* Display an issue with the Table of Contents *}
	{else}
		{include file="frontend/objects/issue_toc.tpl"}
	{/if}
	</section>
</main>

{include file="frontend/components/footer.tpl"}
