#!/usr/bin/php
<?php

if (PHP_SAPI !== 'cli') 
{
  echo "error: this script may only be run from CLI";
  return 1;
}

if(!chdir('redaxo')) 
{
  echo "error: start this script from a redaxo projects' root folder";
  return 2;
}

// ---- bootstrap REX

$REX = array();
$REX['REDAXO'] = true;
$REX['HTDOCS_PATH'] = '../';
$REX['BACKEND_FOLDER'] = 'redaxo';

// bootstrap core
include 'src/core/master.inc.php';
// bootstrap addons
include_once rex_path::core('packages.inc.php');

// run the tests
$tests = rex_dir::recursiveIterator(dirname(__FILE__).'/../lib/tests', rex_dir_recursive_iterator::LEAVES_ONLY)->ignoreSystemStuff();

$runner = new rex_test_runner();
$runner->setUp();
$result = $runner->run($tests);

echo $result;

exit(strpos($result, 'FAILURES!') === false ? 0 : 99);