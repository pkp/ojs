{**
 * submissionCitations.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission citations.
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.citations" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.citations"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	<li class="current"><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
</ul>

{literal}
<script type="text/javascript" src="/pkp-ojs/lib/pkp/js/splitter.js"></script>

<script type="text/javascript">
	$(function() {
		$('#citationEditorCanvas').splitter({
			splitVertical:true,
			A:$('#citationEditorNavPane'),
			minAsize:200,
			B:$('#citationEditorDetailPane'),
			minBsize:300
		});
	});
</script>


<style type="text/css">
	/* style specific to the citation editor */
	div.grid div.approved_citation {
		background-color: #E8F0F8;
	}

	div.grid div.approved_citation span {
		color: #777777;
	}
		
	/* two-pane composite UI styles */
	div.compositeUi {
		margin: 10px 0;
		padding: 0;
		border: 1px solid #B6C9D5;
	}
	
	div.canvas {
		background-color:#EFEFEF;
		width: 100%;
	}
	
	/* Only the outer canvas will have a border */
	div.canvas div.grid table, div.canvas div.canvas {
		border: 0 none;
	}
	
	div.pane {
		background-color: #EFEFEF;
		height: 100%;
		overflow: auto;
	}
	
	div.pane table.pane_header {
		width: 100%;
	}
	
	div.pane table.pane_header th {
		padding: 4px;
		height: 30px;
		background-color: #CED7E1;
		color: #20538D;
		text-align: center;
		vertical-align: middle;
	}
	
	div.pane div.wrapper {
		padding: 10px 30px;
	}
	
	div.pane div.help_message {
		margin: 20px 40px;
		padding-left: 30px;
		background: transparent url("/pkp-ojs/lib/pkp/templates/images/icons/alert.gif") no-repeat;
	}

	div.pane div.pane_actions {
		width: 100%;
	}
	
	div.pane div.pane_actions button {
		float: right;
	}
	
	#citationEditorCanvas {
		height: 600px;
	}
		
	#citationEditorNavPane {
		float: left;
		width: 30%;
	}
	
	#citationEditorDetailPane {
		float: left;
	}


	/* splitbar styles - these are required styles */
	.splitbarV {
		float: left;
		width: 6px;
		height: 100%;
		line-height: 0;
		font-size: 0;
		border-left: solid 1px #9cbdff;
		border-right: solid 1px #9cbdff;
		background: #cbe1fb url(/pkp-ojs/lib/pkp/styles/splitter/ui-bg_pane.gif) 0% 50%;
	}

	.splitbarV.working,.splitbuttonV.working {
		 -moz-opacity: .50;
		 filter: alpha(opacity=50);
		 opacity: .50;
	}


	/* grid subcomponent styles */
	div.pane div.grid th .options {
		margin: 0;
	}
	
	div.pane div.grid th .options a {
		margin: 0;
	}
	
	div.pane div.grid div.active_cell:hover {
		background-color: #B6C9D5;
		cursor: pointer;
		text-decoration: underline:
	}
	
	div.pane div.grid .current_item .row_file,
	div.pane div.grid .current_item .row_container,
	div.pane div.grid .current_item td {
		background-color: #B6C9D5;
	}
	
	div.pane div.grid .current_item .row_container {
		border-left: 3px solid #20538D;
		padding-left: 22px;
	}
	
	div.pane div.grid .current_item .row_actions {
		width: 22px;
	}
</style>
{/literal}

<div id="submissionCitations">
	<h3>{translate key="submission.citations.grid.title"}</h3>

	{if $citationEditorConfigurationError}
		{capture assign="citationSetupUrl"}{url page="manager" op="setup" path="3" anchor="metaCitationEditing"}{/capture}
		{translate key=$citationEditorConfigurationError citationSetupUrl=$citationSetupUrl}
	{else}
		<div id="citationEditorContainer" class="compositeUi">
			<div id="citationEditorCanvas" class="canvas">
				<div id="citationEditorNavPane" class="pane">
					{load_div id="citationGridContainer" loadMessageId="submission.citations.form.loadMessage" url="$citationGridUrl"}
				</div>
				<div id="citationEditorDetailPane" class="pane">
					<table class="pane_header"><thead><tr><th>{translate key="submission.citations.form.citationDetails"}</th></tr></thead></table>
					<div id="citationEditorDetailCanvas" class="canvas">
						<div class="wrapper">
							<div class="help_message">{$initialHelpMessage}</div>
						</div>
					</div>
				</div>
				<script type="text/javascript">
					// Throbber
					$(function() {ldelim}
						$('#citationEditorDetailCanvas').bind('actionStart', function() {ldelim}
							$('#citationEditorDetailCanvas').html('<div id="citationEditorThrobber" class="throbber"></div>');
							$('#citationEditorThrobber').show();
						{rdelim});
					{rdelim});
				</script>
			</div>
			<div style="clear:both" />
		</div>
	{/if}
</div>

{include file="common/footer.tpl"}
