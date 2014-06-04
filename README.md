QuickBench
==========
Quick and easy timing and comparison of PHP functions.

Ever wanted to do a quick test to see which version of a simple
function implementation is the quickest?
No need to fire up a huge testing framework.

### Requires

- PHP 5.4+

### Usage

Create an instance of QuickBench,
register function candidates,
run them any number of times,
get outputs.

Wrap output in a `pre` tag for readability or call your script via command line.

#### Basic example

Which is the fastest way to find out if a given string
exists in a collection of strings?
Ideas: `in_array`, `strpos`
```PHP
$needle = 'Starr';
$haystack = ['McCartney', 'Lennon', 'Harrison', 'Starr'];

require_once('QuickBench.php');
philer\tools\QuickBench::make()
    
	->candidate('in_array', function($needle, $haystack) {
            in_array($needle, $haystack);
        },
        [$needle, $haystack])
    
    ->candidate('strpos', function($needle, $haystack) {
            strpos($haystack, $needle);
        },
        [$needle, implode('|', $haystack)])
	
    ->run(1000)->run(10000)
    ->results();
```
Sample output on my machine:
```
Finished 1000 runs with 2 candidate(s) in 0.0032808781 seconds
Finished 10000 runs with 2 candidate(s) in 0.0318610668 seconds

Results for 'in_array':	average 0.0165037675 per 10000 runs
	      1000 runs in	0.0016610622 seconds
	     10000 runs in	0.0164930820 seconds

Results for 'strpos':	average 0.0154098597 per 10000 runs
	      1000 runs in	0.0015909672 seconds
	     10000 runs in	0.0153598785 seconds

```
See example.php for a more elaborate example

### How it works
Look up `microtime(true)` and `call_user_func_array()`.

