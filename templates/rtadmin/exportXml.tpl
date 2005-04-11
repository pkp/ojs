<?xml version="1.0"?>
<!DOCTYPE version SYSTEM "http://www.pkp.ubc.ca/DTD/rst/rst-version.dtd">
<!-- {$title|escape} RST Version -->

<version id="{$version->getKey()|escape}" locale="{$version->getLocale()|escape}">
	<version_title>{$version->getTitle()|escape}</version_title>
	<version_description>{$version->getDescription()|escape}</version_description>

{foreach from=$version->getContexts() item=context}
	<context>
		<context_title>{$context->getTitle()|escape}</context_title>
		<context_description>{$context->getDescription()|escape}</context_description>
		<context_abbrev>{$context->getAbbrev()|escape}</context_abbrev>
{if $context->getAuthorTerms()}		<author_terms>1</author_terms>{/if}
{if $context->getDefineTerms()}		<define_terms>1</define_terms>{/if}

{foreach from=$context->getSearches() item=search}
		<search>
			<search_title>{$search->getTitle()|escape}</search_title>
			<search_url>{$search->getSearchUrl()|escape}</search_url>
{if $search->getSearchPost()}			<search_post>{$search->getSearchPost()|escape}</search_post>{/if}
			<search_description>{$search->getDescription()|escape}</search_description>
			
		</search>
{/foreach}
	</context>
{/foreach}
</version>
