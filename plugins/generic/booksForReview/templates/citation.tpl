{**
 * @file plugins/generic/booksForReview/templates/citation.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Book for review citation.
 *
 *}
{assign var=authors value=$book->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
{assign var=firstName value=$author->getFirstName()}
{$author->getLastName()|escape}, {$firstName|escape|truncate:1:"":true}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
{/foreach}
{if $book->getAuthorType() == $smarty.const.BFR_AUTHOR_TYPE_EDITED_BY} ({translate key="plugins.generic.booksForReview.authorType.editedByShort"}){/if} ({$book->getYear()|escape}). {$book->getLocalizedTitle()|escape}.{if $book->getEdition()} ({$book->getEdition()|escape} {translate key="plugins.generic.booksForReview.editionShort"}).{/if} {$book->getPublisher()|escape}.
