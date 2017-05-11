{**
 * templates/form/select.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form select
 *}

<div{if $FBV_layoutInfo} class="{$FBV_layoutInfo}"{/if}>
<select {$FBV_selectParams}{if $FBV_class} class="{$FBV_class}"{/if}{if $FBV_disabled} disabled="disabled"{/if}{if $FBV_required} required aria-required="true"{/if}>
	{if $FBV_defaultValue !== null}
		<option value="{$FBV_defaultValue|escape}">{$FBV_defaultLabel|escape}</option>
	{/if}
	{if $FBV_translate}{html_options_translate options=$FBV_from selected=$FBV_selected}{else}{html_options options=$FBV_from selected=$FBV_selected}{/if}
</select>

{if $FBV_label_content}
	<span>{$FBV_label_content}</span>
{/if}
</div>
