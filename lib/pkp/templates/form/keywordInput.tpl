{**
 * templates/form/keywordInput.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Generic keyword input control
 *}
{assign var="uniqId" value="-"|concat:$FBV_uniqId|escape}
{if $FBV_multilingual && count($formLocales) > 1}
	{foreach from=$formLocales key=thisFormLocale item=thisFormLocaleName}
		<script>
			$(document).ready(function(){ldelim}
				$("#{$thisFormLocale|escape}-{$FBV_id}{$uniqId}").tagit({ldelim}
					fieldName: "keywords[{$thisFormLocale|escape}-{$FBV_id|escape}][]",
					allowSpaces: true,
					{if $FBV_sourceUrl && !$FBV_disabled}
						tagSource: function(search, showChoices) {ldelim}
							$.ajax({ldelim}
								url: "{$FBV_sourceUrl}", {* this url should return a JSON array of possible keywords *}
								data: search,
								success: function(choices) {ldelim}
									showChoices(choices);
								{rdelim}
							{rdelim});
						{rdelim}
					{else}
						availableTags: {$FBV_availableKeywords.$thisFormLocale|@json_encode}
					{/if}
				{rdelim});

				{** Tag-it has no "read-only" option, so we must remove input elements to disable the widget **}
				{if $FBV_disabled}
					$("#{$thisFormLocale|escape}-{$FBV_id|concat:$uniqId|escape}").find('.tagit-close, .tagit-new').remove();
				{/if}
			{rdelim});
		</script>
	{/foreach}

	<script>
		$(function() {ldelim}
			$('#{$FBV_id|escape:javascript}-localization-popover-container{$uniqId}').pkpHandler(
				'$.pkp.controllers.form.MultilingualInputHandler'
				);
		{rdelim});
		</script>
		<span id="{$FBV_id|escape}-localization-popover-container{$uniqId}" class="localization_popover_container pkpTagit">
			<ul class="localization_popover_container localizable {if $formLocale != $currentLocale} flag flag_{$formLocale|escape}{/if}" id="{$formLocale|escape}-{$FBV_id|escape}{$uniqId}">
				{if $FBV_currentKeywords}{foreach from=$FBV_currentKeywords.$formLocale item=currentKeyword}<li>{$currentKeyword|escape}</li>{/foreach}{/if}
			</ul>
			{if $FBV_label_content}<span>{$FBV_label_content}</span>{/if}
			<div class="localization_popover">
				{foreach from=$formLocales key=thisFormLocale item=thisFormLocaleName}{if $formLocale != $thisFormLocale}
					<ul class="multilingual_extra flag flag_{$thisFormLocale|escape}" id="{$thisFormLocale|escape}-{$FBV_id|escape}{$uniqId}">
						{if $FBV_currentKeywords}{foreach from=$FBV_currentKeywords.$thisFormLocale item=currentKeyword}<li>{$currentKeyword|escape}</li>{/foreach}{/if}
					</ul>
				{/if}{/foreach}
			</div>
		</span>

{else} {* this is not a multilingual keyword field or there is only one locale available *}
	<script>
		$(document).ready(function(){ldelim}
			$("#{$FBV_id}{$uniqId}").tagit({ldelim}
				fieldName: "keywords[{if $FBV_multilingual}{$formLocale|escape}-{/if}{$FBV_id|escape}][]",
				allowSpaces: true,
				{if $FBV_sourceUrl && !$FBV_disabled}
					tagSource: function(search, showChoices) {ldelim}
						$.ajax({ldelim}
							url: "{$FBV_sourceUrl}", {* this url should return a JSON array of possible keywords *}
							data: search,
							success: function(choices) {ldelim}
								showChoices(choices);
							{rdelim}
						{rdelim});
					{rdelim}
				{else}
					availableTags: {$FBV_availableKeywords.$formLocale|@json_encode}
				{/if}
			{rdelim});

			{** Tag-it has no "read-only" option, so we must remove input elements to disable the widget **}
			{if $FBV_disabled}
				$("#{$FBV_id|escape}{$uniqId}").find('.tagit-close, .tagit-new').remove();
				$("#{$FBV_id|escape}{$uniqId}:empty").removeClass('tagit');
			{/if}
		{rdelim});
	</script>

	<!-- The container which will be processed by tag-it.js as the interests widget -->
	<ul id="{$FBV_id|escape}{$uniqId}">{if $FBV_currentKeywords}{foreach from=$FBV_currentKeywords.$formLocale item=currentKeyword}<li>{$currentKeyword|escape}</li>{/foreach}{/if}</ul>
	{if $FBV_label_content}<span>{$FBV_label_content}</span>{/if}
{/if}
