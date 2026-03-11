<?php
require_once __DIR__ . '/config/database.php';

$queries = [
    "ALTER TABLE login_fingerprints ADD COLUMN is_anomaly TINYINT(1) DEFAULT 0",
    "ALTER TABLE login_fingerprints ADD COLUMN anomaly_score FLOAT DEFAULT 0"
];

foreach ($queries as $q) {
    if ($db->query($q) === TRUE) {
        echo "Query successful: $q\n";
    } else {
        echo "Error or already exists: " . $db->error . "\n";
    }
}
