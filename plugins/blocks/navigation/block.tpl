{**
 * plugins/blocks/navigation/block.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- navigation links.
 *
 *}
{if !$currentJournal || $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
<div class="block" id="sidebarNavigation">
	<span class="blockTitle">{translate key="plugins.block.navigation.journalContent"}</span>

	<span class="blockSubtitle">{translate key="navigation.search"}</span>
	<script>
		$(function() {ldelim}
			// Attach the form handler.
			$('#simpleSearchForm').pkpHandler('$.pkp.controllers.form.FormHandler');
		{rdelim});
	</script>
	<form class="pkp_form" id="simpleSearchForm" method="post" action="{url router=$smarty.const.ROUTE_PAGE page="search" op="search"}">
		<table id="simpleSearchInput">
			<tr>
				<td>
				{capture assign="filterInput"}{call_hook name="Templates::Search::SearchResults::FilterInput" filterName="simpleQuery" filterValue="" size=15}{/capture}
				{if empty($filterInput)}
					<input type="text" id="simpleQuery" name="simpleQuery" size="15" maxlength="255" value="" class="textField" />
				{else}
					{$filterInput}
				{/if}
				</td>
			</tr>
			<tr>
				<td><select id="searchField" name="searchField" size="1" class="selectMenu">
					{html_options_translate options=$articleSearchByOptions}
				</select></td>
			</tr>
			<tr>
				<td><input type="submit" value="{translate key="common.search"}" class="button" /></td>
			</tr>
		</table>
	</form>

	<br />

	{if $currentJournal}
	<span class="blockSubtitle">{translate key="navigation.browse"}</span>
	<ul>
		<li><a href="{url router=$smarty.const.ROUTE_PAGE page="issue" op="archive"}">{translate key="navigation.browseByIssue"}</a></li>
		<li><a href="{url router=$smarty.const.ROUTE_PAGE page="search" op="authors"}">{translate key="navigation.browseByAuthor"}</a></li>
		{call_hook name="Plugins::Blocks::Navigation::BrowseBy"}
		{if $hasOtherJournals}
			<li><a href="{url router=$smarty.const.ROUTE_PAGE journal="index"}">{translate key="navigation.otherJournals"}</a></li>
			{if $siteCategoriesEnabled}<li><a href="{url router=$smarty.const.ROUTE_PAGE journal="index" page="search" op="categories"}">{translate key="navigation.categories"}</a></li>{/if}
		{/if}
	</ul>
	{/if}
</div>
{/if}
