<?php declare(strict_types=1);

ini_set('memory_limit', (string)(1024 * 1024 * 1024) );

require_once('vendor/autoload.php');

use SebastianBergmann\CodeCoverage\Report\Text as PHP_CodeCoverage_Report_Text;

$codeCoverage = require_once('/tmp/cov-main');
foreach (glob('/tmp/cov-*') as $filename) {
  if($filename != '/tmp/cov-main') {
    $codeCoverage->merge(unserialize(file_get_contents($filename)));
  }
}

// Based on PHPUnit_TextUI_TestRunner::doRun
$writer = new PHP_CodeCoverage_Report_Text(50, 90, true, false);

echo $writer->process($codeCoverage);
