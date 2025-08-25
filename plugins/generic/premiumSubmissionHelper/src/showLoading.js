/**
 * Affiche un indicateur de chargement
 * @param {HTMLElement} container - L'élément conteneur où afficher le chargement
 */
export function showLoading(container) {
    // Utiliser le template de chargement s'il existe
    const loadingTemplate = document.getElementById('premiumSubmissionHelperLoadingTemplate');
    
    if (loadingTemplate) {
        container.innerHTML = '';
        container.appendChild(loadingTemplate.content.cloneNode(true));
    } else {
        // Fallback si le template n'est pas disponible
        container.innerHTML = `
            <div class="pkp_helpers_align_center">
                <span class="pkp_spinner"></span>
                <p>Analyse en cours...</p>
            </div>
        `;
    }
    
    container.style.display = 'block';
    container.setAttribute('aria-busy', 'true');
}
