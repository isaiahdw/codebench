<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Codebench — Squeeze every millisecond out of those regexes!
 *
 * @author     Geert De Deckere <geert@idoe.be>
 * @license    BSD License
 */
abstract class Codebench_Core {

	/**
	 * Some optional explanatory comments about the benchmark file.
	 * HTML allowed. URLs will be converted to links automatically.
	 */
	public $description = '';

	/**
	 * How many times to execute each method per subject.
	 */
	public $loops = 1000;

	/**
	 * The subjects to supply iteratively to your benchmark methods.
	 */
	public $subjects = array();

	/**
	 * Grade letters with their maximum scores. Used to color the graphs.
	 */
	public $grades = array
	(
		125 => 'A',
		150 => 'B',
		200 => 'C',
		300 => 'D',
		500 => 'E',
		'default' => 'F',
	);

	/**
	 * Constructor.
	 *
	 * @return  void
	 */
	public function __construct()
	{
		// Set the maximum execution time
		set_time_limit(Kohana::config('codebench.max_execution_time'));
	}

	/**
	 * Runs Codebench on the extending class.
	 *
	 * @return  array  benchmark output
	 */
	public function run()
	{
		// Array of all methods to loop over
		$methods = array_filter(get_class_methods($this), array($this, 'method_filter'));

		// Make sure the benchmark runs at least once,
		// also if no subject data has been provided.
		if (empty($this->subjects))
		{
			$this->subjects = array('NULL' => NULL);
		}

		// Initialize benchmark output
		$codebench = array
		(
			'class'       => get_class($this),
			'description' => $this->description,
			'loops'       => array
			(
				'base'    => (int) $this->loops,
				'total'   => (int) $this->loops * count($this->subjects) * count($methods),
			),
			'subjects'    => $this->subjects,
			'benchmarks'  => array(),
		);

		// Benchmark each method
		foreach ($methods as $method)
		{
			// Initialize benchmark output for this method
			$codebench['benchmarks'][$method] = array('time' => 0, 'memory' => 0);

			// Benchmark each subject on each method
			foreach ($this->subjects as $subject_key => $subject)
			{
				// Start the timer for one subject
				Benchmark::start($method.$subject_key);

				// The heavy work
				for ($i = 0; $i < $this->loops; $i++)
				{
					$return = $this->$method($subject);
				}

				// Stop and read the timer
				$benchmark = Benchmark::get($method.$subject_key, 20);

				// Benchmark output specific to the current method and subject
				$codebench['benchmarks'][$method]['subjects'][$subject_key] = array
				(
					'return' => $return,
					'time'   => $benchmark['time'],
					'memory' => $benchmark['memory'],
				);

				// Update method totals
				$codebench['benchmarks'][$method]['time']   += $benchmark['time'];
				$codebench['benchmarks'][$method]['memory'] += $benchmark['memory'];
			}
		}

		// Initialize the fastest and slowest benchmarks for both methods and subjects, time and memory,
		// these values will be overwritten using min() and max() later on.
		// The 999999999 values look like a hack, I know, but they work,
		// unless your method runs for more than 31 years or consumes over 1GB of memory.
		$fastest_method = $fastest_subject = array('time' => 999999999, 'memory' => 999999999); 
		$slowest_method = $slowest_subject = array('time' => 0, 'memory' => 0);

		// Find the fastest and slowest benchmarks, needed for the percentage calculations
		foreach ($methods as $method)
		{
			// Update the fastest and slowest method benchmarks
			$fastest_method['time']   = min($fastest_method['time'],   $codebench['benchmarks'][$method]['time']);
			$fastest_method['memory'] = min($fastest_method['memory'], $codebench['benchmarks'][$method]['memory']);
			$slowest_method['time']   = max($slowest_method['time'],   $codebench['benchmarks'][$method]['time']);
			$slowest_method['memory'] = max($slowest_method['memory'], $codebench['benchmarks'][$method]['memory']);

			foreach ($this->subjects as $subject_key => $subject)
			{
				// Update the fastest and slowest subject benchmarks
				$fastest_subject['time']   = min($fastest_subject['time'],   $codebench['benchmarks'][$method]['subjects'][$subject_key]['time']);
				$fastest_subject['memory'] = min($fastest_subject['memory'], $codebench['benchmarks'][$method]['subjects'][$subject_key]['memory']);
				$slowest_subject['time']   = max($slowest_subject['time'],   $codebench['benchmarks'][$method]['subjects'][$subject_key]['time']);
				$slowest_subject['memory'] = max($slowest_subject['memory'], $codebench['benchmarks'][$method]['subjects'][$subject_key]['memory']);
			}
		}

		// Percentage calculations for methods
		foreach ($codebench['benchmarks'] as & $method)
		{
			// Calculate percentage difference relative to fastest and slowest methods
			$method['percent']['fastest']['time']   = $method['time']   / $fastest_method['time']   * 100;
			$method['percent']['fastest']['memory'] = $method['memory'] / $fastest_method['memory'] * 100;
			$method['percent']['slowest']['time']   = $method['time']   / $slowest_method['time']   * 100;
			$method['percent']['slowest']['memory'] = $method['memory'] / $slowest_method['memory'] * 100;

			// Assign a grade for time and memory to each method
			$method['grade']['time']   = $this->grade($method['percent']['fastest']['time']);
			$method['grade']['memory'] = $this->grade($method['percent']['fastest']['memory']);

			// Percentage calculations for subjects
			foreach ($method['subjects'] as & $subject)
			{
				// Calculate percentage difference relative to fastest and slowest subjects for this method
				$subject['percent']['fastest']['time']   = $subject['time']   / $fastest_subject['time']   * 100;
				$subject['percent']['fastest']['memory'] = $subject['memory'] / $fastest_subject['memory'] * 100;
				$subject['percent']['slowest']['time']   = $subject['time']   / $slowest_subject['time']   * 100;
				$subject['percent']['slowest']['memory'] = $subject['memory'] / $slowest_subject['memory'] * 100;

				// Assign a grade letter for time and memory to each subject
				$subject['grade']['time']   = $this->grade($subject['percent']['fastest']['time']);
				$subject['grade']['memory'] = $this->grade($subject['percent']['fastest']['memory']);
			}
		}

		return $codebench;
	}

	/**
	 * Callback for array_filter().
	 * Filters out all methods not to benchmark.
	 *
	 * @param   string   method name
	 * @return  boolean
	 */
	protected function method_filter($method)
	{
		return (substr($method, 0, 5) === 'bench');
	}

	/**
	 * Returns the applicable grade letter for a score.
	 *
	 * @param   integer|double  score
	 * @return  string  grade letter
	 */
	protected function grade($score)
	{
		foreach ($this->grades as $max => $grade)
		{
			if ($max === 'default')
				continue;

			if ($score <= $max)
				return $grade;
		}

		return $this->grades['default'];
	}
}