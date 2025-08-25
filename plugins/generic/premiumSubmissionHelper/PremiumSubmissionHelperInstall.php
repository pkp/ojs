<?php

declare(strict_types=1);

namespace APP\plugins\generic\premiumSubmissionHelper;

// PKP classes
use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;
use PKP\db\SchemaDAO;
use PKP\db\XMLDAO;
use PKP\plugins\Install;

/**
 * @file PremiumSubmissionHelperInstall.inc.php
 *
 * @class PremiumSubmissionHelperInstall
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Gère l'installation et la désinstallation du plugin
 */
class PremiumSubmissionHelperInstall extends Install
{
    /**
     * @copydoc Install::install()
     */
    public function install($contextId = null)
    {
        $success = parent::install($contextId);

        if ($success) {
            // Installer le schéma de la base de données
            $success = self::installSchema($contextId);

            // Initialiser les paramètres par défaut
            if ($success) {
                $plugin = PluginRegistry::getPlugin('generic', 'premiumsubmissionhelperplugin');
                $settingsForm = new SettingsForm($plugin, $contextId);
                $settingsForm->initData();
                $settingsForm->execute();
            }
        }

        return $success;
    }

    /**
     * @copydoc Install::uninstall()
     */
    public function uninstall($contextId = null)
    {
        // Désinstaller le schéma de la base de données
        self::uninstallSchema($contextId);

        // Appeler la méthode parente
        return parent::uninstall($contextId);
    }

    /**
     * Installe le schéma de la base de données
     *
     * @param int $contextId ID du contexte
     * @return bool True si l'installation a réussi
     */
    private static function installSchema($contextId)
    {
        // Utiliser la classe Upgrade pour installer le schéma
        require_once('upgrade/Upgrade.inc.php');
        return PremiumHelperUpgrade::installSchema($contextId, 'premiumhelperplugin');
    }

    /**
     * Désinstalle le schéma de la base de données
     *
     * @param int $contextId ID du contexte
     * @return bool True si la désinstallation a réussi
     */
    private static function uninstallSchema($contextId)
    {
        // Utiliser la classe Upgrade pour désinstaller le schéma
        require_once('upgrade/Upgrade.inc.php');
        return PremiumHelperUpgrade::uninstallSchema($contextId, 'premiumhelperplugin');
    }

    /**
     * Retourne le schéma SQL d'installation
     *
     * @return string Schéma SQL
     */
    public function getInstallSchema()
    {
        return []; // Le schéma est géré par la classe Upgrade
    }

    /**
     * Installe les modèles d'emails
     * @return bool
     */
    protected function installEmailTemplates()
    {
        return [];
    }

    /**
     * Installe les tables nécessaires
     * @return bool
     */
    protected function installSQL()
    {
        return [];
    }

    /**
     * Retourne les données d'installation
     *
     * @return array Données d'installation
     */
    public function getInstallData()
    {
        return []; // Aucune donnée d'installation par défaut
    }
}
