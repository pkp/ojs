# Plan d'implémentation du plugin \"Aide à la soumission Premium\"

## 1. Détails du plugin
- **Nom du plugin**: premiumHelper
- **Répertoire du plugin**: `/plugins/generic/premiumHelper/`
- **Description**: Un plugin OJS qui ajoute une fonctionnalité d'analyse IA du résumé pour les utilisateurs premium.

## 2. Hooks OJS utilisés
- **Hook principal**: `Templates::Submission::SubmissionMetadataForm::AdditionalMetadata` pour injecter le bouton d'analyse dans le formulaire de soumission.
- **Gestionnaire**: `LoadHandler` pour gérer les appels API personnalisés.

## 3. Point de terminaison d'API
- **URL**: `/premium-helper/analyze-abstract`
- **Méthode**: POST
- **Données attendues**:
  ```json
  {
    "abstract": "Texte du résumé à analyser"
  }
  ```
- **Réponse**:
  ```json
  {
    "keywords": ["mot-clé1", "mot-clé2"],
    "readabilityScore": 85,
    "suggestions": ["Suggestion 1"]
  }
  ```

## 4. Logique JavaScript frontend
1. Vérification du statut premium de l'utilisateur
2. Ajout d'un bouton "Analyser le résumé" sous le champ de résumé
3. Gestion du clic :
   - Récupération du texte
   - Envoi de la requête à l'API
   - Affichage des résultats

## 5. Sécurité
- Vérification des droits d'accès premium avant de traiter la requête
- Validation et assainissement de l'entrée utilisateur
- Limitation du taux de requêtes pour éviter les abus

## 6. Tests prévus
- Test de l'interface utilisateur
- Test des permissions
- Test des performances avec de longs résumés
- Test de l'expérience utilisateur sur mobile
