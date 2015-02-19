{**
 * @file plugins/generic/booksForReview/templates/metadata.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Supplemental Dublin Core book for review metadata.
 *
 *}
<meta name="DC.Relation" scheme="isReviewOf" content="{$book->getLocalizedTitle()|escape}"/>
{if $book->getISBN()}
	<meta name="DC.Relation" scheme="isReviewOf" content="ISBN {$book->getISBN()|escape}"/>
	<meta name="DC.Relation" scheme="References" content="ISBN {$book->getISBN()|escape}"/>
{/if}
	<meta name="DC.Relation" scheme="References" content="{$citation|escape}"/>
