{if $citation}
	<h2>
        {translate key="submission.howToCite"}
	</h2>
	<div class="citation_format_value">
		<div id="citationOutput" role="region" aria-live="polite">
            {$citation}
		</div>
		<div class="citation_formats dropdown">
			<a class="btn btn-secondary" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true"
			   aria-expanded="false">
                {translate key="submission.howToCite.citationFormats"}
			</a>
			<div class="dropdown-menu" aria-labelledby="dropdownMenuButton" id="dropdown-cit">
                {foreach from=$citationStyles item="citationStyle"}
					<a
							class="dropdown-cite-link dropdown-item"
							aria-controls="citationOutput"
							href="{url page="citationstylelanguage" op="get" path=$citationStyle.id params=$citationArgs}"
							data-load-citation
							data-json-href="{url page="citationstylelanguage" op="get" path=$citationStyle.id params=$citationArgsJson}"
					>
                        {$citationStyle.title|escape}
					</a>
                {/foreach}
                {if count($citationDownloads)}
					<div class="dropdown-divider"></div>
					<h3 class="download-cite">
                        {translate key="submission.howToCite.downloadCitation"}
					</h3>
                    {foreach from=$citationDownloads item="citationDownload"}
						<a class="dropdown-cite-link dropdown-item"
						   href="{url page="citationstylelanguage" op="download" path=$citationDownload.id params=$citationArgs}">
                            {$citationDownload.title|escape}
						</a>
                    {/foreach}
                {/if}
			</div>
		</div>
	</div>
{/if}