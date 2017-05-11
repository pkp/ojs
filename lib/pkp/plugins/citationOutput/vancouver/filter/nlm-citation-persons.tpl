{**
 * nlm-citation-persons.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Vancouver citation output format template (NLM citation schema based) - person list
 *}
{strip}
	{foreach from=$persons item=person name=persons key=personIndex}
		{if $personIndex < 6 && is_a($person, 'MetadataDescription')}
			{if $person->getStatement('prefix')}{$person->getStatement('prefix')|escape} {/if}{$person->getStatement('surname')|escape}{if $person->getStatement('suffix')} {$person->getStatement('suffix')|escape}{/if}
			{literal} {/literal}
			{foreach from=$person->getStatement('given-names') item=givenName}{$givenName[0]}{/foreach}
			{if !$smarty.foreach.persons.last && count($persons) > 1}, {elseif !$editor}. {/if}
		{elseif $smarty.foreach.persons.last}
			{literal}et al. {/literal}
		{/if}
	{/foreach}
{/strip}
