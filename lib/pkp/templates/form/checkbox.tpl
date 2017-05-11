{**
 * templates/form/checkbox.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form checkbox
 *}

<li{if $FBV_layoutInfo} class="{$FBV_layoutInfo}"{/if}>
	{if $FBV_label}
		<label>
	{/if}
	<input type="checkbox" id="{$FBV_id|escape}" {$FBV_checkboxParams} class="field checkbox{if $FBV_validation} {$FBV_validation|escape}{/if}{if $FBV_required} required{/if}"{if $FBV_checked} checked="checked"{/if}{if $FBV_disabled} disabled="disabled"{/if}{if $FBV_required} required aria-required="true"{/if}/>
	{if $FBV_translate}
		{translate key=$FBV_label}
	{else}
		{if $FBV_keepLabelHtml}
			{$FBV_label}
		{else}
			{$FBV_label}
		{/if}
	{/if}
	{if $FBV_label}
		</label>
	{/if}
</li>
