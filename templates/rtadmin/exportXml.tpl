<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE version SYSTEM "{$baseUrl}/rt/rt-version.dtd">

<!--
  * {$version->getKey()|escape}.xml
  *
  * Reading Tools version: {$version->getTitle()|escape} ({$version->getLocale()|escape})
  -->

<version id="{$version->getKey()|escape}" locale="{$version->getLocale()|escape}">
	<version_title>{$version->getTitle()|escape}</version_title>
	<version_description>{$version->getDescription()|escape}</version_description>

{foreach from=$version->getContexts() item=context}
	<context>
		<context_title>{$context->getTitle()|escape}</context_title>
		<context_abbrev>{$context->getAbbrev()|escape}</context_abbrev>
		<context_description>{$context->getDescription()|escape}</context_description>
{if $context->getAuthorTerms()}		<author_terms />{/if}
{if $context->getDefineTerms()}		<define_terms />{/if}

{foreach from=$context->getSearches() item=search}
		<search>
			<search_title>{$search->getTitle()|escape}</search_title>
			<search_description>{$search->getDescription()|escape}</search_description>
			<url>{$search->getUrl()|escape}</url>
			<search_url>{$search->getSearchUrl()|escape}</search_url>
{if $search->getSearchPost()}			<search_post>{$search->getSearchPost()|escape}</search_post>{/if}
			
		</search>
{/foreach}
	</context>
{/foreach}
</version>
