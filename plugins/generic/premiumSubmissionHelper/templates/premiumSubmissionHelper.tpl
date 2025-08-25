{* @file plugins/generic/premiumSubmissionHelper/templates/premiumSubmissionHelper.tpl
 *
 * @brief Template pour l'interface d'analyse de résumé
 *}

{* Variables disponibles :
   - $pluginUrl: URL de base du plugin
   - $apiUrl: URL de l'endpoint d'API
   - $isPremiumUser: Booléen indiquant si l'utilisateur est premium
*}

{* Inclure les données pour JavaScript *}
{assign var="premiumSubmissionHelperData" value=[]}
{capture assign="premiumSubmissionHelperJson"}
    {
        "apiUrl": "{$apiUrl|escape:'javascript'}",
        "isPremiumUser": {if $isPremiumUser}true{else}false{/if}
    }
{/capture}
{php}
    $this->assign('premiumSubmissionHelperData', json_decode($this->get_template_vars('premiumSubmissionHelperJson'), true));
{/php}

{* Conteneur principal *}
<div id="premiumSubmissionHelperContainer" class="pkp_helpers_align_center">
    {* Bouton d'analyse *}
    <button 
        id="analyzeAbstractBtn" 
        class="pkp_button pkp_button_primary"
        {if !$isPremiumUser}disabled title="{translate key='plugins.generic.premiumHelper.premiumRequired'}"{/if}
    >
        <span class="fa fa-chart-line"></span>
        {translate key="plugins.generic.premiumHelper.analyze"}
    </button>
    
    {* Zone des résultats *}
    <div 
        id="premiumSubmissionHelperResults" 
        class="pkp_common_highlight" 
        role="region" 
        aria-live="polite"
        aria-atomic="true"
        hidden>
        <!-- Les résultats seront injectés ici par JavaScript -->
    </div>
    
    {* Template pour l'état de chargement *}
    <template id="premiumSubmissionHelperLoadingTemplate">
        <div class="pkp_helpers_align_center">
            <span class="pkp_spinner"></span>
            <p>{translate key="plugins.generic.premiumSubmissionHelper.analyzing"}</p>
        </div>
    </template>
    
    {* Template pour les résultats *}
    <template id="premiumSubmissionHelperResultsTemplate">
        <div class="pkp_common_highlight">
            <h4>{translate key="plugins.generic.premiumSubmissionHelper.results"}</h4>
            <div class="pkp_helpers_clear"></div>
            
            <div class="pkp_form">
                <div class="pkp_form_group">
                    <div class="label">
                        <label>{translate key="plugins.generic.premiumSubmissionHelper.wordCount"}</label>
                    </div>
                    <div class="value" data-metric="wordCount">
                        <!-- Rempli dynamiquement -->
                    </div>
                </div>
                
                <div class="pkp_form_group">
                    <div class="label">
                        <label>{translate key="plugins.generic.premiumHelper.sentenceCount"}</label>
                    </div>
                    <div class="value" data-metric="sentenceCount">
                        <!-- Rempli dynamiquement -->
                    </div>
                </div>
                
                <div class="pkp_form_group">
                    <div class="label">
                        <label>{translate key="plugins.generic.premiumHelper.readabilityScore"}</label>
                    </div>
                    <div class="value" data-metric="readabilityScore">
                        <!-- Rempli dynamiquement -->
                    </div>
                </div>
                
                <div class="pkp_form_group">
                    <div class="label">
                        <label>{translate key="plugins.generic.premiumHelper.keywords"}</label>
                    </div>
                    <div class="value" data-metric="keywords">
                        <!-- Rempli dynamiquement -->
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

{* Initialiser le plugin JavaScript *}
{load_script context="backend" scripts=$premiumSubmissionHelperData}
{literal}
<script>
    // Initialiser les données du plugin
    document.addEventListener('DOMContentLoaded', function() {
        // Les données sont déjà disponibles via la variable premiumSubmissionHelperData
        window.premiumSubmissionHelper = window.premiumSubmissionHelper || {};
        window.premiumSubmissionHelper = Object.assign(
            window.premiumSubmissionHelper || {},
            {/literal}{$premiumSubmissionHelperJson|@json_encode nofilter}{literal}
        );
    });
</script>
{/literal}
