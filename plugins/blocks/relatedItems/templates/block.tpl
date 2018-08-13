{**
 * plugins/blocks/relatedItems/block.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Keyword cloud block plugin
 *
 *}

{if $journalRt && $journalRt->getEnabled() && $journalRt->getDefineTerms() && $version}

<script>
	{literal}
	$(document).ready(function(){
		$("#relatedItems").hide();
		$("#toggleRelatedItems").show();
	    $("#hideRelatedItems").click(function() {
			$("#relatedItems").hide('slow');
			$("#hideRelatedItems").hide();
			$("#showRelatedItems").show();
		});
		$("#showRelatedItems").click(function() {
			$("#relatedItems").show('slow');
			$("#showRelatedItems").hide();
			$("#hideRelatedItems").show();
		});
	});
	{/literal}
</script>

<div class="pkp_block block_rt_related_items">
	<span class="title">{translate key="plugins.block.relatedItems.title"}</span>
	<div class="content">
		<table>
			<tr>
				<td valign="top" style="width:26px;">
					<img src="{$baseUrl}/plugins/blocks/relatedItems/icons/lookupTerms.png" class="articleToolIcon" />
				</td>
				<td valign="top">
					<div id="relatedItems">
						<ul class="plain">
						{foreach from=$version->getContexts() item=context}
							{if !$context->getDefineTerms()}
								<li><a href="javascript:openRTWindowWithToolbar('{url router=$smarty.const.ROUTE_PAGE page="rt" op="context" path=$articleId|to_array:$galleyId:$context->getContextId()}');">{$context->getTitle()|escape}</a></li>
							{/if}
						{/foreach}
						</ul>
					</div>
					<div id="toggleRelatedItems">
						<span id="hideRelatedItems" style="display:none;"><img src="{$baseUrl}/plugins/blocks/relatedItems/icons/magnifier_zoom_out.png" /> {translate key="plugins.block.relatedItems.hide"}</span>
						<span id="showRelatedItems"><img src="{$baseUrl}/plugins/blocks/relatedItems/icons/magnifier_zoom_in.png" /> {translate key="plugins.block.relatedItems.show"}</span>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>

{/if}
