
{*
 display other revisions of the current galley file
*}

{assign var=galleys value=$article->getGalleys()}

{if $galleys}
	<div class="item versions">
		<div class="label">{translate key="submission.fileDetails"}</div>
		<ul class="value galleys_links">
			{foreach from=$galleys item=galley}
			
				{assign var=otherRevisions value=$galley->getOtherRevisions($galley->getFileId())} 

				{if $otherRevisions}
				
				<div class="value">
					
					{foreach from=$otherRevisions item=revision}

						<li>{include file="frontend/objects/galley_link.tpl" parent=$article galley=$galley revision=$revision} {$revision->getDateModified()|date_format}</li> 

					{/foreach}
						
				</div>

				{/if}

			{/foreach}
		</ul>
	</div>
{/if}
