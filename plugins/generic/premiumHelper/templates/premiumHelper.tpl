{* @file templates/frontend/objects/article_details.tpl
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Template pour l'interface d'analyse de résumé
 *}

{* Variables disponibles :
   - $pluginUrl: URL de base du plugin
   - $apiUrl: URL de l'endpoint d'API
   - $isPremiumUser: Booléen indiquant si l'utilisateur est premium
*}

{* Inclure les données pour JavaScript *}
{assign var="premiumHelperData" value=[]}
{capture assign="premiumHelperJson"}
    {
        "apiUrl": "{$apiUrl|escape:'javascript'}",
        "isPremiumUser": {if $isPremiumUser}true{else}false{/if}
    }
{/capture}
{php}
    $this->assign('premiumHelperData', json_decode($this->get_template_vars('premiumHelperJson'), true));
{/php}

{* Conteneur principal *}
<div id="premiumHelperContainer" class="pkp_helpers_align_center">
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
        id="premiumHelperResults" 
        class="pkp_helpers_align_center" 
        style="display: none; margin-top: 1rem;"
        aria-live="polite"
        aria-atomic="true"
    >
        {* Le contenu sera injecté dynamiquement par JavaScript *}
    </div>
    
    {* Template pour l'état de chargement *}
    <template id="premiumHelperLoadingTemplate">
        <div class="pkp_helpers_align_center">
            <span class="pkp_spinner"></span>
            <p>{translate key="plugins.generic.premiumHelper.analyzing"}</p>
        </div>
    </template>
    
    {* Template pour les résultats *}
    <template id="premiumHelperResultsTemplate">
        <div class="pkp_common_highlight">
            <h4>{translate key="plugins.generic.premiumHelper.results"}</h4>
            <div class="pkp_helpers_clear"></div>
            
            <div class="pkp_form">
                <div class="pkp_form_group">
                    <div class="label">
                        <label>{translate key="plugins.generic.premiumHelper.wordCount"}</label>
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
{load_script context="backend" scripts=$premiumHelperData}
{literal}
<script>
    // Initialiser les données du plugin
    document.addEventListener('DOMContentLoaded', function() {
        // Les données sont déjà disponibles via la variable premiumHelperData
        // définie dans le template Smarty ci-dessus
        window.premiumHelper = window.premiumHelper || {};
        window.premiumHelper = Object.assign(
            window.premiumHelper || {},
            {/literal}{$premiumHelperJson|@json_encode nofilter}{literal}
        );
    });
</script>
{/literal}
