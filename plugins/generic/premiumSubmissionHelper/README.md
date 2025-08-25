# Aide à la soumission Premium pour OJS

> **Propriétaire du plugin** : Saliou Ngom  
> **Dépôt GitHub** : [github.com/Salioungom/ojs](https://github.com/Salioungom/ojs)

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
- PHP 8.2 ou supérieur
- Extensions PHP requises : json, mbstring, ctype

## Installation

1. Téléchargez la dernière version du plugin
2. Décompressez l'archive dans le répertoire `plugins/generic/` de votre installation OJS
3. Renommez le dossier en `premiumHelper`
4. Assurez-vous que les permissions sont correctement définies :
   ```bash
   chmod -R 755 plugins/generic/premiumHelper
   chown -R www-data:www-data plugins/generic/premiumHelper
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

## Personnalisation

### Styles

Vous pouvez personnaliser l'apparence en modifiant le fichier CSS :
`plugins/generic/premiumHelper/styles/premiumHelper.css`

### Traductions

Les fichiers de traduction se trouvent dans :
`plugins/generic/premiumHelper/locale/{lang_ISO}/locale.xml`

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