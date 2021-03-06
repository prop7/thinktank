<?php
class ThinkTankTestDatabaseHelper {
    function create($db) {
        global $THINKTANK_CFG;

        error_reporting(22527); //Don't show E_DEPRECATED PHP messages, split() is deprecated

        //Create all the tables based on the build script
        $create_db_script = file_get_contents($THINKTANK_CFG['source_root_path']."sql/build-db_mysql.sql");
        $create_statements = split(";", $create_db_script);
        foreach ($create_statements as $q) {
            if (trim($q) != '') {
                $db->exec($q.";");
            }
        }
    }

    function drop($db) {
        global $TEST_DATABASE;

        //Delete test data by dropping all existing tables
        $q = "SHOW TABLES FROM ".$TEST_DATABASE;
        $result = $db->exec($q);
        while ($row = mysql_fetch_assoc($result)) {
            $q = "DROP TABLE ".$row['Tables_in_'.$TEST_DATABASE];
            $db->exec($q);
        }
    }
}?>
