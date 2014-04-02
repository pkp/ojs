{**
 * @file plugins/generic/booksForReview/templates/author/booksForReviewAssigned.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of books for author.
 *
 *}
{assign var="pageTitle" value="plugins.generic.booksForReview.author.booksForReviewAssigned"}
{include file="common/header.tpl"}

<div id="authorBooksForReview">

<ul class="menu">
	<li><a href="{url op="booksForReview" path="requested"}">{translate key="plugins.generic.booksForReview.author.requested"} ({$counts[$smarty.const.BFR_STATUS_REQUESTED]})</a></li>
	<li class="current"><a href="{url op="booksForReview" path="assigned"}">{translate key="plugins.generic.booksForReview.author.assigned"} ({$counts[$smarty.const.BFR_STATUS_ASSIGNED]})</a></li>
	<li><a href="{url op="booksForReview" path="mailed"}">{translate key="plugins.generic.booksForReview.author.mailed"} ({$counts[$smarty.const.BFR_STATUS_MAILED]})</a></li>
	<li><a href="{url op="booksForReview" path="submitted"}">{translate key="plugins.generic.booksForReview.author.submitted"} ({$counts[$smarty.const.BFR_STATUS_SUBMITTED]})</a></li>
</ul>

{include file="../plugins/generic/booksForReview/templates/author/booksForReview.tpl"}

</div>

{include file="common/footer.tpl"}
