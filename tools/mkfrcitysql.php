<?php
declare(strict_types=1);

/**
 * Turn French city JSON data obtained from https://github.com/etalab/decoupage-administratif
 * into an SQL file for quick and efficient import.
 */

$inputPath = $argv[1];
$outputPath = $argv[2];

$data = file_get_contents($inputPath);
$rows = json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR);

$valuesList = [];

foreach($rows as $row) {
    if ($row['type'] === 'commune-deleguee' || $row['type'] === 'commune-associee') {
        continue;
    }

    $inseeCode = $row['code'];
    $name = str_replace("'", "\\'", $row['nom']);
    $departement = $row['departement'];

    $valuesList[] = sprintf("('%s', E'%s', '%s')", $inseeCode, $name, $departement);
}

$sqlTemplate = "DELETE FROM fr_city;

INSERT INTO fr_city (insee_code, name, departement)
VALUES
%s;
";

$sql = sprintf($sqlTemplate, implode(sprintf(',%s', PHP_EOL), $valuesList));

file_put_contents($outputPath, $sql);
