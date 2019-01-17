{**
 * plugins/blocks/authorBios/block.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
	<div class="authorBio">
	<p>
		<em>{$author->getFullName()|escape}</em>
		{if $author->getData('orcid')}<a href="{$author->getData('orcid')|escape}" target="_blank" class="orcid"><img src="{$baseUrl}/plugins/blocks/authorBios/orcid.png" alt="orcid" /></a>{/if}
		{if $author->getUrl()}<br /><a href="{$author->getUrl()|escape:"quotes"}">{$author->getUrl()|escape}</a><br />{/if}
		{assign var=authorAffiliation value=$author->getLocalizedAffiliation()}
		{if $authorAffiliation}<br />{$authorAffiliation|escape}{/if}
		{if $author->getCountry()}<br />{$author->getCountryLocalized()|escape}{/if}
	</p>

	<p>{$author->getLocalizedBiography()|strip_unsafe_html|nl2br}</p>
	</div>
	{if !$smarty.foreach.authors.last}<div class="separator"></div>{/if}

	{/foreach}
</div>
