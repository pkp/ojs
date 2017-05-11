{**
 * nlm-citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * NLM citation output format template (NLM citation schema based).
 *
 * NB: This is a mostly generic transformation of an XML-based meta-data schema
 * into an actual XML output. This can be re-used almost unchanged for any other
 * XML-based meta-data schema implementation. This relies on an XPath-like
 * nomenclature for property names.
 *}
{strip}
	{* Generate the schema's child nodes. *}
	{capture assign=xmlBody}
		{foreach from=$metadataDescription->getProperties() item=property}
			{assign var=propertyName value=$property->getName()}
			{* Make sure that this is not an attribute of the main element (starts with [) and that a statement exists *}
			{if $propertyName[0] != '[' && $metadataDescription->hasStatement($propertyName)}
				{assign var=currentStatement value=$metadataDescription->getStatement($propertyName)}
				{assign var=xmlElementOpen value=$propertyName|replace:'[@':' '|replace:']':''}
				{assign var=xmlElementClose value=$propertyName|regex_replace:'/\[@.*/':''}
				{if $property->getCardinality() == $smarty.const.METADATA_PROPERTY_CARDINALITY_MANY}
					{* Elements with cardinality 'many' require special treatment for two reasons:
					   1) We need to implement a workaround for our deviation from
					      NLM 3.0 in the name schema (see Nlm30NameSchema's classdoc for
					      further info).
					   2) Composite descriptions provide their own root tag which means that
					      we have to provide a single enclosing element tag only.
					   In all other cases we assume that cardinality 'many' means that the
					   element tag has to be repeated *}
					{if $propertyName == 'given-names' || in_array($smarty.const.METADATA_PROPERTY_TYPE_COMPOSITE, array_keys($property->getAllowedTypes()))}
						{assign var=singleElement value=true}
						{if $propertyName == 'given-names'}{assign var=insertWhitespace value=true}{/if}
					{else}
						{assign var=singleElement value=false}
						{assign var=insertWhitespace value=false}
					{/if}
					{if $singleElement}<{$xmlElementOpen}>{/if}
					{foreach name=statementValues from=$currentStatement item=currentValue}
						{if !$singleElement}<{$xmlElementOpen}>{elseif $insertWhitespace && !$smarty.foreach.statementValues.first} {/if}
						{include file="nlm-statement.tpl" property=$property value=$currentValue}
						{if !$singleElement}</{$xmlElementClose}>{/if}
					{/foreach}
					{if $singleElement}</{$xmlElementClose}>{/if}
				{else}
					{* An element with cardinality (zero or) one:
					   NB: Publication dates need special treatment as we save them as a single string to avoid
					   having to implement a composite schema. Dates will be split up into tags the statement
					   template. *}
					{if $propertyName != 'date'}<{$xmlElementOpen}>{/if}
						{include file="nlm-statement.tpl" property=$property value=$currentStatement}
					{if $propertyName != 'date'}</{$xmlElementClose}>{/if}
				{/if}
			{/if}
		{/foreach}
	{/capture}

	{* Attributes of the schema's main element have property names like "[@...]". *}
	{capture assign=mainElementAttribs}
		{foreach from=$metadataDescription->getProperties() item=property}
			{assign var=propertyName value=$property->getName()}
			{* Filter attributes of the main element *}
			{if $propertyName[0] == '['}
				{$propertyName|replace:'[@':' '|replace:']':''}="{$metadataDescription->getStatement($propertyName)}"
			{/if}
		{/foreach}
	{/capture}

	{* The main element depends on the meta-data schema we're currently transforming. *}
	{if $metadataDescription->getMetadataSchema()|is_a:'Nlm30CitationSchema'}
		<element-citation{$mainElementAttribs}>{$xmlBody}</element-citation>
	{elseif $metadataDescription->getMetadataSchema()|is_a:'Nlm30NameSchema'}
		<name{$mainElementAttribs}>{$xmlBody}</name>
	{/if}
{/strip}
