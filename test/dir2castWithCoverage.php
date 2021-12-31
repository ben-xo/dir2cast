<?php declare(strict_types=1);

require_once('../vendor/autoload.php');

use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Text as TextReport;

$filter = new Filter;
$filter->includeDirectory('../../dir2cast.php');

$coverage = new CodeCoverage(
    (new Selector)->forLineCoverage($filter),
    $filter
);

$coverage->start('<name of test>');

define('NO_DISPATCHER', true);
require_once('../../dir2cast.php');
$return = main($argv);

$coverage->stop();

$suffix=0;
while(file_exists("/tmp/cov-$suffix")) {
    $suffix++;
}

file_put_contents("/tmp/cov-$suffix", serialize($coverage));

exit($return);
