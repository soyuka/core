<?php
/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

require '../vendor/autoload.php';

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;


$config = (require('config.php'))();
$root = Path::makeAbsolute($config['reference']['src'], getcwd());
$patterns = $config['reference']['patterns'];
$referencePath = $config['sidebar']['directories']['Reference'][0];

$files = [];
foreach ($patterns['names'] as $pattern) {
    foreach ((new Finder)->files()->in($root)->name($pattern) as $file) {
        $files[] = $file;
    }
}

foreach ($patterns['directories'] as $pattern) {
    foreach ((new Finder)->files()->in($root.'/'.$pattern)->name("*.php") as $file) {
        $files[] = $file;
    }
}

foreach ($files as $file) {
    $relativeToSrc = Path::makeRelative($file->getPath(), $root);
    $relativeToDocs = Path::makeRelative($file->getRealPath(), getcwd());
    exec('mkdir -p '.$referencePath.'/'.$relativeToSrc);
    exec('php src/generate-reference.php '.$relativeToDocs.' > '.$referencePath.'/'.$relativeToSrc.'/'.str_replace('.php','.mdx',$file->getBasename()));
}
