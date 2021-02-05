{**
 * templates/controllers/grid/articleGalleys/editFormat.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The "edit artilce galley" tabset.
 *}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#editArticleGalleyMetadataTabs').pkpHandler('$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="editArticleGalleyMetadataTabs">
	<ul>
		<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="editGalleyTab" submissionId=$submissionId publicationId=$publicationId representationId=$representationId}">{translate key="grid.action.editMetadata"}</a></li>
		{if $enableIdentifiers}
			<li><a href="{url router=$smarty.const.ROUTE_COMPONENT op="identifiers" submissionId=$submissionId publicationId=$publicationId representationId=$representationId}">{translate key="submission.identifiers"}</a></li>
		{/if}
	</ul>
</div>
