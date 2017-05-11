{**
 * templates/form/radioButton.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form radio button
 *}

<li{if $FBV_layoutInfo} class="{$FBV_layoutInfo}"{/if}>
	<label>
	<input type="radio" id="{$FBV_id|escape}" {$FBV_radioParams} class="field radio"{if $FBV_checked} checked="checked"{/if}{if $FBV_disabled} disabled="disabled"{/if}
	{if $FBV_required} required aria-required="true"{/if}>
	{if $FBV_label}
		{if $FBV_translate}
			{translate key=$FBV_label}
		{else}
			{$FBV_label|escape}
		{/if}
	{elseif $FBV_content}
		{$FBV_content}
	{/if}
	</label>
</li>
