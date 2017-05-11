{**
 * templates/form/textArea.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form text area
 *}

{assign var="uniqId" value="-"|concat:$FBV_uniqId|escape}
<div{if $FBV_layoutInfo} class="{$FBV_layoutInfo}"{/if}>
{if $FBV_multilingual && count($formLocales) > 1}
	<script>
	$(function() {ldelim}
		$('#{$FBV_name|escape:javascript}-localization-popover-container{$uniqId}').pkpHandler(
			'$.pkp.controllers.form.MultilingualInputHandler'
			);
	{rdelim});
	</script>
	{* This is a multilingual control. Enable popover display. *}
	<span id="{$FBV_name|escape}-localization-popover-container{$uniqId}" class="localization_popover_container">
		{strip}
			<textarea id="{$FBV_id|escape}-{$formLocale|escape}{$uniqId}" {$FBV_textAreaParams}
				rows="{$FBV_rows|escape}"
				cols="{$FBV_cols|escape}"
				class="localizable {$FBV_class} {$FBV_height}{if $FBV_validation} {$FBV_validation|escape}{/if}{if $formLocale != $currentLocale} locale_{$formLocale|escape}{/if}{if $FBV_rich && !$FBV_disabled} richContent{if $FBV_rich==="extended"} extendedRichContent{/if}{/if}"
				{if $FBV_disabled} disabled="disabled"{/if}
				{if $FBV_readonly} readonly="readonly"{/if}
				{if $FBV_variables} data-variables="{$FBV_variables|@json_encode|escape:"url"}"{/if}
				{if $FBV_required} required aria-required="true"{/if}
				name="{$FBV_name|escape}[{$formLocale|escape}]">{$FBV_value[$formLocale]|escape}
			</textarea>
		{/strip}

		{$FBV_label_content}

		<div class="localization_popover">
			{foreach from=$formLocales key=thisFormLocale item=thisFormLocaleName}{if $formLocale != $thisFormLocale}
				{strip}
				<textarea id="{$FBV_id|escape}-{$thisFormLocale|escape}{$uniqId}" {$FBV_textAreaParams}
					placeholder="{$thisFormLocaleName|escape}"
					class="flag flag_{$thisFormLocale|escape} {$FBV_class} {$FBV_height}{if $FBV_rich && !$FBV_disabled} richContent{if $FBV_rich==="extended"} extendedRichContent{/if}{/if}"
					{if $FBV_disabled} disabled="disabled"{/if}
					{if $FBV_readonly} readonly="readonly"{/if}
					{if $FBV_variables} data-variables="{$FBV_variables|@json_encode|escape:"url"}"{/if}
					{if $FBV_required} required aria-required="true"{/if}
					name="{$FBV_name|escape}[{$thisFormLocale|escape}]">{$FBV_value[$thisFormLocale]|escape}
				</textarea>
				{/strip}
				<label for="{$FBV_id|escape}-{$thisFormLocale|escape}{$uniqId}" class="locale">({$thisFormLocaleName|escape})</label>
			{/if}{/foreach}
		</div>
	</span>
{else}
	{* This is not a multilingual control or there is only one locale available *}
	{if $FBV_rich && $FBV_disabled}
		{if $FBV_multilingual}{$FBV_value[$formLocale]|strip_unsafe_html}{else}{$FBV_value|strip_unsafe_html}{/if}
	{else}
		<textarea {$FBV_textAreaParams}
			class="{$FBV_class} {$FBV_height}{if $FBV_validation} {$FBV_validation|escape}{/if}{if $FBV_rich && !$FBV_disabled} richContent{if $FBV_rich==="extended"} extendedRichContent{/if}{/if}"
			{if $FBV_disabled} disabled="disabled"{/if}
			{if $FBV_readonly} readonly="readonly"{/if}
			{if $FBV_variables} data-variables="{$FBV_variables|@json_encode|escape:"url"}"{/if}
			{if $FBV_required} required aria-required="true"{/if}
			name="{$FBV_name|escape}{if $FBV_multilingual}[{$formLocale|escape}]{/if}"
			rows="{$FBV_rows|escape}"
			cols="{$FBV_cols|escape}"
			id="{$FBV_id|escape}{$uniqId}">{if $FBV_multilingual}{$FBV_value[$formLocale]|escape}{else}{$FBV_value|escape}{/if}</textarea>
	{/if}
		<span>{$FBV_label_content}</span>
{/if}
</div>
