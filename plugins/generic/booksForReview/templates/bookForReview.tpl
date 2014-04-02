{**
 * @file plugins/generic/booksForReview/templates/bookForReview.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Detailed book for review view.
 *
 *}
{assign var="pageTitle" value=plugins.generic.booksForReview.public.bookDetails}
{include file="common/header.tpl"}

<br/>

<div id="bookForReviewDetails">

<table class="bookForReviewDetails">
	<tr>
		{if $bookForReview->getFileName($locale)}
			<td class="coverPage"><br /><img src="{$coverPagePath|escape}{$bookForReview->getFileName($locale)|escape}"{if $bookForReview->getCoverPageAltText($locale) != ''} alt="{$bookForReview->getCoverPageAltText($locale)|escape}"{else} alt="{translate key="plugins.generic.booksForReview.public.coverPage.altText"}"{/if}/></td>
		{else}
			<td class="coverPage">&nbsp;</td>
		{/if}
		<td class="coverDetails">
			<h3>{$bookForReview->getLocalizedTitle()|escape}</h3>
			{translate key=$bookForReview->getAuthorTypeString()}&nbsp;{$bookForReview->getAuthorString()|escape}
			<br />
			<br />
			{$bookForReview->getYear()|escape}&nbsp;|&nbsp;{if $bookForReview->getUrl()}<a href="{$bookForReview->getUrl()|escape}" target="_blank">{$bookForReview->getPublisher()|escape}</a>&nbsp;({translate key="plugins.generic.booksForReview.public.externalSite"}){else}{$bookForReview->getPublisher()|escape}{/if}
			<br />
			{translate key="plugins.generic.booksForReview.public.copy"}:&nbsp;{if $bookForReview->getCopy()}{translate key="plugins.generic.booksForReview.public.copyYes"}{else}{translate key="plugins.generic.booksForReview.public.copyNo"}{/if}
			<br />
			<br />
			{translate key="plugins.generic.booksForReview.public.language"}:&nbsp;{$bookForReview->getLanguageString()|escape}
			<br />
			{if $bookForReview->getEdition()}
				{translate key="plugins.generic.booksForReview.public.edition"}:&nbsp;{$bookForReview->getEdition()|escape}
				<br />
			{/if}
			{if $bookForReview->getPages()}
				{translate key="plugins.generic.booksForReview.public.pages"}:&nbsp;{$bookForReview->getPages()|escape}
				<br />
			{/if}
			{if $bookForReview->getISBN()}
				{translate key="plugins.generic.booksForReview.public.isbn"}:&nbsp;{$bookForReview->getISBN()|escape}
				<br />
			{/if}
			{if $isAuthor}
				<br />
				<a href="{url page="author" op="requestBookForReview" path=$bookForReview->getId()}" class="action">{translate key="plugins.generic.booksForReview.author.requestBookForReview}</a>
			{/if}
			<br />
			<br />
			{$bookForReview->getLocalizedDescription()|strip_unsafe_html|nl2br}
		</td>
	</tr>
</table>

</div>


{include file="common/footer.tpl"}
