{**
 * @file plugins/generic/objectsForReview/templates/objectsForReview.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display public list of objects available for review.
 *
 *}
{assign var="pageTitle" value="plugins.generic.objectsForReview.objectsForReview.pageTitle"}
{include file="common/header.tpl"}

<div id="ojectsForReviewListing">

{if $additionalInformation[$locale]}
	{$additionalInformation[$locale]|strip_unsafe_html|nl2br}
{/if}

{if !$isAuthor}
	{url|assign:"registerUrl" page="user" op="register"}
	{url|assign:"loginAuthorUrl" page="author"}
	{translate key="plugins.generic.objectsForReview.public.objectsForReviewInstructions" registerUrl=$registerUrl loginAuthorUrl=$loginAuthorUrl}
	<br />
{/if}

<form method="post" action="{url page="objectsForReview}">
	<input type="hidden" name="filterType" value="{$filterType|escape}" />
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$searchFieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" size="30" name="search" class="textField" value="{$search|escape}" />
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<br />

<form name="filterAndSortForm" action="#">
<ul class="filter">
	<li>{translate key="plugins.generic.objectsForReview.filter.filterBy"}: <select name="filterType" onchange="location.href='{url|escape searchField=$searchField searchMatch=$searchMatch search=$search filterType="TYPE" sort="SORT" sortDirection="SORTDIR" escape=false}'.replace('TYPE', this.options[this.selectedIndex].value).replace('SORT', document.forms.filterAndSortForm.elements.sort.value).replace('SORTDIR', document.forms.filterAndSortForm.elements.sortDirection.value)" size="1" class="selectMenu">{html_options options=$typeOptions selected=$filterType}</select></li>
	<li>{translate key="plugins.generic.objectsForReview.sort.sortBy"}: <select name="sort" id="sort" onchange="location.href='{url|escape searchField=$searchField searchMatch=$searchMatch search=$search filterType="TYPE" sort="SORT"  sortDirection="SORTDIR" escape=false}'.replace('TYPE', document.forms.filterAndSortForm.elements.filterType.value).replace('SORT', this.options[this.selectedIndex].value).replace('SORTDIR', document.forms.filterAndSortForm.elements.sortDirection.value)" size="1" class="selectMenu">{html_options options=$sortingOptions selected=$sort}</select><select name="sortDirection" id="sortDirection" onchange="location.href='{url|escape searchField=$searchField searchMatch=$searchMatch search=$search filterType="TYPE" sort="SORT"  sortDirection="SORTDIR" escape=false}'.replace('TYPE', document.forms.filterAndSortForm.elements.filterType.value).replace('SORT', document.forms.filterAndSortForm.elements.sort.value).replace('SORTDIR', this.options[this.selectedIndex].value)" size="1" class="selectMenu">{html_options options=$sortDirections selected=$sortDirection}</select></li>
</ul>
</form>

{iterate from=objectsForReview item=objectForReview}
	<div class="objectForReviewListing" style="clear:left;">

		{include file="../plugins/generic/objectsForReview/templates/objectForReviewMetadata.tpl"}

		<div class="separator" style="clear:both;"></div>
	</div>
{/iterate}

{if $objectsForReview->wasEmpty() and $search != ""}
	<br />
	{translate key="plugins.generic.objectsForReview.search.noResults"}
{elseif $objectsForReview->wasEmpty()}
	<br />
	{translate key="plugins.generic.objectsForReview.objectsForReview.noneCreated"}
{else}
	{page_info iterator=$objectsForReview}&nbsp;&nbsp;&nbsp;&nbsp;
	{page_links anchor="objectsForReview" name="objectsForReview" iterator=$objectsForReview sort=$sort sortDirection=$sortDirection filterType=$filterType searchField=$searchField searchMatch=$searchMatch search=$search}
{/if}

</div>

{include file="common/footer.tpl"}
