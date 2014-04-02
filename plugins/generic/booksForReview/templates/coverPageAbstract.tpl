{**
 * @file plugins/generic/booksForReview/templates/coverPageAbstract.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display book cover image as article cover image in article view.
 *
 *}
{if !$article->getFileName($locale) && !$article->getHideCoverPageAbstract($locale) && $book->getFileName($locale)}
	<div class="articleCoverImage">
	<img src="{$baseCoverPagePath|escape}{$book->getFileName($locale)|escape}"{if $book->getCoverPageAltText($locale) != ''} alt="{$book->getCoverPageAltText($locale)|escape}"{else} alt="{translate key="plugins.generic.booksForReview.public.coverPage.altText"}"{/if}/>
	</div>
{/if}
