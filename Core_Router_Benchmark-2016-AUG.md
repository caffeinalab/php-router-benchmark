# Core Router Benchmark

Using : https://git.caffeina.co/internal/php-router-benchmark

## PHP Version

```
$ php -v
PHP 7.0.6 (cli) (built: Apr 28 2016 20:23:54) ( NTS )
Copyright (c) 1997-2016 The PHP Group
Zend Engine v3.0.0, Copyright (c) 1998-2016 Zend Technologies
```


## Worst-case matching
This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. 1,000 routes each with 9 arguments.

This benchmark consists of 12 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Caffeina Core - unknown route (1000 routes) | 999 | 0.0000043047 | +0.0000000000 | baseline
Caffeina Core - last route (1000 routes) | 999 | 0.0000077974 | +0.0000034927 | 81% slower
FastRoute - unknown route (1000 routes) | 992 | 0.0000389805 | +0.0000346758 | 806% slower
Symfony2 Dumped - unknown route (1000 routes) | 994 | 0.0000650598 | +0.0000607551 | 1411% slower
FastRoute - last route (1000 routes) | 999 | 0.0000698254 | +0.0000655207 | 1522% slower
Symfony2 Dumped - last route (1000 routes) | 982 | 0.0001364053 | +0.0001321006 | 3069% slower
Symfony2 - unknown route (1000 routes) | 985 | 0.0003468482 | +0.0003425435 | 7958% slower
Symfony2 - last route (1000 routes) | 999 | 0.0004290578 | +0.0004247531 | 9867% slower
Pux PHP - unknown route (1000 routes) | 979 | 0.0004412371 | +0.0004369324 | 10150% slower
Pux PHP - last route (1000 routes) | 999 | 0.0005776331 | +0.0005733284 | 13319% slower
Aura v2 - unknown route (1000 routes) | 965 | 0.0205734416 | +0.0205691369 | 477835% slower
Aura v2 - last route (1000 routes) | 992 | 0.0209373041 | +0.0209329994 | 486287% slower


## First route matching
This benchmark tests how quickly each router can match the first route. 1,000 routes each with 9 arguments.

This benchmark consists of 6 tests. Each test is executed 1,000 times, the results pruned, and then averaged. Values that fall outside of 3 standard deviations of the mean are discarded.


Test Name | Results | Time | + Interval | Change
--------- | ------- | ---- | ---------- | ------
Pux PHP - first route | 999 | 0.0000033443 | +0.0000000000 | baseline
FastRoute - first route | 999 | 0.0000033968 | +0.0000000525 | 2% slower
Symfony2 Dumped - first route | 998 | 0.0000052029 | +0.0000018587 | 56% slower
Caffeina Core - first route | 992 | 0.0000084975 | +0.0000051532 | 154% slower
Symfony2 - first route | 996 | 0.0000291328 | +0.0000257885 | 771% slower
Aura v2 - first route | 980 | 0.0000525278 | +0.0000491835 | 1471% slower
