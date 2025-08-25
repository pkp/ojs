<?php

namespace APP\plugins\generic\premiumSubmissionHelper\pages;

use PKP\handler\APIHandler;
use PKP\core\JSONMessage;
use PKP\db\DAORegistry;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\Role;
use PKP\plugins\PluginRegistry;

/**
 * @file plugins/generic/premiumSubmissionHelper/pages/APIHandler.inc.php
 *
 * Copyright (c) 2025 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumSubmissionHelperAPIHandler
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Gestionnaire de l'API pour l'analyse de résumé
 *
 * Ce fichier contient la logique de traitement des requêtes d'analyse de texte.
 * Il gère la validation des entrées, l'analyse du texte et la génération des réponses JSON.
 */

import('lib.pkp.classes.handler.PKPHandler');

/**
 * Gestionnaire des requêtes API pour l'analyse de texte
 *
 * Cette classe traite les requêtes d'analyse, valide les entrées,
 * et renvoie des métriques détaillées sur le texte fourni.
 */
class PremiumSubmissionHelperAPIHandler extends PKPHandler
{
    /** @var \APP\plugins\generic\premiumSubmissionHelper\PremiumSubmissionHelperPlugin Le plugin */
    protected $plugin;

    /**
     * Constructeur
     * @param PremiumHelperPlugin $plugin Instance du plugin
     */
    public function __construct($plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;

        // Autoriser uniquement les utilisateurs connectés avec les rôles appropriés
        $this->addRoleAssignment(
            [ROLE_ID_AUTHOR, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR],
            ['analyze']
        );
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
        $this->addPolicy(new ContextRequiredPolicy($request));

        // Vérifier que l'utilisateur est connecté et a un rôle autorisé
        $context = $request->getContext();
        $user = $request->getUser();

        if (!$user || !$context) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('user.authorization.userRequired')
            ], 403);
        }

        // Vérifier que l'utilisateur a un rôle autorisé
        if (!$this->plugin->isUserPremium($user, $context)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.premiumRequired')
            ], 403);
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Gère la requête d'analyse de résumé
     *
     * @param array $args Arguments de la requête
     * @param PKPRequest $request Requête actuelle
     */
    /**
     * Traite une requête d'analyse de résumé
     *
     * Point d'entrée principal pour l'API d'analyse. Valide la requête,
     * effectue l'analyse et renvoie les résultats au format JSON.
     *
     * @param array $args Arguments de la requête
     * @param PKPRequest $request Objet de requête OJS
     * @return JSONMessage Réponse JSON contenant les résultats ou une erreur
     */
    public function analyze($args, $request)
    {
        if (!$request->isPost()) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('common.wrongMethod')
            ], 405);
        }

        if (!SessionManager::isCsrfValid()) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('form.csrfInvalid')
            ], 400);
        }

        $abstract = $request->getUserVar('abstract');
        if (empty($abstract)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.emptyAbstract')
            ], 400);
        }

        // Effectuer l'analyse
        try {
            $analysis = $this->analyzeAbstract($abstract);

            return $this->jsonResponse([
                'success' => true,
                'data' => $analysis
            ]);
        } catch (Exception $e) {
            error_log('Erreur lors de l\'analyse du résumé: ' . $e->getMessage());

            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.analysisFailed')
            ], 500);
        }

        // Récupérer les données JSON
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Valider les données
        if (!isset($data['abstract']) || !is_string($data['abstract'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.abstractRequired')
            ], 400);
        }

        $abstract = trim($data['abstract']);

        if (empty($abstract)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.abstractEmpty')
            ], 400);
        }

        // Vérifier la longueur du résumé
        $wordCount = str_word_count(strip_tags($abstract));
        if ($wordCount < $settings['minWordCount']) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.abstractTooShort', [
                    'minWords' => $settings['minWordCount'],
                    'currentWords' => $wordCount
                ])
            ], 400);
        }

        if ($wordCount > $settings['maxWordCount']) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.abstractTooLong', [
                    'maxWords' => $settings['maxWordCount'],
                    'currentWords' => $wordCount
                ])
            ], 400);
        }

        try {
            // Analyser le texte
            $wordCount = str_word_count(strip_tags($abstract));
            $sentenceCount = max(1, preg_match_all('/[.!?]+/', $abstract, $matches));

            // Calculer le score de lisibilité (indice Flesch-Kincaid simplifié)
            $syllableCount = $this->countSyllablesInText($abstract);
            $readabilityScore = $this->calculateReadabilityScore($wordCount, $sentenceCount, $syllableCount);

            // Préparer les données pour l'analyse des mots-clés
            $words = str_word_count(mb_strtolower($abstract), 1);
            $wordFreq = array_count_values($words);
            arsort($wordFreq);

            // Obtenir les mots vides (par défaut + personnalisés)
            $stopWords = $this->getStopWords();
            if (!empty($settings['customStopWords'])) {
                $customStopWords = array_map('trim', explode(',', $settings['customStopWords']));
                $stopWords = array_merge($stopWords, $customStopWords);
            }

            // Filtrer les mots-clés
            $keywords = [];
            foreach ($wordFreq as $word => $count) {
                // Vérifier la longueur minimale et l'exclusion des mots vides
                $word = trim($word, ".,;:!?()\"'“”‘’\t\n\r\0\x0B");
                if (mb_strlen($word) >= 4 && !in_array(mb_strtolower($word), $stopWords)) {
                    $keywords[] = [
                        'word' => $word,
                        'count' => $count,
                        'frequency' => round(($count / $wordCount) * 100, 2)
                    ];
                }

                // Limiter le nombre de mots-clés
                if (count($keywords) >= $settings['maxKeywords']) {
                    break;
                }
            }

            // Trier les mots-clés par fréquence décroissante
            usort($keywords, function ($a, $b) {
                return $b['count'] - $a['count'];
            });

            // Extraire uniquement les mots pour la réponse
            $keywordList = array_column($keywords, 'word');

            // Journaliser l'analyse
            import('lib.pkp.classes.log.SubmissionLog');
            import('classes.log.SubmissionEventLogEntry');

            $context = $request->getContext();
            if ($context) {
                $submissionId = $request->getUserVar('submissionId');
                if ($submissionId) {
                    $submission = Services::get('submission')->get($submissionId);
                    if ($submission) {
                        SubmissionLog::logEvent(
                            $request,
                            $submission,
                            SUBMISSION_LOG_ABSTRACT_ANALYZED,
                            'plugins.generic.premiumHelper.log.analyzed',
                            [
                                'username' => $user->getUsername(),
                                'wordCount' => $wordCount,
                                'sentenceCount' => $sentenceCount
                            ]
                        );
                    }
                }
            }

            // Préparer les résultats
            $results = [
                'success' => true,
                'wordCount' => $wordCount,
                'sentenceCount' => $sentenceCount,
                'syllableCount' => $syllableCount,
                'readabilityScore' => $readabilityScore,
                'keywords' => $keywordList,
                'keywordDetails' => $keywords,
                'settings' => [
                    'minWordCount' => $settings['minWordCount'],
                    'maxWordCount' => $settings['maxWordCount'],
                    'readabilityThreshold' => $settings['readabilityThreshold'],
                    'showWordCount' => $settings['showWordCount'],
                    'showSentenceCount' => $settings['showSentenceCount'],
                    'showReadabilityScore' => $settings['showReadabilityScore'],
                    'maxKeywords' => $settings['maxKeywords']
                ]
            ];

            // Journaliser l'analyse si le mode debug est activé
            if ($settings['enableDebugMode']) {
                error_log('PremiumHelper - Analyse effectuée: ' . json_encode([
                    'contextId' => $context->getId(),
                    'userId' => $user->getId(),
                    'wordCount' => $wordCount,
                    'sentenceCount' => $sentenceCount,
                    'readabilityScore' => $readabilityScore,
                    'keywords' => $keywordList
                ]));
            }

            // Retourner la réponse
            return $this->jsonResponse($results);
        } catch (Exception $e) {
            error_log('Erreur lors de l\'analyse du résumé: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.analysisFailed')
            ], 500);
        }
    }

    /**
     * Analyse le résumé et retourne des métriques utiles
     * @param string $abstract Le résumé à analyser
     * @return array Les résultats de l'analyse
     */
    protected function analyzeAbstract($abstract)
    {
        // Nettoyer le texte
        $abstract = trim(strip_tags($abstract));

        // Compter les mots (séparés par des espaces, des tabulations ou des retours à la ligne)
        $wordCount = count(preg_split('/\s+/', $abstract));

        // Compter les phrases (séparées par . ! ? suivi d'un espace ou d'une majuscule)
        $sentenceCount = preg_match_all('/[.!?]\s+[A-Z]|[.!?]$/u', $abstract) + 1;

        // Calculer la longueur moyenne des phrases
        $avgSentenceLength = $sentenceCount > 0 ? $wordCount / $sentenceCount : 0;

        // Calculer un score de lisibilité (formule simplifiée de Flesch-Kincaid)
        $readabilityScore = 206.835 - 1.015 * ($wordCount / max(1, $sentenceCount)) - 84.6 * $this->countSyllablesInText($abstract) / max(1, $wordCount);
        $readabilityScore = max(0, min(100, round($readabilityScore, 1)));

        // Extraire les mots-clés (mots les plus fréquents, en excluant les mots vides)
        $keywords = $this->extractKeywords($abstract);

        // Retourner les résultats
        return [
            'wordCount' => $wordCount,
            'sentenceCount' => $sentenceCount,
            'avgSentenceLength' => round($avgSentenceLength, 1),
            'readabilityScore' => $readabilityScore,
            'readabilityLevel' => $this->getReadabilityLevel($readabilityScore),
            'keywords' => array_slice($keywords, 0, 10), // Limiter à 10 mots-clés
            'analysisDate' => date('Y-m-d H:i:s')
        ];
    }


    /**
     * Extrait les mots-clés d'un texte
     * @param string $text Le texte à analyser
     * @return array Tableau des mots-clés triés par fréquence décroissante
     */
    protected function extractKeywords($text)
    {
        // Liste des mots vides à exclure
        $stopWords = ['le', 'la', 'les', 'de', 'des', 'du', 'un', 'une', 'et', 'ou', 'que', 'qui', 'dont', 'où', 'à', 'au', 'aux', 'en', 'par', 'pour', 'sur', 'dans', 'avec', 'sans', 'sous', 'mais', 'donc', 'or', 'ni', 'car', 'si', 'est', 'sont', 'a', 'as', 'ai', 'ont', 'été', 'être', 'été', 'être', 'ce', 'cet', 'cette', 'ces', 'mon', 'ton', 'son', 'notre', 'votre', 'leur', 'mes', 'tes', 'ses', 'nos', 'vos', 'leurs'];

        // Nettoyer le texte
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\s]/u', ' ', $text);

        // Compter les occurrences des mots
        $words = preg_split('/\s+/', $text);
        $wordCounts = [];

        foreach ($words as $word) {
            $word = trim($word);

            // Ignorer les mots vides et les mots trop courts
            if (mb_strlen($word) > 2 && !in_array($word, $stopWords)) {
                if (!isset($wordCounts[$word])) {
                    $wordCounts[$word] = 0;
                }
                $wordCounts[$word]++;
            }
        }

        // Trier par fréquence décroissante
        arsort($wordCounts);

        return array_keys($wordCounts);
    }

    /**
     * Détermine le niveau de lisibilité en fonction du score
     * @param float $score Le score de lisibilité
     * @return string Le niveau de lisibilité
     */
    protected function getReadabilityLevel($score)
    {
        if ($score >= 90) {
            return 'Très facile';
        }
        if ($score >= 80) {
            return 'Facile';
        }
        if ($score >= 70) {
            return 'Assez facile';
        }
        if ($score >= 60) {
            return 'Standard';
        }
        if ($score >= 50) {
            return 'Assez difficile';
        }
        if ($score >= 30) {
            return 'Difficile';
        }
        return 'Très difficile';
    }

    /**
     * Get plugin settings
     * @param array $args Arguments de la requête
     * @param PKPRequest $request Requête actuelle
     * @return array
     */
    public function getSettings($args, $request)
    {
        $context = $request->getContext();
        if (!$context) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.contextRequired')
            ], 400);
        }

        $settings = $this->plugin->getSetting($context->getId(), 'settings');
        if (empty($settings)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.settingsNotFound')
            ], 404);
        }

        return $this->jsonResponse([
            'success' => true,
            'settings' => $settings
        ]);
    }

    /**
     * Retourne une liste de mots vides à exclure de l'analyse
     *
     * @return array Liste des mots vides
     */
    private function getStopWords()
    {
        // Mots vides en français
        $frenchStopWords = [
            // Articles
            'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'd', 'au', 'aux', 'l', 'à',
            // Prépositions
            'à', 'dans', 'par', 'pour', 'avec', 'sans', 'sur', 'sous', 'vers', 'parmi', 'dès',
            'depuis', 'pendant', 'durant', 'jusque', 'contre', 'malgré', 'sauf', 'selon', 'chez',
            // Conjonctions
            'et', 'ou', 'mais', 'donc', 'car', 'ni', 'or', 'que', 'qui', 'quoi', 'dont', 'où',
            'lorsque', 'quoique', 'puisque', 'tandis', 'quand', 'comme', 'si', 'soit',
            // Adverbes
            'voici', 'voilà', 'afin', 'ainsi', 'alors', 'aussi', 'bien', 'comme', 'déjà',
            'encore', 'ensuite', 'hors', 'même', 'parce', 'peu', 'plus', 'plutôt', 'puis',
            'quand', 'si', 'surtout', 'tant', 'tard', 'tôt', 'toujours', 'très', 'trop',
            'autant', 'beaucoup', 'bientôt', 'cependant', 'certes', 'davantage', 'déjà', 'enfin',
            // Pronoms
            'je', 'tu', 'il', 'elle', 'nous', 'vous', 'ils', 'elles', 'me', 'te', 'se', 'y', 'en',
            'moi', 'toi', 'lui', 'leur', 'eux', 'elles', 'celles', 'ceux', 'celui', 'celle',
            // Autres
            'ce', 'cet', 'cette', 'ces', 'ceux', 'celui', 'celle', 'celles', 'ceux-ci', 'celui-là',
            'on', 'nous', 'vous', 'ils', 'elles', 'leur', 'leurs', 'lui', 'leur', 'eux', 'elles'
        ];

        // Mots vides en anglais (pour les revues bilingues)
        $englishStopWords = [
            'a', 'an', 'the', 'and', 'or', 'but', 'if', 'because', 'as', 'until', 'while', 'of',
            'at', 'by', 'for', 'with', 'about', 'against', 'between', 'into', 'through', 'during',
            'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off',
            'over', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where',
            'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such',
            'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'can', 'will',
            'just', 'should', 'now', 'd', 'll', 'm', 'o', 're', 've', 'y', 'ain', 'aren', 'couldn',
            'didn', 'doesn', 'hadn', 'hasn', 'haven', 'isn', 'ma', 'mightn', 'mustn', 'needn',
            'shan', 'shouldn', 'wasn', 'weren', 'won', 'wouldn'
        ];

        // Fusionner et dédoublonner
        return array_unique(array_merge($frenchStopWords, $englishStopWords));
    }

    /**
     * Compte le nombre de syllabes dans un texte
     *
     * @param string $text Texte à analyser
     * @return int Nombre de syllabes
     */
    private function countSyllablesInText($text)
    {
        // Nettoyer le texte
        $text = strip_tags($text);
        $text = preg_replace('/[^\p{L}\s]/u', '', $text); // Supprimer la ponctuation
        $text = mb_strtolower($text);

        // Diviser en mots
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $totalSyllables = 0;

        foreach ($words as $word) {
            // Règles simplifiées pour compter les syllabes en français
            // 1. Compter les groupes de voyelles (approximation)
            $syllables = preg_match_all('/[aeiouyàâäéèêëîïôöùûüœæ]+/i', $word, $matches);

            // 2. Ajustements pour les mots courts
            if (mb_strlen($word) <= 3) {
                $syllables = 1;
            }

            // 3. Un mot doit avoir au moins une syllabe
            $totalSyllables += max(1, $syllables);
        }

        return $totalSyllables;
    }

    /**
     * Calcule un score de lisibilité (indice Flesch-Kincaid adapté au français)
     *
     * @param int $wordCount Nombre de mots
     * @param int $sentenceCount Nombre de phrases
     * @param int $syllableCount Nombre de syllabes
     * @return float Score de lisibilité (0-100)
     */
    private function calculateReadabilityScore($wordCount, $sentenceCount, $syllableCount)
    {
        if ($wordCount === 0 || $sentenceCount === 0) {
            return 0;
        }

        // Moyenne de syllabes par mot
        $syllablesPerWord = $syllableCount / $wordCount;

        // Moyenne de mots par phrase
        $wordsPerSentence = $wordCount / $sentenceCount;

        // Formule adaptée du Flesch-Kincaid pour le français
        // Plus le score est élevé, plus le texte est facile à lire
        $score = 207 - (1.015 * $wordsPerSentence) - (73.6 * $syllablesPerWord);

        // Normaliser entre 0 et 100
        $score = max(0, min(100, $score));

        return (int) round($score);
    }
}
