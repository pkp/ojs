<?php

/**
 * @file upgrade/PremiumSubmissionHelperUpgrade.inc.php
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PremiumSubmissionHelperUpgrade
 * @ingroup plugins_generic_premiumSubmissionHelper
 *
 * @brief Gère les mises à jour du schéma de la base de données
 */

class PremiumSubmissionHelperUpgrade
{
    /**
     * Exécute les mises à jour nécessaires
     *
     * @param string $context Le contexte de la mise à jour
     * @param string $plugin Le plugin concerné
     * @param string $fromVersion La version actuelle
     * @param string $toVersion La version cible
     * @return bool True si la mise à jour a réussi
     */
    public static function upgrade($context, $plugin, $fromVersion, $toVersion)
    {
        $migration = new SchemaMigration($plugin, $fromVersion, $toVersion);

        // Ajouter les étapes de migration
        $migration->addMigration('1.0.0', function () use ($context, $plugin) {
            return self::installSchema($context, $plugin);
        });

        // Exécuter les migrations
        return $migration->migrate();
    }

    /**
     * Installe le schéma de la base de données
     *
     * @param string $context Le contexte de l'installation
     * @param string $plugin Le plugin concerné
     * @return bool True si l'installation a réussi
     */
    public static function installSchema($context, $plugin)
    {
        $schemaMgr = new SchemaMigration($plugin);
        $installer = new Install($plugin);

        // Exécuter les fichiers SQL d'installation
        $schemaMgr->addSQL(
            $installer->getInstallSchema(),
            $installer->getInstallData()
        );

        // Créer les tables nécessaires
        $tables = self::getSchemaTables();
        foreach ($tables as $tableName => $tableSchema) {
            $schemaMgr->createTable($tableName, $tableSchema);
        }

        return $schemaMgr->execute();
    }

    /**
     * Désinstalle le schéma de la base de données
     *
     * @param string $context Le contexte de la désinstallation
     * @param string $plugin Le plugin concerné
     * @return bool True si la désinstallation a réussi
     */
    public static function uninstallSchema($context, $plugin)
    {
        $schemaMgr = new SchemaMigration($plugin);

        // Supprimer les tables
        $tables = array_keys(self::getSchemaTables());
        foreach ($tables as $tableName) {
            $schemaMgr->dropTable($tableName);
        }

        return $schemaMgr->execute();
    }

    /**
     * Retourne la définition des tables du schéma
     *
     * @return array Définition des tables
     */
    private static function getSchemaTables()
    {
        return [
            'premiumhelper_analyses' => [
                'columns' => [
                    'analysis_id' => 'bigint NOT NULL AUTO_INCREMENT',
                    'submission_id' => 'bigint NOT NULL',
                    'user_id' => 'bigint NOT NULL',
                    'context_id' => 'bigint NOT NULL',
                    'date_analyzed' => 'datetime NOT NULL',
                    'word_count' => 'int DEFAULT 0',
                    'sentence_count' => 'int DEFAULT 0',
                    'readability_score' => 'float DEFAULT 0',
                    'keywords' => 'text',
                    'metadata' => 'text',
                    'PRIMARY KEY (analysis_id)'
                ],
                'indexes' => [
                    'premiumhelper_analyses_submission' => ['submission_id'],
                    'premiumhelper_analyses_user' => ['user_id'],
                    'premiumhelper_analyses_context' => ['context_id']
                ],
                'foreignKeys' => [
                    [
                        'table' => 'submissions',
                        'column' => 'submission_id',
                        'referenceColumn' => 'submission_id',
                        'onDelete' => 'CASCADE'
                    ],
                    [
                        'table' => 'users',
                        'column' => 'user_id',
                        'referenceColumn' => 'user_id',
                        'onDelete' => 'CASCADE'
                    ],
                    [
                        'table' => 'journals',
                        'column' => 'context_id',
                        'referenceColumn' => 'journal_id',
                        'onDelete' => 'CASCADE'
                    ]
                ]
            ],
            'premiumhelper_settings' => [
                'columns' => [
                    'setting_id' => 'bigint NOT NULL AUTO_INCREMENT',
                    'context_id' => 'bigint NOT NULL',
                    'setting_name' => 'varchar(255) NOT NULL',
                    'setting_value' => 'text',
                    'setting_type' => 'varchar(6) NOT NULL',
                    'PRIMARY KEY (setting_id)'
                ],
                'indexes' => [
                    'premiumhelper_settings_context' => ['context_id'],
                    'premiumhelper_settings_unique' => [
                        'context_id',
                        'setting_name',
                        'UNIQUE'
                    ]
                ],
                'foreignKeys' => [
                    [
                        'table' => 'journals',
                        'column' => 'context_id',
                        'referenceColumn' => 'journal_id',
                        'onDelete' => 'CASCADE'
                    ]
                ]
            ]
        ];
    }
}
