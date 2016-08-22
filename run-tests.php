<?php

use Nice\Benchmark\BenchmarkCollection;

error_reporting(E_ALL);
gc_disable();

require __DIR__ . '/vendor/autoload.php';

$numIterations = 1000;
$numRoutes = 1000;
$numArgs = 9;

require __DIR__ . '/worst-case-tests.php';
$worstCaseBenchmark = WorstCaseMatching\setupBenchmark($numIterations, $numRoutes, $numArgs);

require __DIR__ . '/first-route-tests.php';
$firstRouteBenchmark = FirstRouteMatching\setupBenchmark($numIterations, $numRoutes, $numArgs);

$collection = new BenchmarkCollection();
$collection->addBenchmark($worstCaseBenchmark);
$collection->addBenchmark($firstRouteBenchmark);
$collection->execute();
