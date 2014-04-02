{**
 * plugins/generic/browse/templates/searchIndex.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display journal browse objects (sections or identify types)
 *
 *}
{if $enableBrowseBySections}
{assign var="pageTitle" value="plugins.generic.browse.search.sectionIndex"}
{else if $enableBrowseByIdentifyTypes}
{assign var="pageTitle" value="plugins.generic.browse.search.identifyTypeIndex"}
{/if}
{include file="common/header.tpl"}

<div id="results">
{if $enableBrowseBySections}
{iterate from=results key=title item=id}
<h4><a href="{url op="sections" path="view" sectionId=$id}">{$title|escape}</a></h4>
{/iterate}
{else if $enableBrowseByIdentifyTypes}
{iterate from=results item=identifyType}
<h4><a href="{url op="identifyTypes" path="view" identifyType=$identifyType}">{$identifyType|escape}</a></h4>
{/iterate}
{/if}
{if !$results->wasEmpty()}
	<br />
	{page_info iterator=$results}&nbsp;&nbsp;&nbsp;&nbsp;{page_links anchor="results" iterator=$results name="search"}
{else}
	<br />
	{translate key="search.noResults"}
{/if}
</div>

{include file="common/footer.tpl"}
