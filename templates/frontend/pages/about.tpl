{**
 * templates/frontend/pages/about.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view a journal's description, contact details,
 *  politics and more.
 *
 * @uses $currentJournal Journal The current journal
 * @uses $aboutJournal string HTML text about the journal
 *}
{include file="frontend/components/header.tpl" pageTitle="about.aboutTheJournal"}

<div class="page page_about">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="about.aboutTheJournal"}

	{$aboutJournal}
</div><!-- .page -->

{include file="common/frontend/footer.tpl"}
