{**
 * templates/frontend/components/skipLinks.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Skip links to aid navigation when tabbing for screen reader accessibility
 *}

 <nav class="cmp_skip_to_content" aria-label="{translate key="navigation.skip.description"}">
	<a href="#pkp_content_main">{translate key="navigation.skip.main"}</a>
	<a href="#siteNav">{translate key="navigation.skip.nav"}</a>
	{if !$requestedPage || $requestedPage === 'index'}
		{if $activeTheme && $activeTheme->getOption('showDescriptionInJournalIndex')}
			<a href="#homepageAbout">{translate key="navigation.skip.about"}</a>
		{/if}
		{if $numAnnouncementsHomepage && $announcements|@count}
			<a href="#homepageAnnouncements">{translate key="navigation.skip.announcements"}</a>
		{/if}
		{if $issue}
			<a href="#homepageIssue">{translate key="navigation.skip.issue"}</a>
		{/if}
	{/if}
	<a href="#pkp_content_footer">{translate key="navigation.skip.footer"}</a>
</nav>
