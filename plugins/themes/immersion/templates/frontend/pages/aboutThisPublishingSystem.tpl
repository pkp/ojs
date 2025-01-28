{**
 * templates/frontend/pages/aboutThisPublishingSystem.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view details about the OJS software.
 *
 * @uses $currentContext Journal The journal currently being viewed
 * @uses $appVersion string Current version of OJS
 * @uses $contactUrl string URL to the journal's contact page
 *}
{include file="frontend/components/header.tpl" pageTitle="about.aboutSoftware"}

<main class="container main__content" id="immersion_content_main">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				<h1 class="main__title">
					<span>{translate key="about.aboutSoftware"}</span>
				</h1>
			</header>

			<div class="content-body">
				<p>
					{if $currentContext}
						{translate key="about.aboutOJSJournal" ojsVersion=$appVersion contactUrl=$contactUrl}
					{else}
						{translate key="about.aboutOJSSite" ojsVersion=$appVersion}
					{/if}
				</p>
			</div>
		</div>
	</div>
</main>

{include file="frontend/components/footer.tpl"}
