{**
 * block.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- navigation links.
 *
 * $Id$
 *}
<div class="block" id="sidebarNavigation">
	<span class="blockTitle">{translate key="plugins.block.navigation.journalContent"}</span>
	
	<span class="blockSubtitle">{translate key="navigation.search"}</span>
	<form method="post" action="{url page="search" op="results"}">
	<table>
	<tr>
		<td><input type="text" id="query" name="query" size="15" maxlength="255" value="" class="textField" /></td>
	</tr>
	<tr>
		<td><select name="searchField" size="1" class="selectMenu">
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
		<li><a href="{url page="issue" op="archive"}">{translate key="navigation.browseByIssue"}</a></li>
		<li><a href="{url page="search" op="authors"}">{translate key="navigation.browseByAuthor"}</a></li>
		<li><a href="{url page="search" op="titles"}">{translate key="navigation.browseByTitle"}</a></li>
		{if $hasOtherJournals}
		<li><a href="{url journal="index"}">{translate key="navigation.otherJournals"}</a></li>
		{/if}
	</ul>
	{/if}
</div>
