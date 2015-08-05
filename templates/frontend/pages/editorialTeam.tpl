{**
 * templates/frontend/pages/editorialTeam.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view the editorial team.
 *
 * @uses $currentJournal Journal The current journal
 *}
{include file="common/frontend/header.tpl" pageTitle="about.editorialTeam"}

<div class="page page_editorial_team">
	<h1 class="page_title">
		{translate key="about.editorialTeam"}
	</h1>
	{$currentJournal->getLocalizedSetting('masthead')}
</div><!-- .page -->

{include file="common/frontend/footer.tpl"}
