{**
 * templates/article/citations.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Citations list for an article.
 *
 * Available data:
 *  $article Article The article object for the current article view
 *  $galley ArticleGalley The (optional!) galley object for the current view
 *  $galleys array The list of galleys available for this article
 *}
{if $citationFactory->getCount()}
	<div id="articleCitations">
	<h4>{translate key="submission.citations"}</h4>
	<br />
	<div>
		{iterate from=citationFactory item=citation}
			<p>{$citation->getRawCitation()|strip_unsafe_html}</p>
		{/iterate}
	</div>
	<br />
	</div>
{/if}
