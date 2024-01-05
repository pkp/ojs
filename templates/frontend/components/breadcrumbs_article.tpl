{**
 * templates/frontend/components/breadcrumbs_article.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display a breadcrumb nav item showing the current page. This basic
 *  version is for top-level pages which only need to show the Home link. For
 *  category- and series-specific breadcrumb generation, see
 *  templates/frontend/components/breadcrumbs_catalog.tpl.
 *
 * @uses $currentTitle string The title to use for the current page.
 * @uses $currentTitleKey string Translation key for title of current page.
 * @uses $issue Issue Issue this article was published in.
 *}
{assign var=articlePath value=$publication->getData('urlPath')|default:$article->getId()}
<nav class="cmp_breadcrumbs" aria-label="{translate key="navigation.breadcrumbLabel"}">
	<ol>
		<li>
			<a href="{url page="index" router=\PKP\core\PKPApplication::ROUTE_PAGE}">
				{translate key="common.homepageNavigationLabel"}
			</a>
			<span class="separator" aria-hidden="true">{translate key="navigation.breadcrumbSeparator"}</span>
		</li>
		<li>
			<a href="{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="issue" op="archive"}">
				{translate key="navigation.archives"}
			</a>
			<span class="separator" aria-hidden="true">{translate key="navigation.breadcrumbSeparator"}</span>
		</li>
		{if $issue}
			<li>
				<a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
					{$issue->getIssueIdentification()}
				</a>
				<span class="separator" aria-hidden="true">{translate key="navigation.breadcrumbSeparator"}</span>
			</li>
		{/if}
		<li class="current">
			<a href="{url page="article" op="view" path=$articlePath}" current="page">
				{if $currentTitleKey}
					{translate key=$currentTitleKey}
				{else}
					{$publication->getLocalizedTitle(null, 'html')|strip_unsafe_html}
				{/if}
			</a>
		</li>
	</ol>
</nav>
