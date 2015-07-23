{**
 * templates/header/search.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common search box.
 *}
{if !$currentJournal || $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
	<script type="text/javascript">
		$(function() {ldelim}
			$('#topSearchFormField').jLabel();
		{rdelim});
	</script>

	<form id="topSearchForm" class="pkp_search" action="{url page="search" op="search"}" method="post">
		<input id="topSearchFormField" name="query" value="{$searchQuery|escape}" type="text" title="{translate key="common.search"}..." />
		<button class="go">{translate key="common.go"}</button>
	</form>
{/if}
