#!/usr/bin/php
<?php

# establish database connection
require './ojs_db_connect.php';

# Get journal IDs
$sql = <<<SQL
    SELECT *
    FROM journals
SQL;

if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $sql . '] [' . $db->error . ']');
}

$journals = array();
while($row = $result->fetch_assoc()){
    $journals[] = $row['journal_id'];
}

$result->free();

$journals[] = 0; // add journal 0 (special sidewide placeholder)

foreach($journals as $journal_id) {
    echo "\n### Journal ID: $journal_id###\n\n";

    # blocks we are using
    $enabled = array(
        0 => 'userblockplugin',
        1 => 'informationblockplugin',
        2 => 'helpblockplugin',
        3 => 'fontsizeblockplugin'
    );

    # blocks we aren't using
    $disabled = array(
        'authorbiosblockplugin',
        'developedbyblockplugin',
        'donationblockplugin',
        'keywordcloudblockplugin',
        'languagetoggleblockplugin',
        'navigationblockplugin',
        'notificationblockplugin',
        'readingtoolsblockplugin',
        'relateditemsblockplugin',
        'roleblockplugin',
        'subscriptionblockplugin',
        'keywordcloudblockplugin'
    );

    # journal 0 is slightly different
    if($journal_id == 0) { 
        $enabled = array(
            0 => 'userblockplugin',
            1 => 'fontsizeblockplugin'
        );

        $disabled[] = 'informationblockplugin'; 
        $disabled[] = 'helpblockplugin';
    }

    # disable blocks we aren't using
    foreach($disabled as $d) {
        $disable_sql = <<<SQL
            UPDATE plugin_settings
            SET setting_value = 0
            WHERE plugin_name = '$d'
            AND setting_name = 'enabled'
            AND journal_id = $journal_id
SQL;
        echo "$disable_sql\n\n";
        if(!$disable_result = $db->query($disable_sql)){
            die('There was an error running the query [' . $disable_sql . '] [' . $db->error . ']');
        }
        echo 'Total rows updated: ' . $db->affected_rows . "\n";
        //$disable_result->free();

        $clean_disabled_sql = <<<SQL
            DELETE FROM plugin_settings
            WHERE plugin_name = '$d'
            AND setting_name != 'enabled'
            AND journal_id = $journal_id
SQL;
        echo "$clean_disabled_sql\n\n";
        if(!$clean_disabled_result = $db->query($clean_disabled_sql)){
            die('There was an error running the query [' . $clean_disabled_sql . '] [' . $db->error . ']');
        }
        echo 'Total rows updated: ' . $db->affected_rows . "\n";
    }


    # enable blocks we are using
    foreach($enabled as $seq => $e) {

        # delete existing settings
        $delete_sql = <<<SQL
            DELETE FROM plugin_settings
            WHERE plugin_name = '$e'
            AND journal_id = $journal_id
SQL;
        echo "$delete_sql\n\n";
        if(!$delete_result = $db->query($delete_sql)){
            die('There was an error running the query [' . $delete_sql . '] [' . $db->error . ']');
        }
        echo 'Total rows updated: ' . $db->affected_rows . "\n";

        // enabled = 1, context = 2
        $enable_sql = <<<SQL
            INSERT INTO plugin_settings
            (plugin_name, journal_id, setting_name, setting_value, setting_type)
            VALUES ('$e', $journal_id, 'enabled', 1, 'bool')
SQL;
        echo "$enable_sql\n\n";
        if(!$enable_result = $db->query($enable_sql)){
            die('There was an error running the query [' . $enable_sql . '] [' . $db->error . ']');
        }
        echo 'Total rows updated: ' . $db->affected_rows . "\n";

        // context = 2
        $context_sql = <<<SQL
            INSERT INTO plugin_settings
            (plugin_name, journal_id, setting_name, setting_value, setting_type)
            VALUES ('$e', $journal_id, 'context', 2, 'int')
SQL;
        echo "$context_sql\n\n";
        if(!$context_result = $db->query($context_sql)){
            die('There was an error running the query [' . $context_sql . '] [' . $db->error . ']');
        }
        echo 'Total rows updated: ' . $db->affected_rows . "\n";

        // sequence
        $seq_sql = <<<SQL
            INSERT INTO plugin_settings
            (plugin_name, journal_id, setting_name, setting_value, setting_type)
            VALUES ('$e', $journal_id, 'seq', $seq, 'int')
SQL;
        echo "$seq_sql\n\n";
        if(!$seq_result = $db->query($seq_sql)){
            die('There was an error running the query [' . $seq_sql . '] [' . $db->error . ']');
        }
        echo 'Total rows updated: ' . $db->affected_rows . "\n";

    }
}

$db->close();

?>
