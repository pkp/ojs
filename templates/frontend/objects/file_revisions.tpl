
{*
 display other revisions of the current galley file
*}

{assign var=otherRevisions value=$galley->getOtherRevisions($galley->getFileId())} 

{if $otherRevisions}

<div>
	<div class="label">{translate key="article.revisions"}</div>

		<ul>
		{foreach from=$otherRevisions item=revision}

			<li>{include file="frontend/objects/galley_link.tpl" parent=$article galley=$galley revision=$revision}</li> 

		{/foreach}
		</ul>	
</div>

{/if}
