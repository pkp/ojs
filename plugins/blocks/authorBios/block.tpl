{**
 * plugins/blocks/authorBios/block.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Keyword cloud block plugin
 *
 *}

<div class="block" id="sidebarRTAuthorBios">
	<span class="blockTitle">
		{if count($article->getAuthors()) gt 1}
			{translate key="plugins.block.authorBios.aboutTheAuthors"}
		{else}
			{translate key="plugins.block.authorBios.aboutTheAuthor"}
		{/if}
	</span>
	{foreach from=$article->getAuthors() item=author name=authors}
	<div id="authorBio">
	<p>
		<em>{$author->getFullName()|escape}</em><br />
		{if $author->getData('orcid')}<a href="{$author->getData('orcid')|escape}" target="_blank">{translate key="user.orcid"}</a>{/if}
		{if $author->getUrl()}<a href="{$author->getUrl()|escape:"quotes"}">{$author->getUrl()|escape}</a><br/>{/if}
		{assign var=authorAffiliation value=$author->getLocalizedAffiliation()}
		{if $authorAffiliation}{$authorAffiliation|escape}{/if}
		{if $author->getCountry()}<br/>{$author->getCountryLocalized()|escape}{/if}
	</p>

	<p>{$author->getLocalizedBiography()|strip_unsafe_html|nl2br}</p>
	</div>
	{if !$smarty.foreach.authors.last}<div class="separator"></div>{/if}

	{/foreach}
</div>
