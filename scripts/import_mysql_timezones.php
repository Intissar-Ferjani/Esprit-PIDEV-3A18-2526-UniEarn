<?php

/**
 * Import MySQL timezone tables from PHP's built-in tzdata.
 *
 * PHP ships with a complete copy of the IANA timezone database
 * (updated via pecl/timezonedb or PHP updates), so we can use
 * DateTimeZone to generate proper transition data without downloading anything.
 *
 * Usage: php scripts/import_mysql_timezones.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = \Symfony\Component\Dotenv\Dotenv::class;

// Read DB credentials from .env.local then .env
$envFile    = __DIR__ . '/../.env';
$envLocal   = __DIR__ . '/../.env.local';

$env = parse_ini_file($envFile);
if (file_exists($envLocal)) {
    $env = array_merge($env, parse_ini_file($envLocal));
}

$dbUrl = $env['DATABASE_URL'] ?? '';
preg_match('#mysql://([^:]*):([^@]*)@([^:]+):(\d+)/(\w+)#', $dbUrl, $m);
[, $user, $pass, $host, $port] = $m;

echo "Connecting to MySQL at {$host}:{$port} as {$user}...\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname=mysql;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Clearing existing timezone tables...\n";
$pdo->exec("DELETE FROM mysql.time_zone_transition");
$pdo->exec("DELETE FROM mysql.time_zone_transition_type");
$pdo->exec("DELETE FROM mysql.time_zone_name");
$pdo->exec("DELETE FROM mysql.time_zone");

$timezones = DateTimeZone::listIdentifiers();
$total     = count($timezones);
echo "Importing {$total} timezone identifiers...\n";

$insertTz     = $pdo->prepare("INSERT INTO mysql.time_zone (Time_zone_id, Use_leap_seconds) VALUES (?, 'N')");
$insertName   = $pdo->prepare("INSERT INTO mysql.time_zone_name (Name, Time_zone_id) VALUES (?, ?)");
$insertTtype  = $pdo->prepare("INSERT INTO mysql.time_zone_transition_type (Time_zone_id, Transition_type_id, Offset, Is_DST, Abbreviation) VALUES (?, ?, ?, ?, ?)");
$insertTrans  = $pdo->prepare("INSERT INTO mysql.time_zone_transition (Time_zone_id, Transition_time, Transition_type_id) VALUES (?, ?, ?)");

$pdo->beginTransaction();

$id = 1;
foreach ($timezones as $name) {
    $tz          = new DateTimeZone($name);
    $transitions = $tz->getTransitions(mktime(0, 0, 0, 1, 1, 1970), mktime(0, 0, 0, 1, 1, 2038));

    // Insert timezone
    $insertTz->execute([$id]);
    $insertName->execute([$name, $id]);

    // Build unique transition types
    $typeMap = [];
    $typeId  = 0;

    foreach ($transitions as $t) {
        $key = $t['offset'] . '_' . $t['isdst'] . '_' . substr($t['abbr'], 0, 8);
        if (!isset($typeMap[$key])) {
            $abbr = substr($t['abbr'], 0, 8);
            $insertTtype->execute([$id, $typeId, $t['offset'], (int) $t['isdst'], $abbr]);
            $typeMap[$key] = $typeId;
            $typeId++;
        }
    }

    // Insert transitions (skip the first synthetic one at PHP_INT_MIN)
    foreach ($transitions as $i => $t) {
        if ($i === 0) continue; // synthetic boundary entry
        $key = $t['offset'] . '_' . $t['isdst'] . '_' . substr($t['abbr'], 0, 8);
        if (!isset($typeMap[$key])) continue;
        $insertTrans->execute([$id, $t['ts'], $typeMap[$key]]);
    }

    $id++;

    if ($id % 100 === 0) {
        echo "  {$id}/{$total} done...\n";
    }
}

$pdo->commit();

$count = (int) $pdo->query("SELECT COUNT(*) FROM mysql.time_zone_name")->fetchColumn();
echo "\n✅ Done! Imported {$count} timezone names into mysql.time_zone_name.\n";
echo "Run FLUSH TABLES; to activate:\n";
