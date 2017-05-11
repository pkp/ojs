{**
 * templates/form/colour.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form color input that uses the HTML <input type="color"> element. For
 * browsers that don't support this element, we load the Spectrum jQuery lib:
 * https://github.com/bgrins/spectrum
 *}

{assign var="uniqId" value="-"|concat:$FBV_uniqId|escape}
<div{if $FBV_layoutInfo} class="{$FBV_layoutInfo}"{/if}>
	<input type="color"
		{$FBV_colorParams}
		class="field color{if $FBV_class} {$FBV_class|escape}{/if}{if $FBV_validation} {$FBV_validation}{/if}"
		{if $FBV_disabled} disabled="disabled"{/if}
		{if $FBV_readonly} readonly="readonly"{/if}
		name="{$FBV_name|escape}"
		value="{if $FBV_value}{$FBV_value|escape}{else}{$FBV_default|escape}{/if}"
		id="{$FBV_id|escape}{$uniqId}"
		{if $FBV_tabIndex} tabindex="{$FBV_tabIndex|escape}"{/if}
	/>
	<span>{$FBV_label_content}</span>
</div>
