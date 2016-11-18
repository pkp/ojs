{**
 * templates/frontend/objects/file_revisions.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View of file revisions the current galley file which is shown at the article page.
 *
 * @uses $galley Galley
 *}


<div class="item file_revisions">

	<div class="label">{translate key="submissions.versionsOf"} {$galley->getGalleyLabel()}</div>
	
	<div class="sub_item">	
		{assign var=currentVersion value=$galley->getCurrentFileVersion($galley->getFileId())}
		<div class="label">{translate key="submissions.versions.current"}</div>
		<div class="value">
			<ul class="value galleys_links">
				<li>{include file="frontend/objects/galley_link.tpl" parent=$article galley=$galley revision=$currentVersion} {$currentVersion->getDateModified()|date_format}</li> 
			</ul>
		</div>
	</div>	
	
	{assign var=otherRevisions value=$galley->getOtherRevisions($galley->getFileId())} 
	{if $otherRevisions}
		
		<div class="sub_item">	
			<div class="label">{translate key="submissions.versions.previous"}</div>
			<div class="value">
				<ul class="value galleys_links">
					{foreach from=$otherRevisions item=revision}
						<li>{include file="frontend/objects/galley_link.tpl" parent=$article galley=$galley revision=$revision} {$revision->getDateModified()|date_format}</li> 
					{/foreach}
				</ul>	
			</div>
		</div>
	{/if}	
</div>
