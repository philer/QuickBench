<html><head><meta encoding="UTF-8"><title>QuicBench test</title></head><body><pre><?php

// full error reporting
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('QuickBench.php');
use philer\tools\QuickBench;
QuickBench::make(1000, 5)
    
    ->candidate('uniqid', 'uniqid')
    
    ->candidate('rand', 'rand', [1,1000])
    
    ->runIterative(10, 1e4, 10)
    ->results();


?></pre></body></html>
