<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @author Geert De Deckere <geert@idoe.be>
 */
class Valid_Color extends Codebench {

	public $description = "Optimization for <code>valid::color()</code>\nSee: http://forum.kohanaphp.com/comments.php?DiscussionID=2192.";

	public $loops = 10000;

	public $subjects = array
	(
		// Valid colors
		'aaA',
		'123',
		'000000',
		'#123456',
		'#abcdef',

		// Invalid colors
		'ggg',
		'1234',
		'#1234567',
		"#000\n",
		'}§è!çà%$z',
	);

	// Note that I added the D modifier to corey's regexes. We need to match exactly
	// the same if we want the benchmarks to be of any value.
	public function bench_corey_regex_1($subject)
	{
		return (bool) preg_match('/^#?([0-9a-f]{1,2}){3}$/iD', $subject);
	}

	public function bench_corey_regex_2($subject)
	{
		return (bool) preg_match('/^#?([0-9a-f]){3}(([0-9a-f]){3})?$/iD', $subject);
	}

	// Optimized corey_regex_1
	// Using non-capturing parentheses and a possessive interval
	public function bench_geert_regex_1a($subject)
	{
		return (bool) preg_match('/^#?(?:[0-9a-f]{1,2}+){3}$/iD', $subject);
	}

	// Optimized corey_regex_2
	// Removed useless parentheses, made the remaining ones non-capturing
	public function bench_geert_regex_2a($subject)
	{
		return (bool) preg_match('/^#?[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $subject);
	}

	// Optimized geert_regex_1a
	// Possessive "#"
	public function bench_geert_regex_1b($subject)
	{
		return (bool) preg_match('/^#?+(?:[0-9a-f]{1,2}+){3}$/iD', $subject);
	}

	// Optimized geert_regex_2a
	// Possessive "#"
	public function bench_geert_regex_2b($subject)
	{
		return (bool) preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $subject);
	}

	// A solution without regex
	public function bench_geert_native_str($subject)
	{
		if ($subject[0] === '#')
		{
			$subject = substr($subject, 1);
		}

		$strlen = strlen($subject);
		return (($strlen !== 3 AND $strlen !== 6) OR ! ctype_xdigit($subject));
	}
}