{**
 * nlm-citation.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * NLM citation output format template (NLM citation schema based) - display a single element
 * Parameters:
 *   $property: a MetadataProperty object containing the property information for this statement.
 *   $value: the value to be displayed
 *}
{* Identify the exact type of the value *}
{strip}
	{assign var=validatedTypeArray value=$property->isValid($value)}
	{foreach from=$validatedTypeArray key=validatedType item=typeOptions}
		{if $validatedType == $smarty.const.METADATA_PROPERTY_TYPE_COMPOSITE}
			{* recursively include the generic XML template *}
			{include file="nlm-citation.tpl" metadataDescription=$value}
		{else}
			{* Special treatment for dates which we have to split up here because we
			   wanted to avoid the complexity of a full composite schema for dates when
			   modeling our meta-data schema. *}
			{if $property->getName()|substr:0:4 == 'date'}
				{* Dates are expected in ISO format (YYYY[-MM[-DD]]) and must be reversed to day - month - year. *}
				{assign var=dateParts value=$value|explode:"-"|regex_replace:"/^0+/":""}
				{if isset($dateParts[2])}
					<day>{$dateParts[2]}</day>
				{/if}
				{if isset($dateParts[1])}
					<month>{$dateParts[1]}</month>
				{/if}
				<year>{$dateParts[0]}</year>
			{else}
				{* Escape only mandatory XML entities. *}
				{$value|replace:'"':'&quot;'|replace:'&':'&amp;'|replace:"'":'&apos;'|replace:'<':'&lt;'|replace:'>':'&gt;'}
			{/if}
		{/if}
	{/foreach}
{/strip}
