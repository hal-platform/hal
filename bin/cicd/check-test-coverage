#!/usr/bin/env php
<?php

if (!isset($argv[1])) {
    echo 'Please provide an integer value for the threshold of unit test coverage percentage.';
    echo PHP_EOL;
    exit(1);
}

$coverageThreshold = (int) $argv[1];

if ($coverageThreshold < 0 || $coverageThreshold > 100) {
    echo 'Invalid coverage threshold specified. Please provide a number from 0-100.';
    echo PHP_EOL;
    exit(1);
}

$cloverFile = __DIR__ . '/../../.phpunit/clover.xml';

if (!file_exists($cloverFile)) {
    echo 'Missing coverage file in clover format. Expected: ' . $cloverFile;
    echo PHP_EOL;
    exit(1);
}

$xml = new SimpleXMLElement(file_get_contents($cloverFile));
$metrics = $xml->xpath('//metrics');

$elements = 0;
$coveredElements = 0;

$statements = 0;
$coveredStatements = 0;

$methods = 0;
$coveredMethods = 0;

foreach ($metrics as $metric) {
    $elements += (int) $metric['elements'];
    $coveredElements += (int) $metric['coveredelements'];

    $statements += (int) $metric['statements'];
    $coveredStatements += (int) $metric['coveredstatements'];

    $methods += (int) $metric['methods'];
    $coveredMethods += (int) $metric['coveredmethods'];
}

// See calculation: https://confluence.atlassian.com/pages/viewpage.action?pageId=79986990

$t = $statements + $methods + $elements;
$tCovered = $coveredStatements + $coveredMethods + $coveredElements;

$actualCoverage = ($tCovered / $t) * 100;

$formatted = sprintf('%0.2f', $actualCoverage);

if ($actualCoverage < $coverageThreshold) {
    echo "Total code coverage is ${formatted}%, which is below the accepted ${coverageThreshold}%";
    echo PHP_EOL;
    exit(1);
}

echo "Total code coverage is ${formatted}%, which is above the accepted ${coverageThreshold}% ";
echo PHP_EOL;
