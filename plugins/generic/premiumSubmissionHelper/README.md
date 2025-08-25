# Aide à la soumission Premium pour OJS

> **Propriétaire du plugin** : Saliou Ngom  
> **Dépôt GitHub** : [github.com/Salioungom/ojs](https://github.com/Salioungom/ojs)

## 📁 Structure du projet

```
premiumSubmissionHelper/
├── classes/                    # Classes principales du plugin
│   ├── PremiumSubmissionHelperLog.php        # Modèle pour la journalisation
│   └── PremiumSubmissionHelperLogDAO.php     # Accès aux données de journalisation
├── js/                        # Fichiers JavaScript
│   └── premiumSubmissionHelper.js    # Scripts côté client
├── locale/                    # Fichiers de traduction
│   ├── en_US/                 # Traductions anglaises
│   └── fr_FR/                 # Traductions françaises
├── pages/                     # Gestionnaires de pages
│   └── APIHandler.php         # Gestionnaire des appels API
├── templates/                 # Modèles de vue
│   └── premiumSubmissionHelper.tpl  # Template principal
├── vendor/                    # Dépendances externes
├── .gitignore                 # Fichiers ignorés par Git
├── LICENSE                   # Licence du plugin
├── PremiumSubmissionHelperPlugin.php  # Point d'entrée principal
└── version.xml               # Version du plugin
```

## 🔄 Interactions entre les composants

1. **Flux d'analyse d'un résumé**
   - L'utilisateur clique sur "Analyser le résumé" dans l'interface
   - `premiumSubmissionHelper.js` capture l'événement et envoie une requête AJAX
   - `APIHandler.php` reçoit la requête, valide les données et les permissions
   - `PremiumSubmissionHelperLogDAO` enregistre l'événement dans la base de données
   - Les résultats sont renvoyés et affichés à l'utilisateur

2. **Sécurité**
   - Toutes les requêtes passent par le système d'autorisation d'OJS
   - Protection CSRF intégrée
   - Validation stricte des entrées
   - Journalisation des événements importants

3. **Internationalisation**
   - Les textes sont extraits dans des fichiers de traduction
   - Support multilingue via le système de localisation d'OJS

## Description

Ce plugin pour Open Journal Systems (OJS) ajoute une fonctionnalité d'analyse avancée des résumés pour les utilisateurs premium. Il permet d'analyser le contenu des résumés soumis et fournit des métriques utiles comme le comptage de mots, le nombre de phrases, un score de lisibilité et des suggestions de mots-clés.

## Fonctionnalités

- Analyse en temps réel des résumés
- Comptage des mots et des phrases
- Calcul d'un score de lisibilité
- Extraction automatique de mots-clés pertinents
- Interface utilisateur intégrée au formulaire de soumission OJS
- Support multilingue (français par défaut)
- Restriction aux utilisateurs premium

## Prérequis

- OJS version 3.4.0 ou supérieure
- PHP 8.3 ou supérieur
- Extensions PHP requises : json, mbstring, ctype

## Installation

1. Téléchargez la dernière version du plugin
2. Décompressez l'archive dans le répertoire `plugins/generic/` de votre installation OJS
3. Renommez le dossier en `premiumSubmissionHelper`
4. Assurez-vous que les permissions sont correctement définies :
   ```bash
   chmod -R 755 plugins/generic/premiumSubmissionHelper
   chown -R www-data:www-data plugins/generic/premiumSubmissionHelper
   ```
5. Connectez-vous à l'interface d'administration d'OJS
6. Allez dans Paramètres > Site Web > Plugins
7. Trouvez "Aide à la soumission Premium" dans la section Plugins génériques
8. Cliquez sur "Activer"
9. Pour les mises à jour, suivez les mêmes étapes après avoir sauvegardé vos données

## Configuration

1. Allez dans Paramètres > Site Web > Plugins
2. Trouvez "Aide à la soumission Premium" dans la section Plugins génériques
3. Cliquez sur "Paramètres" pour configurer les options du plugin
4. Enregistrez vos modifications

## Utilisation

1. Connectez-vous avec un compte utilisateur ayant les droits de soumission
2. Commencez une nouvelle soumission ou modifiez une soumission existante
3. Sur la page des métadonnées, vous verrez un bouton "Analyser le résumé"
4. Cliquez sur le bouton pour lancer l'analyse
5. Les résultats s'afficheront sous le bouton

## ⚙️ Configuration avancée

### Variables d'environnement

Le plugin supporte les variables d'environnement suivantes :

- `PREMIUM_HELPER_MAX_REQUESTS`: Nombre maximum de requêtes par minute (défaut: 100)
- `PREMIUM_HELPER_DEBUG`: Active le mode débogage (défaut: false)
- `PREMIUM_HELPER_LOG_LEVEL`: Niveau de journalisation (debug, info, warning, error)

### Fichier de configuration

Créez un fichier `config.inc.php` dans le dossier du plugin pour surcharger les paramètres par défaut :

```php
<?php
return [
    'max_requests_per_minute' => 150,
    'enable_rate_limiting' => true,
    'debug_mode' => false,
    'log_level' => 'info'
];
```

### Personnalisation

#### Styles

Vous pouvez personnaliser l'apparence en modifiant le fichier CSS :
`plugins/generic/premiumSubmissionHelper/css/premiumSubmissionHelper.css`

#### Traductions

Les fichiers de traduction se trouvent dans :
`plugins/generic/premiumSubmissionHelper/locale/{lang_ISO}/LC_MESSAGES/locale.po`

Pour ajouter une nouvelle langue :
1. Créez un nouveau dossier avec le code de langue (ex: `es_ES`)
2. Copiez la structure du dossier `en_US`
3. Traduisez les chaînes dans le fichier `locale.po`
4. Compilez le fichier .po en .mo avec `msgfmt`

## 🐛 Débogage

### Activer les logs

1. Modifiez le fichier `config.inc.php` :
   ```php
   return [
       'debug_mode' => true,
       'log_level' => 'debug'
   ];
   ```

2. Vérifiez les logs dans :
   - Logs PHP : `/var/log/apache2/error.log` ou équivalent
   - Logs du plugin : `data/logs/premiumSubmissionHelper.log`

### Erreurs courantes

#### Le bouton d'analyse n'apparaît pas
- Vérifiez que l'utilisateur a les droits premium
- Vérifiez la console JavaScript pour les erreurs
- Vérifiez que le fichier JavaScript est bien chargé

#### L'analyse échoue avec une erreur 403
- Vérifiez la validité du jeton CSRF
- Assurez-vous que l'utilisateur est toujours connecté
- Vérifiez les logs pour plus de détails

#### Problèmes de performance
- Augmentez la limite de requêtes/min si nécessaire
- Vérifiez que le cache est correctement configuré
- Surveillez l'utilisation mémoire du serveur

## Dépannage

### Le plugin n'apparaît pas dans la liste des plugins

- Vérifiez que le dossier est bien placé dans `plugins/generic/premiumHelper/`
- Vérifiez les permissions du dossier et des fichiers
- Videz le cache d'OJS

### L'analyse ne fonctionne pas

- Vérifiez les logs d'erreurs PHP et Apache/Nginx
- Assurez-vous que l'utilisateur est bien connecté et a les droits nécessaires
- Vérifiez la console JavaScript du navigateur pour d'éventuelles erreurs

## Sécurité

- Toutes les requêtes sont validées côté serveur
- Protection CSRF intégrée
- Vérification des permissions utilisateur
- Validation des données d'entrée

## Licence

Ce plugin est distribué sous la licence GNU GPL v3. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Crédits

Développé par Saliou Ngom ingénieur en informatique

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur notre [dépôt GitHub](https://github.com/salioungom/ojs/plugins/generic/premiumHelper) 