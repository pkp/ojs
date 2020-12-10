{**
 * plugins/importexport/native/templates/results.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Result of operations this plugin performed
 *}
{if $errorsFound}
	{translate key="plugins.importexport.native.processFailed"}
{else}
	{translate key="plugins.importexport.native.importComplete"}
	<ul>
		{foreach from=$importedRootObjects item=contentItemArrays key=contentItemName}
			<b>{$contentItemName}</b>
			{foreach from=$contentItemArrays item=contentItemArray}
				{foreach from=$contentItemArray item=contentItem}
					<li>
						{$contentItem->getUIDisplayString()}
					</li>
				{/foreach}
			{/foreach}
		{/foreach}
	</ul>
{/if}

{if array_key_exists('warnings', $errorsAndWarnings) && $errorsAndWarnings.warnings|@count > 0}
	<h2>{translate key="plugins.importexport.common.warningsEncountered"}</h2>
	{foreach from=$errorsAndWarnings.warnings item=allRelatedTypes key=relatedTypeName}
		{foreach from=$allRelatedTypes item=thisTypeIds key=thisTypeId}
			{if $thisTypeIds|@count > 0}
				<p>{$relatedTypeName} {if $thisTypeId > 0} (Id: {$thisTypeId}) {/if}</p>
				<ul>
					{foreach from=$thisTypeIds item=idRelatedItems}
						{foreach from=$idRelatedItems item=relatedItemMessage}
							<li>{$relatedItemMessage|escape}</li>
						{/foreach}
					{/foreach}
				</ul>
			{/if}
		{/foreach}
	{/foreach}
{/if}

{if array_key_exists('errors', $errorsAndWarnings) && $errorsAndWarnings.errors|@count > 0}
	<h2>{translate key="plugins.importexport.common.errorsOccured"}</h2>
	{foreach from=$errorsAndWarnings.errors item=allRelatedTypes key=relatedTypeName}
		{foreach from=$allRelatedTypes item=thisTypeIds key=thisTypeId}
			{if $thisTypeIds|@count > 0}
				<p>{$relatedTypeName} {if $thisTypeId > 0} (Id: {$thisTypeId}) {/if}</p>
				<ul>
					{foreach from=$thisTypeIds item=idRelatedItems}
						{foreach from=$idRelatedItems item=relatedItemMessage}
							<li>{$relatedItemMessage|escape}</li>
						{/foreach}
					{/foreach}
				</ul>
			{/if}
		{/foreach}
	{/foreach}
{/if}

{if $validationErrors}
	<h2>{translate key="plugins.importexport.common.validationErrors"}</h2>
	<ul>
		{foreach from=$validationErrors item=validationError}
			<li>{$validationError->message|escape}</li>
		{/foreach}
	</ul>
{/if}
