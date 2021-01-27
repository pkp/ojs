{**
 * templates/controllers/modals/submissionPayments/status.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display submission payment status information.
 *}

{assign var="uuid" value=""|uniqid|escape}
<div id="assign-{$uuid}" class="pkpWorkflow__submissionPaymentsModal">
	<pkp-form v-bind="components.{$smarty.const.FORM_SUBMISSION_PAYMENTS}" @set="set" />
	<script type="text/javascript">
		pkp.registry.init('assign-{$uuid}', 'Container', {$assignData|json_encode});
	</script>
</div>
