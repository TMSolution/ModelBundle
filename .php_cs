 <?php

    $finder = Symfony\CS\Finder\DefaultFinder::create()
        ->exclude('docs')
        ->in(__DIR__);

    return Symfony\CS\Config\Config::create()
        ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
        ->finder($finder);
