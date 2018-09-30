{**
 * templates/submission/form/section.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Include section placement for submissions.
 *}
{assign var=sectionDescription value=""}
{if !$readOnly}
	{assign var=sectionDescription value="author.submit.journalSectionDescription"}
{/if}
{fbvFormSection title="section.section" required=true}
	{fbvElement type="select" id="sectionId" label=$sectionDescription from=$sectionOptions selected=$sectionId translate=false disabled=$readOnly size=$fbvStyles.size.MEDIUM required=true}
{/fbvFormSection}

{foreach from=$sectionPolicies key="sectionPolicySectionId" item="content"}
	{assign var=sectionPolicyHidden value=false}
	{if $sectionPolicySectionId != $sectionId}
		{assign var=sectionPolicyHidden value=true}
	{/if}
	{include file="submission/form/sectionPolicy.tpl" sectionId=$sectionPolicySectionId content=$content hidden=$sectionPolicyHidden}
{/foreach}

<script type="text/javascript">
	$(function() {ldelim}

		{* replace initial pkp_helpers_display_none classes with inline style 'display: none' to allow animations in jQuery.show() calls *}
		$('.section-policy').filter('.pkp_helpers_display_none').hide().removeClass('pkp_helpers_display_none');

		$('#sectionId').change(function () {ldelim}
			var sectionId = $(this).val();

			var $visibleSectionPolicy = $('.section-policy:visible');

			var showSectionPolicy = function (sectionId) {ldelim}
				$('.section-policy.section-id-' + sectionId).fadeIn({ldelim}
					complete: function () {ldelim}
						// section shown
					{rdelim}
				{rdelim});
			{rdelim};

			if ($visibleSectionPolicy.length > 0) {ldelim}
				$visibleSectionPolicy.fadeOut({ldelim}
					complete: function () {ldelim}
						showSectionPolicy(sectionId);
					{rdelim}
				{rdelim});
			{rdelim} else {ldelim}
				showSectionPolicy(sectionId);
			{rdelim}
		{rdelim});
	{rdelim});
</script>
