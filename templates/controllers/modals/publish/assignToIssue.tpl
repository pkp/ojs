{**
 * templates/controllers/modals/publish/assignToIssue.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Assign publicatin to issue before scheduling for publication
 *}


{assign var="uuid" value=""|uniqid|escape}
<div id="assign-{$uuid}" class="pkpWorkflow__publishModal">
	<pkp-form v-bind="components.{$smarty.const.FORM_ASSIGN_TO_ISSUE}" @set="set" />
	<script type="text/javascript">
		pkp.registry.init('assign-{$uuid}', 'Container', {$assignData|json_encode});
	</script>
</div>