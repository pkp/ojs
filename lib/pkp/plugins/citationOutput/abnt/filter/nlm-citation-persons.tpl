{**
 * nlm-citation-persons.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ABNT citation output format template (NLM citation schema based) - person list
 *}
{strip}
	{foreach from=$persons item=person name=persons key=personIndex}
		{if (count($persons) <= 3 || $smarty.foreach.persons.first) && is_a($person, 'MetadataDescription')}
			{if $person->getStatement('prefix')}{$person->getStatement('prefix')|escape|upper} {/if}{$person->getStatement('surname')|escape|upper}, {if $person->getStatement('suffix')}{$person->getStatement('suffix')|escape|upper} {/if}
			{foreach from=$person->getStatement('given-names') item=givenName}{$givenName[0]|escape}.{/foreach}
			{if $smarty.foreach.persons.last || count($persons) > 3} {else}; {/if}
		{elseif $smarty.foreach.persons.last}
			{literal}et al. {/literal}
		{/if}
	{/foreach}
{/strip}
