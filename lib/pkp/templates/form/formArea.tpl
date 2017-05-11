{**
 * templates/form/formArea.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form area
 *}

<fieldset {if $FBV_id} id="{$FBV_id}"{/if}{if $FBV_class} class="pkp_formArea {$FBV_class|escape}"{/if}>
	{if $FBV_title}
		<legend>{if $FBV_translate}{translate key=$FBV_title}{else}{$FBV_title}{/if}</legend>
	{/if}
	{$FBV_content}
</fieldset>
