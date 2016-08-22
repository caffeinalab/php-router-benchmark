<?php

namespace WorstCaseMatching;

use Nice\Benchmark\Benchmark;
use Nice\Benchmark\ResultPrinter\MarkdownPrinter;

/**
 * Sets up the Worst-case matching benchmark.
 *
 * This benchmark generates a randomly prefixed and suffixed route, in an attempt to thwart
 * any optimization.
 *
 * @param $numIterations
 * @param $numRoutes
 * @param $numArgs
 *
 * @return Benchmark
 */
function setupBenchmark($numIterations, $numRoutes, $numArgs)
{
    $benchmark = new Benchmark($numIterations, 'Worst-case matching', new MarkdownPrinter());
    $benchmark->setDescription(sprintf(
            'This benchmark matches the last route and unknown route. It generates a randomly prefixed and suffixed route in an attempt to thwart any optimization. %s routes each with %s arguments.',
            number_format($numRoutes),
            $numArgs
        ));

    setupCore($benchmark, $numRoutes, $numArgs);

    setupAura2($benchmark, $numRoutes, $numArgs);
    setupFastRoute($benchmark, $numRoutes, $numArgs);
    if (extension_loaded('r3')) {
        setupR3($benchmark, $numRoutes, $numArgs);
    } else {
        echo "R3 extension is not loaded. Skipping initialization for \"Worst-case matching\" test using R3.\n";
    }

    setupSymfony2($benchmark, $numRoutes, $numArgs);
    setupSymfony2Optimized($benchmark, $numRoutes, $numArgs);
    setupPux($benchmark, $numRoutes, $numArgs);

    return $benchmark;
}

function getRandomParts()
{
    $rand = md5(uniqid(mt_rand(), true));

    return array(
        substr($rand, 0, 10),
        substr($rand, -10),
    );
}

function setupR3(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = r3_tree_create_persist("app", 10);
    if (!r3_tree_is_compiled($router)) {
        for ($i = 0; $i < $routes; $i++) {
            list ($pre, $post) = getRandomParts();
            $str = '/' . $pre . '/' . $argString . '/' . $post;
            if (0 === $i) {
                $firstStr = str_replace(array('{', '}'), '', $str);
            }
            $lastStr = str_replace(array('{', '}'), '', $str);
            r3_tree_insert($router, $str, "handler" . $i);
        }
        r3_tree_compile($router);
    }

    $benchmark->register(sprintf('r3 - last route (%s routes)', $routes), function () use ($router, $lastStr) {
            $data = r3_tree_match($router, $lastStr);
        });

    $benchmark->register(sprintf('r3 - unknown route (%s routes)', $routes), function () use ($router) {
            $data = r3_tree_match($router, "/not-even-real");
        });


}

/**
 * Sets up FastRoute tests
 */
function setupFastRoute(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = \FastRoute\simpleDispatcher(function ($router) use ($routes, $argString, &$lastStr) {
            for ($i = 0; $i < $routes; $i++) {
                list ($pre, $post) = getRandomParts();
                $str = '/' . $pre . '/' . $argString . '/' . $post;

                if (0 === $i) {
                    $firstStr = str_replace(array('{', '}'), '', $str);
                }
                $lastStr = str_replace(array('{', '}'), '', $str);

                $router->addRoute('GET', $str, 'handler' . $i);
            }
        });

    $benchmark->register(sprintf('FastRoute - last route (%s routes)', $routes), function () use ($router, $lastStr) {
            $route = $router->dispatch('GET', $lastStr);
        });

    $benchmark->register(sprintf('FastRoute - unknown route (%s routes)', $routes), function () use ($router) {
            $route = $router->dispatch('GET', '/not-even-real');
        });
}

/**
 * Sets up Pux tests
 */
function setupPux(Benchmark $benchmark, $routes, $args)
{
    $name = extension_loaded('pux') ? 'Pux ext' : 'Pux PHP';

    $argString = implode('/', array_map(function ($i) { return ':arg' . $i; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $router = new \Pux\Mux;
    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(':', '', $str);
        }
        $lastStr = str_replace(':', '', $str);

        $router->add($str, 'handler' . $i);
    }

    $benchmark->register(sprintf('%s - last route (%s routes)', $name, $routes), function () use ($router, $lastStr) {
            $route = $router->match($lastStr);
        });

    $benchmark->register(sprintf('%s - unknown route (%s routes)', $name, $routes), function () use ($router) {
            $route = $router->match('/not-even-real');
        });
}

/**
 * Sets up Symfony 2 tests
 */
function setupSymfony2(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $sfRoutes = new \Symfony\Component\Routing\RouteCollection();
    $router = new \Symfony\Component\Routing\Matcher\UrlMatcher($sfRoutes, new \Symfony\Component\Routing\RequestContext());
    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        $lastStr = str_replace(array('{', '}'), '', $str);

        $sfRoutes->add($str, new \Symfony\Component\Routing\Route($str, array('controller' => 'handler' . $i)));
    }

    $benchmark->register(sprintf('Symfony2 - last route (%s routes)', $routes), function () use ($router, $lastStr) {
            $route = $router->match($lastStr);
        });

    $benchmark->register(sprintf('Symfony2 - unknown route (%s routes)', $routes), function () use ($router) {
            try {
                $route = $router->match('/not-even-real');
            } catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $e) { }
        });
}

/**
 * Sets up Symfony2 optimized tests
 */
function setupSymfony2Optimized(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';
    $sfRoutes = new \Symfony\Component\Routing\RouteCollection();
    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        $lastStr = str_replace(array('{', '}'), '', $str);

        $sfRoutes->add($str, new \Symfony\Component\Routing\Route($str, array('controller' => 'handler' . $i)));
    }
    $dumper = new \Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper($sfRoutes);
    file_put_contents(__DIR__ . '/files/worst-case-sf2.php', $dumper->dump(array(
                'class' => 'WorstCaseSf2UrlMatcher'
            )));
    require_once __DIR__ . '/files/worst-case-sf2.php';

    $router = new \WorstCaseSf2UrlMatcher(new \Symfony\Component\Routing\RequestContext());

    $benchmark->register(sprintf('Symfony2 Dumped - last route (%s routes)', $routes), function () use ($router, $lastStr) {
            $route = $router->match($lastStr);
        });

    $benchmark->register(sprintf('Symfony2 Dumped - unknown route (%s routes)', $routes), function () use ($router) {
            try {
                $route = $router->match('/not-even-real');
            } catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException $e) { }
        });
}

/**
 * Sets up Aura v2 tests
 *
 * https://github.com/auraphp/Aura.Router
 */
function setupAura2(Benchmark $benchmark, $routes, $args)
{
    $argString = implode('/', array_map(function ($i) { return "{arg$i}"; }, range(1, $args)));
    $lastStr = '';
    $router = new \Aura\Router\Router(
        new \Aura\Router\RouteCollection(
            new \Aura\Router\RouteFactory()
        ),
        new \Aura\Router\Generator()
    );
    for ($i = 0, $str = 'a'; $i < $routes; $i++, $str++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(array('{', '}'), '', $str);
        }
        $lastStr = str_replace(array('{', '}'), '', $str);

        $router->add($str, $str)
            ->addValues(array(
                    'controller' => 'handler' . $i
                ));
    }

    $benchmark->register(sprintf('Aura v2 - last route (%s routes)', $routes), function () use ($router, $lastStr) {
            $route = $router->match($lastStr, $_SERVER);
        });

    $benchmark->register(sprintf('Aura v2 - unknown route (%s routes)', $routes), function () use ($router) {
            $route = $router->match('/not-even-real', $_SERVER);
        });
}

/**
 * Sets up Core tests
 */
function setupCore(Benchmark $benchmark, $routes, $args){

    $argString = implode('/', array_map(function ($i) { return ':arg' . $i; }, range(1, $args)));
    $str = $firstStr = $lastStr = '';

    for ($i = 0; $i < $routes; $i++) {
        list ($pre, $post) = getRandomParts();
        $str = '/' . $pre . '/' . $argString . '/' . $post;

        if (0 === $i) {
            $firstStr = str_replace(':', '', $str);
        }
        $lastStr = str_replace(':', '', $str);

        \Route::on($str, "handler$i");
    }

    $benchmark->register(sprintf('Caffeina Core - last route (%s routes)', $routes), function () use ($lastStr) {
      \Route::dispatch($lastStr,'get',true);
    });

    $benchmark->register(sprintf('Caffeina Core - unknown route (%s routes)', $routes), function () {
      \Route::dispatch('/not-even-real','get',true);
    });
}
