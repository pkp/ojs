<?php

/**
 * @file plugins/generic/premiumSubmissionHelper/pages/APIHandler.inc.php
 *
 * @class PremiumSubmissionHelperAPIHandler
 * @ingroup pages_api
 *
 * @brief Gestionnaire de l'API pour l'analyse de résumé
 *
 * Ce fichier contient la logique de traitement des requêtes d'analyse de texte.
 * Il gère la validation des entrées, l'analyse du texte et la génération des réponses JSON.
 *
 * Ce gestionnaire prend en charge :
 * - La validation des données d'entrée
 * - L'autorisation des utilisateurs
 * - La limitation du taux de requêtes
 * - La journalisation des activités
 */

declare(strict_types=1);

namespace APP\plugins\generic\premiumSubmissionHelper\pages;

// PKP classes
use PKP\core\JSONMessage;
use PKP\db\DAORegistry;
use PKP\handler\APIHandler;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\Role;
// External classes
use Exception;
use PKP\plugins\PluginRegistry;
use PKP\core\PKPRequest;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\security\authorization\PolicySet;
use PKP\security\authorization\RoleBasedHandlerOperationPolicy;
use PKP\session\SessionManager;
use PKP\core\PKPString;
use PKP\core\Registry;
use APP\plugins\generic\premiumSubmissionHelper\PremiumSubmissionHelperPlugin;
use APP\plugins\generic\premiumSubmissionHelper\classes\PremiumSubmissionHelperLog;
use PKP\security\authorization\UserRequiredPolicy;

/**
 * Gestionnaire des requêtes API pour l'analyse de texte
 *
 * Cette classe traite les requêtes d'analyse, valide les entrées,
 * et renvoie des métriques détaillées sur le texte fourni.
 */
class PremiumSubmissionHelperAPIHandler extends APIHandler
{
    /** @var PremiumSubmissionHelperPlugin */
    protected $plugin;

    /**
     * Constructor
     * @param PremiumSubmissionHelperPlugin $plugin
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
        $this->addPolicy(new ContextRequiredPolicy($request));
        $this->addPolicy(new UserRequiredPolicy($request));
        $this->addPolicy(new UserRolesRequiredPolicy($request), true);

        $rolePolicy = new PolicySet(PolicySet::COMBINING_PERMIT_OVERRIDES);
        $roles = [
            Role::ROLE_ID_SITE_ADMIN,
            Role::ROLE_ID_MANAGER,
            Role::ROLE_ID_SUB_EDITOR,
        ];

        foreach ($roles as $roleId) {
            $rolePolicy->addPolicy(
                new RoleBasedHandlerOperationPolicy(
                    $request,
                    $roleId,
                    $roleAssignments
                )
            );
        }

        $this->addPolicy($rolePolicy);

        // Vérification CSRF pour les requêtes POST
        if ($request->isPost() && !SessionManager::isCsrfValid()) {
            $this->logSecurityEvent($request, 'CSRF_VALIDATION_FAILED');
            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => __('form.csrfInvalid'),
                ],
                403
            );
        }

        // Vérification du taux de requêtes
        if (!$this->checkRateLimit($request)) {
            $this->logSecurityEvent($request, 'RATE_LIMIT_EXCEEDED');
            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => __('plugins.generic.premiumHelper.error.rateLimitExceeded'),
                ],
                429
            );
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Traite une requête d'analyse de résumé avec validation renforcée
     *
     * @param array $args Arguments de la requête
     * @param PKPRequest $request Objet de requête OJS
     * @return JSONMessage Réponse JSON
     */
    public function analyze($args, $request)
    {
        // Vérification de la méthode HTTP
        if (!$request->isPost()) {
            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => __('common.wrongMethod'),
                ],
                405
            );
        }

        // Récupération et validation des données
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => __('plugins.generic.premiumHelper.error.invalidJson'),
                ],
                400
            );
        }

        // Validation du résumé
        $abstract = $this->validateAndSanitizeAbstract($data['abstract'] ?? '');
        if ($abstract === null) {
            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => __('plugins.generic.premiumHelper.error.invalidAbstract'),
                ],
                400
            );
        }

        // Vérification de la longueur du résumé
        $maxLength = (int) $this->plugin->getSetting(
            $request->getContext()?->getId() ?? 0,
            'maxAbstractLength'
        ) ?: 10000; // Valeur par défaut de 10 000 caractères

        if (mb_strlen($abstract) > $maxLength) {
            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => __(
                        'plugins.generic.premiumHelper.error.abstractTooLong',
                        [
                            'max' => $maxLength,
                            'current' => mb_strlen($abstract),
                        ]
                    ),
                ],
                400
            );
        }

        // Journalisation de la tentative d'analyse
        $this->logSecurityEvent(
            $request,
            'ANALYSIS_ATTEMPT',
            [
                'abstract_length' => mb_strlen($abstract),
                'context_id' => $request->getContext()?->getId(),
            ]
        );

        // Effectuer l'analyse avec gestion des erreurs
        try {
            $startTime = microtime(true);
            $analysis = $this->analyzeAbstract($abstract);
            $processingTime = microtime(true) - $startTime;

            // Journalisation de l'analyse réussie
            $this->logSecurityEvent(
                $request,
                'ANALYSIS_SUCCESS',
                [
                    'processing_time' => round($processingTime, 3),
                    'word_count' => $analysis['word_count'] ?? 0,
                ]
            );

            // Réponse avec en-têtes de sécurité
            $response = $this->jsonResponse(
                [
                    'success' => true,
                    'data' => $analysis,
                    'meta' => [
                        'processing_time' => round($processingTime, 3),
                        'api_version' => '1.0.0',
                    ],
                ]
            );

            // Ajout des en-têtes de sécurité
            $response->setHeader('X-Content-Type-Options', 'nosniff');
            $response->setHeader('X-Frame-Options', 'DENY');
            $response->setHeader('X-XSS-Protection', '1; mode=block');
            $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

            return $response;
        } catch (\Exception $e) {
            // Journalisation de l'erreur
            $this->logSecurityEvent(
                $request,
                'ANALYSIS_ERROR',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            // Ne pas renvoyer de détails sensibles en production
            $errorMessage = APP_DEBUG
                ? $e->getMessage()
                : __('plugins.generic.premiumHelper.error.analysisFailed');

            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => $errorMessage,
                ],
                500
            );
        }
    }

    /**
     * Valide et nettoie le résumé
     *
     * @param mixed $abstract Résumé à valider
     * @return string|null Résumé nettoyé ou null si invalide
     */
    protected function validateAndSanitizeAbstract($abstract): ?string
    {
        if (!is_string($abstract) || empty(trim($abstract))) {
            return null;
        }

        // Nettoyage du texte
        $abstract = trim($abstract);
        $abstract = PKPString::stripUnsafeHtml($abstract);

        // Vérification de la longueur minimale
        if (mb_strlen($abstract) < 20) {
            return null;
        }

        return $abstract;
    }

    /**
     * Vérifie et applique une limite de débit
     *
     * @param PKPRequest $request
     * @return bool True si la requête est autorisée
     */
    protected function checkRateLimit(PKPRequest $request): bool
    {
        $userId = $request->getUser()?->getId();
        $ip = $request->getRemoteAddr();
        $cache = Registry::get('cache', true);

        if (!$cache) {
            return true; // Désactiver la limitation si le cache n'est pas disponible
        }

        $cacheKey = "rate_limit_{$userId}_{$ip}";
        $requests = (int) $cache->get($cacheKey);

        // Limite: 100 requêtes par minute par utilisateur/IP
        if ($requests > 100) {
            return false;
        }

        $cache->set($cacheKey, $requests + 1, 60); // Expire après 60 secondes
        return true;
    }

    /**
     * Journalise les événements de sécurité
     *
     * @param PKPRequest $request
     * @param string $eventType Type d'événement
     * @param array $data Données supplémentaires
     */
    /**
     * Journalise les événements de sécurité
     *
     * @param PKPRequest $request Requête courante
     * @param string $eventType Type d'événement
     * @param array $data Données supplémentaires
     * @return void
     */
    protected function logSecurityEvent(
        PKPRequest $request,
        string $eventType,
        array $data = []
    ): void {
        $user = $request->getUser();
        $context = $request->getContext();

        PremiumSubmissionHelperLog::logEvent(
            $context ? $context->getId() : 0,
            $user ? $user->getId() : null,
            null,
            'SECURITY_' . $eventType,
            'Security event: ' . $eventType,
            array_merge(
                $data,
                [
                    'ip' => $request->getRemoteAddr(),
                    'user_agent' => $request->getUserAgent(),
                    'request_uri' => $request->getRequestUrl(),
                ]
            )
        );
    }


    /**
     * Analyse le résumé et retourne des métriques utiles
     *
     * @param string $abstract Le résumé à analyser
     * @return array Les résultats de l'analyse
     */
    protected function analyzeAbstract($abstract): array
    {
        $settings = $this->plugin->getSettings();

        if (empty($abstract)) {
            return [
                'success' => false,
                'message' => __('plugins.generic.premiumHelper.error.abstractEmpty'),
            ];
        }

        // Vérifier la longueur du résumé
        $wordCount = str_word_count(strip_tags($abstract));

        if ($wordCount < $settings['minWordCount']) {
            return [
                'success' => false,
                'message' => __(
                    'plugins.generic.premiumHelper.error.abstractTooShort',
                    [
                        'minWords' => $settings['minWordCount'],
                        'currentWords' => $wordCount,
                    ]
                ),
            ];
        }

        if ($wordCount > $settings['maxWordCount']) {
            return [
                'success' => false,
                'message' => __(
                    'plugins.generic.premiumHelper.error.abstractTooLong',
                    [
                        'maxWords' => $settings['maxWordCount'],
                        'currentWords' => $wordCount,
                    ]
                ),
            ];
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
     * Extrait les mots-clés d'un texte
     *
     * @param string $text Le texte à analyser
     * @return array Tableau des mots-clés triés par fréquence décroissante
     */
    protected function extractKeywords($text): array
    {
        // Liste des mots vides à exclure
        $stopWords = [
            'le', 'la', 'les', 'de', 'des', 'du', 'un', 'une', 'et', 'ou',
            'que', 'qui', 'dont', 'où', 'à', 'au', 'aux', 'en', 'par', 'pour',
            'sur', 'dans', 'avec', 'sans', 'sous', 'mais', 'donc', 'or', 'ni',
            'car', 'si', 'est', 'sont', 'a', 'as', 'ai', 'ont', 'été', 'être',
            'ce', 'cet', 'cette', 'ces', 'mon', 'ton', 'son', 'notre', 'votre',
            'leur', 'mes', 'tes', 'ses', 'nos', 'vos', 'leurs',
        ];

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
     *
     * @param float $score Le score de lisibilité
     * @return string Le niveau de lisibilité
     */
    protected function getReadabilityLevel($score): string
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
     * Récupère les paramètres du plugin
     *
     * @param array $args Arguments de la requête
     * @param PKPRequest $request Requête actuelle
     * @return JSONMessage Réponse JSON contenant les paramètres
     */
    public function getSettings($args, $request): JSONMessage
    {
        $context = $request->getContext();
        if (!$context) {
            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => __('plugins.generic.premiumHelper.error.contextRequired'),
                ],
                400
            );
        }

        $settings = $this->plugin->getSetting($context->getId(), 'settings');
        if (empty($settings)) {
            return $this->jsonResponse(
                [
                    'success' => false,
                    'message' => __('plugins.generic.premiumHelper.error.settingsNotFound'),
                ],
                404
            );
        }

        return $this->jsonResponse(
            [
                'success' => true,
                'settings' => $settings,
            ]
        );
    }

    /**
     * Retourne une liste de mots vides à exclure de l'analyse
     *
     * @return array Liste des mots vides
     */
    private function getStopWords(): array
    {
        // Mots vides en français
        return [
            // Articles
            'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'd', 'au', 'aux', 'l', 'à',
            // Prépositions
            'dans', 'par', 'pour', 'avec', 'sans', 'sur', 'sous', 'vers', 'parmi', 'dès',
            'depuis', 'pendant', 'durant', 'jusque', 'contre', 'malgré', 'sauf', 'selon', 'chez',
            // Conjonctions
            'et', 'ou', 'mais', 'donc', 'car', 'ni', 'or', 'que', 'qui', 'quoi', 'dont', 'où',
            'lorsque', 'quoique', 'puisque', 'tandis', 'quand', 'comme', 'si', 'soit',
            // Adverbes
            'voici', 'voilà', 'afin', 'ainsi', 'alors', 'aussi', 'bien', 'déjà',
            'encore', 'ensuite', 'hors', 'même', 'parce', 'peu', 'plus', 'plutôt', 'puis',
            'surtout', 'tant', 'tard', 'tôt', 'toujours', 'très', 'trop',
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
    private function countSyllablesInText(string $text): int
    {
        // Nettoyer le texte
        $text = strip_tags($text);
        // Supprimer la ponctuation
        $text = preg_replace('/[^\p{L}\s]/u', '', $text);
        $text = mb_strtolower($text);

        // Diviser en mots
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $totalSyllables = 0;

        foreach ($words as $word) {
            // Règles simplifiées pour compter les syllabes en français
            // 1. Compter les groupes de voyelles (approximation)
            $syllables = preg_match_all(
                '/[aeiouyàâäéèêëîïôöùûüœæ]+/i',
                $word,
                $matches
            );

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
     * @return int Score de lisibilité (0-100)
     */
    private function calculateReadabilityScore(
        int $wordCount,
        int $sentenceCount,
        int $syllableCount
    ): int {
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
        return (int) round(max(0, min(100, $score)));
    }

    /**
     * Retourne une réponse JSON formatée avec des en-têtes de sécurité
     *
     * @param array $data Données à renvoyer
     * @param int $statusCode Code HTTP de statut (par défaut: 200)
     * @return JSONMessage Réponse JSON formatée
     */
    private function jsonResponse(array $data, int $statusCode = 200): JSONMessage
    {
        $response = new JSONMessage($data);
        $response->setHttpStatus($statusCode);

        // Configuration des en-têtes de sécurité
        $securityHeaders = [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'"
        ];

        foreach ($securityHeaders as $header => $value) {
            $response->setHeader($header, $value);
        }

        return $response;
    }
}
