{**
 * templates/frontend/pages/preprint.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view an preprint with all of it's details.
 *
 * @uses $preprint Submission This preprint
 * @uses $publication Publication The publication being displayed
 * @uses $firstPublication Publication The first published version of this preprint
 * @uses $currentPublication Publication The most recently published version of this preprint
 * @uses $section Section The journal section this preprint is assigned to
 * @uses $journal Journal The journal currently being viewed.
 * @uses $primaryGalleys array List of preprint galleys that are not supplementary or dependent
 * @uses $supplementaryGalleys array List of preprint galleys that are supplementary
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$preprint->getLocalizedTitle()|escape}

<div class="page page_article">
	{if $section}
		{include file="frontend/components/breadcrumbs_preprint.tpl" currentTitle=$section->getLocalizedTitle()}
	{else}
		{include file="frontend/components/breadcrumbs_preprint.tpl" currentTitleKey="common.publication"}
	{/if}

	{* Show preprint overview *}
	{include file="frontend/objects/preprint_details.tpl"}

	{call_hook name="Templates::Preprint::Footer::PageFooter"}

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
