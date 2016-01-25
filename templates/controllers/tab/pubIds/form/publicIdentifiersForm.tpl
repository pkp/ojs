{**
 * templates/controllers/tab/pubIds/form/identifiersForm.tpl
 *
 * Copyright (c) 2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#publicIdentifiersForm').pkpHandler(
			'$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true
			{rdelim}
		);
	{rdelim});
</script>
{if $pubObject instanceof Article}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveForm"}">
		<input type="hidden" name="submissionId" value="{$pubObject->getId()|escape}" />
		<input type="hidden" name="stageId" value="{$stageId|escape}" />
		<input type="hidden" name="tabPos" value="2" />
		<input type="hidden" name="displayedInContainer" value="{$formParams.displayedInContainer|escape}" />
		<input type="hidden" name="tab" value="identifiers" />
{else} {* $pubObject instanceof Issue *}
	<form class="pkp_form" id="publicIdentifiersForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="updatePubIds" issueId=$pubObject->getId()}">
{/if}
	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{assign var=pubIdMetadataFile value=$pubIdPlugin->getPubIdMetadataFile()}
		{include file="$pubIdMetadataFile" pubObject=$pubObject}
	{/foreach}
	{fbvFormButtons id="publicIdentifiersFormSubmit" submitText="common.save"}
</form>