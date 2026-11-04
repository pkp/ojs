{**
 * templates/controllers/grid/issues/issueEditors.tpl
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Form to edit an issue's editors
 *}
{assign var="uuid" value=""|uniqid|escape}
<div id="issue-editors-{$uuid}">
	<pkp-form v-bind="components.{APP\components\forms\issue\IssueEditorsForm::FORM_ISSUE_EDITORS}" @set="set" />
	<script type="text/javascript">
		pkp.registry.init('issue-editors-{$uuid}', 'Container', {$issueEditorsData|json_encode});
	</script>
</div>
