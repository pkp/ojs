{**
 * nlm-citation-persons.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * MLA citation output format template (NLM citation schema based) - person list
 *}
{strip}
	{foreach from=$persons item=person name=persons key=personIndex}
		{if (count($persons) <= 3 || $smarty.foreach.persons.first) && is_a($person, 'MetadataDescription')}
			{capture assign="surname"}
				{if $person->getStatement('prefix')}{$person->getStatement('prefix')|escape} {/if}{$person->getStatement('surname')|escape}{if $person->getStatement('suffix')} {$person->getStatement('suffix')|escape}{/if}
			{/capture}
			{capture assign="givenName"}
				{foreach from=$person->getStatement('given-names') name=givenNames item=givenName}
					{$givenName|escape}{if ! $smarty.foreach.givenNames.last} {/if}
				{/foreach}
			{/capture}
			{if count($persons) <= 3 && !$smarty.foreach.persons.first && !$smarty.foreach.persons.last}, {elseif !$smarty.foreach.persons.first} {/if}
			{if $smarty.foreach.persons.first && !$editor}
				{$surname}, {$givenName}{if count($persons) > 1},{/if}
			{else}
				{if $smarty.foreach.persons.last && count($persons) > 1}and {/if}{$givenName} {$surname}
			{/if}
			{if $smarty.foreach.persons.last}. {/if}
		{elseif $smarty.foreach.persons.last}
			{literal} et al. {/literal}
		{/if}
	{/foreach}
{/strip}
