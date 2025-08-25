/**
 * Plugin Premium Submission Helper - Analyse de résumé
 *
 * Ce module gère l'interface utilisateur pour l'analyse de résumé, y compris :
 * - La gestion des interactions utilisateur
 * - Les appels asynchrones à l'API d'analyse
 * - L'affichage des résultats et des messages d'erreur
 *
 * @module premiumSubmissionHelper
 */

import { showLoading } from '../src/showLoading';

// Exposer la fonction showLoading globalement pour la rétrocompatibilité
window.showLoading = showLoading;

// Initialisation du module lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function () {
    // Cette fonction est le point d'entrée principal du script
    // Elle initialise les écouteurs d'événements et configure l'interface
    // Éléments du DOM
    const analyzeBtn = document.getElementById('analyzeAbstractBtn');
    const abstractField = document.querySelector('textarea[name="abstract"]');
    const analysisResults = document.getElementById('premiumSubmissionHelperResults');

    // Vérifier que les éléments nécessaires sont présents
    if (!analyzeBtn || !abstractField || !analysisResults) {
        console.warn('Premium Submission Helper: Certains éléments requis sont manquants dans le DOM');
        return;
    }

    // Désactiver le bouton si l'utilisateur n'est pas premium
    if (typeof premiumHelper !== 'undefined' && !premiumHelper.isPremiumUser) {
        analyzeBtn.disabled = true;
        analyzeBtn.title = 'Cette fonctionnalité est réservée aux utilisateurs premium';
        return;
    }

    /**
     * Gestionnaire d'événement pour le clic sur le bouton d'analyse
     * Valide l'entrée et lance le processus d'analyse
     */
    analyzeBtn.addEventListener('click', function (e) {
        e.preventDefault();

        const abstract = abstractField.value.trim();

        // Vérifier que le résumé n'est pas vide
        if (!abstract) {
            showNotification('Veuillez d\'abord saisir un résumé à analyser.', 'error');
            return;
        }

        // Vérifier la longueur minimale du résumé
        if (abstract.split(/\s+/).length < 10) {
            showNotification('Le résumé doit contenir au moins 10 mots.', 'error');
            return;
        }

        // Afficher l'indicateur de chargement
        showLoading(analysisResults);

        // Récupérer le jeton CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Construire l'URL de l'API
        const apiUrl = typeof premiumHelper !== 'undefined' ?
            premiumHelper.apiUrl :
            window.location.origin + '/index.php/index/premiumHelper/analyze';

        // Afficher le bouton comme étant en cours d'utilisation
        analyzeBtn.disabled = true;
        analyzeBtn.innerHTML = '<span class="fa fa-spinner fa-spin"></span> Analyse en cours...';

        // Envoyer la requête à l'API
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Csrf-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                abstract: abstract
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || `Erreur HTTP: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayAnalysisResults(data.data);
                showNotification('Analyse terminée avec succès !', 'success');
            } else {
                throw new Error(data.message || 'Erreur inconnue lors de l\'analyse');
            }
        })
        .catch(error => {
            console.error('Premium Helper - Erreur:', error);
            showNotification(
                'Erreur lors de l\'analyse: ' + (error.message || 'Veuillez réessayer plus tard.'),
                'error'
            );
            analysisResults.style.display = 'none';
        })
        .finally(() => {
            // Réactiver le bouton
            analyzeBtn.disabled = false;
            analyzeBtn.innerHTML = '<span class="fa fa-chart-line"></span> Exécuter l\'analyse Santaane AI';
        });
    });

    /**
     * Affiche les résultats de l'analyse
     * @param {Object} data - Les données d'analyse à afficher
     */
    function displayAnalysisResults(data)
    {
        const container = document.getElementById('premiumHelperResults');
        if (!container) {
            return;
        }

        // Utiliser le template de résultats s'il existe
        const resultsTemplate = document.getElementById('premiumHelperResultsTemplate');

        if (resultsTemplate) {
            container.innerHTML = '';
            container.appendChild(resultsTemplate.content.cloneNode(true));

            // Remplir les données
            if (data.wordCount !== undefined) {
                const element = container.querySelector('[data-metric="wordCount"]');
                if (element) {
                    element.textContent = data.wordCount;
                }
            }

            if (data.sentenceCount !== undefined) {
                const element = container.querySelector('[data-metric="sentenceCount"]');
                if (element) {
                    element.textContent = data.sentenceCount;
                }
            }

            if (data.avgSentenceLength !== undefined) {
                const element = container.querySelector('[data-metric="avgSentenceLength"]');
                if (element) {
                    element.textContent = data.avgSentenceLength;
                }
            }

            if (data.readabilityScore !== undefined) {
                const element = container.querySelector('[data-metric="readabilityScore"]');
                if (element) {
                    element.textContent = `${data.readabilityScore} / 100`;
                    element.setAttribute('title', `Niveau: ${data.readabilityLevel || 'N/A'}`);
                }
            }

            if (data.keywords && Array.isArray(data.keywords)) {
                const element = container.querySelector('[data-metric="keywords"]');
                if (element) {
                    element.innerHTML = data.keywords
                        .map(keyword => ` < span class = "keyword-tag" > ${escapeHtml(keyword)} < / span > `)
                        .join('');
                }
            }
        } else {
            // Fallback si le template n'est pas disponible
            let html = `
                < div class = "pkp_common_highlight" >
                    < h4 > Résultats de l'analyse < / h4 >
                    < div class = "pkp_helpers_clear" > < / div >
                    < div class = "pkp_form" >
                        < div class = "pkp_form_group" >
                            < div class = "label" >
                                < label > Nombre de mots < / label >
                            <  / div >
                            < div class = "value" > ${data.wordCount || 'N/A'} < / div >
                        <  / div >
                        < div class = "pkp_form_group" >
                            < div class = "label" >
                                < label > Nombre de phrases < / label >
                            <  / div >
                            < div class = "value" > ${data.sentenceCount || 'N/A'} < / div >
                        <  / div >
                        < div class = "pkp_form_group" >
                            < div class = "label" >
                                < label > Longueur moyenne des phrases < / label >
                            <  / div >
                            < div class = "value" > ${data.avgSentenceLength || 'N/A'} mots < / div >
                        <  / div >
                        < div class = "pkp_form_group" >
                            < div class = "label" >
                                < label > Score de lisibilité < / label >
                            <  / div >
                            < div class = "value" title = "Niveau: ${data.readabilityLevel || 'N/A'}" >
                                ${data.readabilityScore ? `${data.readabilityScore} / 100` : 'N/A'}
                            <  / div >
                        <  / div >
                        < div class = "pkp_form_group" >
                            < div class = "label" >
                                < label > Mots - clés < / label >
                            <  / div >
                            < div class = "value" >
                                ${data.keywords && data.keywords.length
                                    ? data.keywords.map(k => ` < span class = "keyword-tag" > ${escapeHtml(k)} < / span > `).join('')
                                    : 'Aucun mot-clé significatif trouvé'}
                            <  / div >
                            <  / div >
                            <  / div >
                            <  / div >
                            `;
                            container.innerHTML = html;
        }

        container.style.display = 'block';
        container.setAttribute('aria-busy', 'false');

        // Faire défiler jusqu'aux résultats
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Affiche une notification à l'utilisateur
     * @param {string} message - Le message à afficher
     * @param {string} type - Le type de notification (success, error, warning, info)
     */
    function showNotification(message, type = 'info')
    {
        // Utiliser le système de notification d'OJS s'il est disponible
        if (typeof pkp !== 'undefined' && typeof pkp.eventBus !== 'undefined') {
            const notificationType = type === 'error' ? 'error' : 'success';
            pkp.eventBus.$emit('notify', {
                type: notificationType,
                message: message,
                isHtml: false
            });
            return;
        }

        // Fallback pour un système de notification personnalisé
        const notification = document.createElement('div');
        notification.className = `pkp_notification ${type}`;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'polite');

        // Ajouter une icône en fonction du type
        let icon = 'fa-info-circle';
        if (type === 'error') {
            icon = 'fa-exclamation-circle';
        }
        if (type === 'success') {
            icon = 'fa-check-circle';
        }
        if (type === 'warning') {
            icon = 'fa-exclamation-triangle';
        }

        notification.innerHTML = `
            < span class = "fa ${icon}" aria - hidden = "true" > < / span >
            < span class = "message" > ${message} < / span >
            < button type = "button" class = "close" aria - label = "Fermer" >
                < span class = "fa fa-times" aria - hidden = "true" > < / span >
            <  / button >
        `;

        // Ajouter un gestionnaire d'événement pour le bouton de fermeture
        const closeButton = notification.querySelector('.close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            });
        }

        // Ajouter la notification au document
        document.body.appendChild(notification);

        // Forcer le navigateur à traiter l'ajout avant d'appliquer la transition
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateY(0)';
        }, 10);

        // Supprimer la notification après 5 secondes
        const timeoutId = setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-100%)';

            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 5000);

        // Arrêter le minuteur si l'utilisateur survole la notification
        notification.addEventListener('mouseenter', () => {
            clearTimeout(timeoutId);
        });

        // Redémarrer le minuteur quand l'utilisateur quitte la notification
        notification.addEventListener('mouseleave', () => {
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-100%)';

                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 2000);
        });
    }

    /**
     * Échappe le HTML pour éviter les injections XSS
     * @param {string} unsafe - Le texte à échapper
     * @return {string} Le texte échappé
     */
    function escapeHtml(unsafe)
    {
        if (typeof unsafe !== 'string') {
            return '';
        }
        return unsafe
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
