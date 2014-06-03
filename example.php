<html><head><meta encoding="UTF-8"><title>QuicBench test</title></head><body><pre><?php

// full error reporting
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


$needle = 'Starr';
$haystack = ['McCartney', 'Lennon', 'Harrison', 'Starr'];

require_once('QuickBench.php');
use philer\tools\QuickBench;
QuickBench::make(1000, 5)
    
    ->candidate('in_array', function($needle, $haystack) {
            in_array($needle, $haystack);
        },
        [$needle, $haystack])
    
    ->candidate('strpos with implode', function($needle, $haystack) {
            strpos(implode($haystack), $needle);
        },
        [$needle, $haystack])
    
    ->candidate('strpos', function($needle, $haystack) {
            strpos($haystack, $needle);
        },
        [$needle, implode('|', $haystack)])
    
    ->candidate('stripos', function($needle, $haystack) {
            stripos($haystack, $needle);
        },
        [$needle, implode('|', $haystack)])
    
    ->candidate('preg_match', function($needle, $haystack) {
            preg_match($haystack, $needle);
        },
        [$needle, '/' . implode('|', $haystack) . '/'])
    
    ->candidate('preg_match i', function($needle, $haystack) {
            preg_match($haystack, $needle);
        },
        [$needle, '/' . implode('|', $haystack) . '/i'])
    
    ->run()->run(1000)->run(10000)
    ->results()
    
    ->discardSamples()
    ->removeCandidate('strpos with implode', 'stripos', 'preg_match', 'preg_match i')
    ->runIterative(4, 1e4, 100)
    ->results();


?></pre></body></html>
