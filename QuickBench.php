<?php

namespace philer\tools;

/**
 * Easy benchmarking and comparing of PHP functions (callbacks)
 * 
 * @author Philipp Miller
 */
class QuickBench {
    
    /**
	 * Array of Candidates = function containers
	 *
     * @var array<philer\tools\Candidate>
     */
    protected $candidates;
    
    /**
	 * Default number of runs per $this->run() call
	 *
     * @var int
     */
    protected $runs;
    
    /**
     * Default precision for outputs.
	 * Does not influence internal data.
	 *
     * @see round() and sprintf()
     * @var int
     */
    public $precision;
    
    /**
	 * Returns a new instance of QuickBench
	 *
     * @param  integer $runs
     * @param  integer $precision
     * @return philer\tools\QuickBench
     */
    public static function make($runs = 1, $precision = 10)
    {
        return new self($runs, $precision);
    }
    
    public function __construct($runs = 1, $precision = 10)
    {
        $this->runs = $runs;
        $this->precision = $precision;
    }
    
    /**
	 * Generic getter and setter methods with property names
	 * Setters are chainable.
	 *
     * @param  string $method
     * @return mixed          getter value or $this
     */
    public function __call($method, $args)
    {
        if (property_exists($this, $method)) {
            if ($args) $this->$method = $args[0];
            else  return $this->$method;
        } else throw new \Exception("Method $method doesn't exist!");
        return $this;
    }

	/**
	 * Add a new Callable
	 * Chainable
	 *
	 * @param  string   $name     idientification string
	 * @param  Callable $callback function to be executed
	 * @return philer\tools\QuickBench
	 */
    public function candidate($name, Callable $callback, $arguments = [])
    {
        $this->candidates[$name] = new Candidate($callback, $arguments);
        return $this;
    }

	/**
	 * Remove one or more candidates by name.
	 * takes any number and combination of
	 * strings and arrays containing strings as arguments.
	 *
	 * @param string|array
	 */
    public function removeCandidate()
    {
        foreach (self::flatten(func_get_args()) as $rmKey)
            unset($this->candidates[$rmKey]);
        return $this;
    }

	/**
	 * Runs all registered candidates $runs times
	 * and measures how long it takes.
	 * Chainable
	 * @see microtime(true)
	 * 
	 * @param integer $runs optional
	 * @return philer\tools\QuickBench
	 */
    public function run($runs = 0, $samples = null)
    {
        if (0 >= $runs)
            if (0 >= $runs = $this->runs) return '0 runs';
        
        if (func_num_args() > 1) {
            $args = func_get_args();
            unshift($args);
            $candidates = array_intersect_key($this->candidates, self::flatten($args));
        } else {
            $candidates = $this->candidates;
        }
        
        $ping = microtime(true);
        foreach ($candidates as $name => $candidate)
            $candidate->run($runs);
        $pong = microtime(true);
        
        printf(
            "Finished %d runs with %d candidate(s) in %.{$this->precision}f seconds\n",
            $runs,
            count($candidates),
            (double) $pong - (double) $ping
            );
        return $this;
    }

	/**
	 * Runs all registered candidates with an increasing number
	 * of runs per round, until $timelimit is reached.
	 * Uses a linear time estimation (5% tolerance)
	 * to determine whether the next round
	 * is going to surpass the timelimit.
	 * Chainable
	 * @see philer\tools\QuickBench::run()
	 * 
	 * @param integer|double $timelimit  max seconds to run
	 * @param integer        $init       number of runs in the first round
	 * @param integer        $multiplier increase factor of runs per round
	 * @return philer\tools\QuickBench
	 */
    public function runIterative($timelimit = 1, $init = 1, $multiplier = 10)
    {
        $time = 0;
        $timeEstimate = 0;
        for ($runs = $init ; $timeEstimate < $timelimit ; $runs *= $multiplier) {
            $ping = microtime(true);
            $this->run($runs);
            $pong = microtime(true);
            $time += (double) $pong - (double) $ping;
            $timeEstimate = $multiplier * 0.95 * $time;
        }
        return $this;
    }

	/**
	 * Discard all data from previous runs for given candidate names.
	 * If no candidate names are provided, discard all previous data.
	 * Chainable.
	 * 
	 * @param string|array optional
	 * @return philer\tools\QuickBench
	 */
    public function discardSamples()
    {
        array_map(function($candidate) {
                $candidate->discardSamples();
            },
            func_num_args() ? self::flatten(func_get_args()) : $this->candidates
        );
        return $this;
    }

	/**
	 * Output results. Output is formatted to be used in <pre> tags.
	 * Chainable
	 * 
	 * @return philer\tools\QuickBench
	 */
    public function results($precision = null)
    {
        if (!$precision) $precision = $this->precision;
        foreach ($this->candidates as $name => $candidate) {
            echo "\nResults for '$name':\taverage " . round($candidate->per(10000), $precision) . " per 10000 runs\n"
               . $candidate->results($precision);
        }
        echo "\n";
        return $this;
    }

	/**
	 * Helper function for functional programming:
	 * Flattens array to one dimension.
	 *
	 * @param array $array
	 * @return array
	 */
    public static function flatten($array)
    {
        $return = array();
        array_walk_recursive($array, function($x) use (&$return) { $return[] = $x; });
        return $return;
    }
}

/**
 * Helper class
 * Represents one QuickBench benchmark candidate / function / Callable
 */
class Candidate {

	/**
	 * Function to be tested
	 * 
	 * @var Callable
	 */
    protected $callback;

	/**
	 * Function parameters for $callback
	 * 
	 * @var array
	 */
    protected $arguments;

	/**
	 * Data collected from previous rounds.
	 * Each dataset is an array with two entries:
	 * [$time, $runs]
	 * 
	 * @var array
	 */
    protected $samples = [];

	/**
	 * Constructor
	 * Expects one Callable and it's arguments as an array (if any)
	 * @see call_user_function_array()
	 *
	 * @return philer\tools\Candidate
	 */
    public function __construct(Callable $callback, $arguments = [])
    {
        $this->callback = $callback;
        if ($arguments) $this->arguments = $arguments;
    }

	/**
	 * Execute this Candidates callback $runs times
	 * and measure the time.
	 * Chainable
	 *
	 * @param integer $runs
	 * @return philer\tools\Candidates
	 */
    public function run($runs)
    {
        $callback = $this->callback;
        $args     = $this->arguments;
        $ping = microtime(true);
        for ($i = 0 ; $i < $runs ; ++$i) {
            call_user_func_array($callback, $args);
        }
        $pong = microtime(true);
        $this->samples[] = [(double) $pong - (double) $ping, $runs];
        return $this;
    }

	/**
	 * Calculate average duration for $runs runs
	 * from collected data.
	 * Does not execute any runs by itself.
	 * 
	 * @param integer $runs
	 * @return double
	 */
    public function per($runs)
    {
        $totalTime = $totalRuns = 0;
        foreach ($this->samples as $sample) {
            $totalTime += $sample[0];
            $totalRuns += $sample[1];
        }
        return $totalTime * $runs / $totalRuns;
    }

	/**
	 * Discards all collected data from this Candidate.
	 * Chainable
	 *
	 * @return philer\tools\Candidate
	 */
    public function discardSamples()
    {
        $this->samples = [];
        return $this;
    }

	/**
	 * Returns formatted string with collected data.
	 * @see philer\tools\QuickBench::results()
	 *
	 * @param integer $precision output precision
	 * @return string
	 */
    public function results($precision)
    {
        return array_reduce($this->samples, function($return, $sample) use ($precision) {
                return $return . sprintf( "\t%10d runs in\t%2.{$precision}f seconds\n", $sample[1], $sample[0]);
            }, '');
    }
}
