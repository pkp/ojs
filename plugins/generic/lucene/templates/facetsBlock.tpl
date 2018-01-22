{**
 * plugins/generic/lucene/templates/facetsBlock.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Faceted search results navigation block.
 *}
<div class="pkp_block block_lucene_facets">
	<span class="title">{translate key="plugins.generic.lucene.faceting.title"}</span>
	<div class="content">
		{foreach from=$facets key="facetCategory" item="facetList"}
			{if count($facetList)}
				{capture assign="categoryFacetsMarkup"}
					<ul>
					{foreach from=$facetList key="facet" item="facetCount"}
						{if $facetCategory == "publicationDate"}
							{assign var="dateFromYear" value=$facet}
							{assign var="dateToYear" value=$facet}
						{else}
							{if $facetCategory == "journalTitle"}
								{assign var=$facetCategory value=$facet}
							{else}
								{* exact phrase search *}
								{assign var=$facetCategory value='"'|concat:$facet|concat:'"'}
							{/if}
						{/if}
						<li>
							<a href="{url query=$query journalTitle=$journalTitle
								authors=$authors title=$title abstract=$abstract galleyFullText=$galleyFullText
								discipline=$discipline subject=$subject type=$type coverage=$coverage
								dateFromMonth=$dateFromMonth dateFromDay=$dateFromDay dateFromYear=$dateFromYear
								dateToMonth=$dateToMonth dateToDay=$dateToDay dateToYear=$dateToYear escape=false}">
									{$facet|escape}
							</a> ({$facetCount})
						</li>
						{if $facetCategory == "publicationDate"}
							{assign var="dateFromYear" value=""}
							{assign var="dateToYear" value=""}
						{else}
							{assign var=$facetCategory value=""}
						{/if}
					{/foreach}
					</ul>
				{/capture}
				{include file="controllers/extrasOnDemand.tpl" id=$facetCategory|concat:"Category"
					moreDetailsText="plugins.generic.lucene.faceting."|concat:$facetCategory
					lessDetailsText="plugins.generic.lucene.faceting."|concat:$facetCategory
					extraContent=$categoryFacetsMarkup}
			{/if}
		{/foreach}
	</div>
</div>
