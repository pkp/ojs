# Documentation de configuration du plugin Premium Helper

## Aperçu
Ce document décrit les paramètres de configuration disponibles pour le plugin Premium Helper.

## Paramètres généraux

### `enabled`
- **Type** : Booléen
- **Valeur par défaut** : `true`
- **Description** : Active ou désactive le plugin.

## Paramètres de comptage de mots

### `minWordCount`
- **Type** : Entier
- **Valeur par défaut** : `50`
- **Plage** : 50-1000
- **Description** : Nombre minimum de mots requis pour l'analyse.

### `maxWordCount`
- **Type** : Entier
- **Valeur par défaut** : `300`
- **Plage** : 100-5000
- **Description** : Nombre maximum de mots autorisé pour l'analyse.

## Paramètres d'affichage

### `readabilityThreshold`
- **Type** : Entier
- **Valeur par défaut** : `60`
- **Plage** : 0-100
- **Description** : Seuil de lisibilité (en pourcentage).

### `showWordCount`
- **Type** : Booléen
- **Valeur par défaut** : `true`
- **Description** : Affiche le nombre de mots dans l'analyse.

### `showSentenceCount`
- **Type** : Booléen
- **Valeur par défaut** : `true`
- **Description** : Affiche le nombre de phrases dans l'analyse.

### `showReadabilityScore`
- **Type** : Booléen
- **Valeur par défaut** : `true`
- **Description** : Affiche le score de lisibilité.

## Paramètres d'analyse avancée

### `maxKeywords`
- **Type** : Entier
- **Valeur par défaut** : `10`
- **Description** : Nombre maximum de mots-clés à extraire.

### `enableAdvancedAnalysis`
- **Type** : Booléen
- **Valeur par défaut** : `false`
- **Description** : Active l'analyse avancée (consomme plus de ressources).

### `customStopWords`
- **Type** : Chaîne
- **Valeur par défaut** : `""`
- **Format** : Mots séparés par des virgules
- **Description** : Mots vides personnalisés à exclure de l'analyse.

## Paramètres de débogage

### `enableDebugMode`
- **Type** : Booléen
- **Valeur par défaut** : `false`
- **Description** : Active le mode débogage (pour les développeurs).

### `logRetentionDays`
- **Type** : Entier
- **Valeur par défaut** : `90`
- **Plage** : 1-365
- **Description** : Nombre de jours de conservation des journaux.
