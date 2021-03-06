<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/20 0020
 * Time: 23:57
 */

require __DIR__ . '/s-autoload.php';

// var_dump(fnmatch('.*', ".gitkeep"));die;
// var_dump(glob(__DIR__ . '/{t,T}ests', GLOB_BRACE | GLOB_ONLYDIR));

$finder = \Inhere\Library\Files\SimpleFinder::create()
    ->files()
    ->name('*.php')
    // ->ignoreVCS(false)
    // ->ignoreDotFiles(false)
    // ->exclude('tmp')
    ->notPath('tmp')
    // ->inDir(__DIR__ . '/tmp')
    ->inDir(__DIR__)
;

foreach ($finder as $file) {
    // var_dump($file);die;
    echo "+ {$file->getPathname()}\n";
}

// print_r($finder);
// var_dump($finder->count());
