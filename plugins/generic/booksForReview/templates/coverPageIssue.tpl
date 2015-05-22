{**
 * @file plugins/generic/booksForReview/templates/coverPageIssue.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display book cover image as article cover image in issue table of contents.
 *
 *}
{if !$article->getFileName($locale) && !$article->getHideCoverPageToc($locale) && $book->getFileName($locale)}
	<td rowspan="2">
		<div class="tocArticleCoverImage">
		<a href="{url page="article" op="view" path=$articlePath}" class="file">
		<img src="{$coverPagePath|escape}{$book->getFileName($locale)|escape}"{if $book->getCoverPageAltText($locale) != ''} alt="{$book->getCoverPageAltText($locale)|escape}"{else} alt="{translate key="plugins.generic.booksForReview.public.coverPage.altText"}"{/if}/></a>
		</div>
	</td>
{/if}
