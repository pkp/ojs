{**
 * templates/controllers/grid/issues/form/issueData.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *}

{if !empty($sections)}
	{fbvFormArea id="sectionArea" title="editor.issues.sectionArea"}
		{fbvFormSection title="plugins.themes.immersion.colorPick" inline=false size=$fbvStyles.size.MEDIUM}
			{foreach from=$sections item=section}
				{assign var=sectionId value=$section->getId()}
				{* Color picker for issue's sections*}
				<label for="immersionSectionColor-{$sectionId}">{$section->getLocalizedTitle()|escape}</label>
				<input
						id="immersionSectionColor-{$sectionId}"
						type="color"
						name="immersionSectionColor[{$sectionId}]"
						value="{$immersionSectionColor[$sectionId]}"
				/>
			{/foreach}
		{/fbvFormSection}
	{/fbvFormArea}
{/if}

