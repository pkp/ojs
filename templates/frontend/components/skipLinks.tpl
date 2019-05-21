{**
 * templates/frontend/components/skipLinks.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Add skip links to the top of the page when tabbing for screen reader accessibility
 *}
 {if $requestedPage|escape|default:"index" === 'index'}
	<div class="cmp_skip_to_content">
		<a href="#pkp_content_main">{translate key="navigation.skip.main"}</a>
		<a href="#pkp_content_nav">{translate key="navigation.skip.nav"}</a>
		<a href="#homepage_about">{translate key="navigation.skip.about"}</a>
		<a href="#announcements">{translate key="navigation.skip.announcements"}</a>
		<a href="#current_issue">{translate key="navigation.skip.issue"}</a>
		<a href="#pkp_content_footer">{translate key="navigation.skip.footer"}</a>
	</div>
{/if}
