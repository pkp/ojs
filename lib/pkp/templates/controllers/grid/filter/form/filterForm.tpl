{**
 * filterForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Filter grid form
 *}

{assign var="uid" value="-"|uniqid}
<script>
		$(function() {ldelim}
				// Attach the form handler.
				$('#editFilterForm{$uid|escape:"javascript"}').pkpHandler(
						'$.pkp.controllers.grid.filter.form.FilterFormHandler',
			{ldelim}
				noMoreTemplates: {if $noMoreTemplates}true{else}false{/if},
				filterTemplates: {if $filterTemplates}true{else}false{/if},
				editFilterUrlTemplate: {url|json_encode op="editFilter" filterTemplateId="DUMMY_FILTER_TEMPLATE_ID" escape=false},
				pulldownSelector: '#filterTemplateSelect{$uid|escape:"javascript"}',
			{rdelim}
		);
		{rdelim});
</script>

<form class="pkp_form" id="editFilterForm{$uid|escape}" method="post" action="{url op="updateFilter"}" >
	{csrf}
	{if $noMoreTemplates}
		<p>{translate key='manager.setup.filter.noMoreTemplates'}</p>
	{else}
		<h3>{translate key=$formTitle}</h3>

		<p>{translate key=$formDescription filterDisplayName=$filterDisplayName}</p>

		{include file="common/formErrors.tpl"}

		{if $filterTemplates}
			{* Template selection *}
			{fbvElement type="select" id="filterTemplateSelect"|concat:$uid name="filterTemplateId"
					from=$filterTemplates translate=false defaultValue="-1" defaultLabel="manager.setup.filter.pleaseSelect"|translate}
		{else}
			{assign var=hasRequiredField value=false}
			<table>
				{foreach from=$filterSettings item=filterSetting}
					{if $filterSetting->getRequired() == $smarty.const.FORM_VALIDATOR_REQUIRED_VALUE}
						{assign var=filterSettingRequired value='1'}
						{assign var=hasRequiredField value=true}
					{else}
						{assign var=filterSettingRequired value=''}
					{/if}
					<tr>
						<td class="label">{fieldLabel name=$filterSetting->getName() key=$filterSetting->getDisplayName() required=$filterSettingRequired}</td>
						{capture assign=settingValueVar}{ldelim}${$filterSetting->getName()}{rdelim}{/capture}
						{eval|assign:"settingValue" var=$settingValueVar}
						<td class="value">
							{if $filterSetting|is_a:SetFilterSetting}
								{fbvElement type="select" id=$filterSetting->getName() name=$filterSetting->getName()
										from=$filterSetting->getLocalizedAcceptedValues() selected=$settingValue translate=false}
							{elseif $filterSetting|is_a:BooleanFilterSetting}
								{fbvElement type="checkbox" id=$filterSetting->getName() name=$filterSetting->getName()
										checked=$settingValue}
							{else}
								{fbvElement type="text" id=$filterSetting->getName() name=$filterSetting->getName()
										size=$fbvStyles.size.LARGE maxlength=250 value=$settingValue}
							{/if}
						</td>
					</tr>
				{/foreach}
			</table>
			{if $hasRequiredField}<p><span class="formRequired">{translate key="common.requiredField"}</span></p>{/if}

			{if $filterId}<input type="hidden" name="filterId" value="{$filterId|escape}" />{/if}
			{if $filterTemplateId}<input type="hidden" name="filterTemplateId" value="{$filterTemplateId|escape}" />{/if}
		{/if}

	{/if}
	{fbvFormButtons submitText="common.save"}
</form>
