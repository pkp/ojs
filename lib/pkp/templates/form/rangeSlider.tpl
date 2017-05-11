{**
 * templates/form/rangeSliderInput.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * PKP handler for the jQueryUI range slider input
 *}
<script>
	$(function() {ldelim}
		$('#{$FBV_id}_container').pkpHandler('$.pkp.controllers.RangeSliderHandler',
			{ldelim}
				values: [{$FBV_value_min|string_format:"%d"}, {$FBV_value_max|string_format:"%d"}],
				min: "{$FBV_min|escape:javascript}",
				max: "{$FBV_max|escape:javascript}",
				toggleable: {if $FBV_toggleable}true{else}false{/if}
			{rdelim});
	{rdelim});
</script>

<div id="{$FBV_id}_container" class="pkp_controllers_rangeSlider{if $FBV_toggleable} is_toggleable{if $FBV_enabled} is_enabled{/if}{/if}{if $FBV_layoutInfo} {$FBV_layoutInfo}{/if}">
	{if $FBV_toggleable}
		<div class="toggle">
			<input type="checkbox" id="{$FBV_id}Enabled" name="{$FBV_id}Enabled" value="1"{if $FBV_enabled} checked{/if}>
			<label for="{$FBV_id}Enabled">
				<span class="label">
					{translate key=$FBV_toggleable_label}
				</span>
			</label>
		</div>
	{/if}
	<div class="control">
		<label>
			{* Wrap min/max values in spans required for the live update of values,
				but construct the overall string in a way that can still be
				re-arranged by translators as needed. *}
			{capture assign="current_min_value"}
				<span class="pkp_controllers_rangeSlider_sliderValueMin">
					{$FBV_value_min}
				</span>
			{/capture}
			{capture assign="current_max_value"}
				<span class="pkp_controllers_rangeSlider_sliderValueMax">
					{$FBV_value_max}
				</span>
			{/capture}
			{capture assign="current_value"}
				<span class="value">{translate key="common.range" min=$current_min_value max=$current_max_value}</span>
				{if $FBV_toggleable}
					<span class="disabled">{translate key="common.disabled"}</span>
				{/if}
			{/capture}
			{translate key=$FBV_label current=$current_value}
		</label>
		<div id="{$FBV_id}_slider" class="pkp_controllers_rangeSlider_slider"></div>
	</div>
	<input type="hidden" id="{$FBV_id}Min" name="{$FBV_id}Min" value="{$FBV_value_min}" class='pkp_controllers_rangeSlider_minInput' />
	<input type="hidden" id="{$FBV_id}Max" name="{$FBV_id}Max" value="{$FBV_value_max}" class='pkp_controllers_rangeSlider_maxInput' />
</div>
