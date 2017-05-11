{**
 * templates/form/formSection.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form section.
 *}


<div {if $FBV_id}id="{$FBV_id|escape}" {/if}class="section {$FBV_class|escape} {$FBV_layoutInfo|escape}">
	{if $FBV_label}
		{if $FBV_translate}{translate|assign:"FBV_labelTranslated" key=$FBV_label|escape}
		{else}{assign var="FBV_labelTranslated" value=$FBV_Label}{/if}
		{if $FBV_labelFor}<label for="{$FBV_labelFor|escape}">{$FBV_labelTranslated}{if $FBV_required}<span class="req">*</span>{/if}</label>
		{else}<span class="label">{$FBV_labelTranslated}</span>{/if}
	{/if}
	{if $FBV_description}<label class="description">{if $FBV_translate}{translate key=$FBV_description}{else}{$FBV_description}{/if}</label>{/if}
	{if $FBV_listSection}<ul class="checkbox_and_radiobutton">{/if}
		{if $FBV_title}<label {if $FBV_labelFor} for="{$FBV_labelFor|escape}"{/if}>{if $FBV_translate}{translate key=$FBV_title}{else}{$FBV_title}{/if}{if $FBV_required}<span class="req">*</span>{/if}</label>{/if}
			{foreach from=$FBV_sectionErrors item=FBV_error}
				<span class="error">{$FBV_error|escape}</span>
			{/foreach}

			{$FBV_content}
	{if $FBV_listSection}</ul>{/if}
</div>
