{**
 * plugins/generic/lucene/templates/preResults.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Search::SearchResults::PreResults hook.
 *}
{if !empty($spellingSuggestion)}
	<strong class="plugins_generic_lucene_preResults_spelling">{translate key="plugins.generic.lucene.results.didYouMean"}: <a href="{url op="search" params=$spellingSuggestionUrlParams|escape}">{$spellingSuggestion|escape}</a></strong>
{/if}
