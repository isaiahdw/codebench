<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Codebench — Squeeze every millisecond out of those regexes!
 *
 * @author     Geert De Deckere <geert@idoe.be>
 * @license    BSD License
 */
class format extends format_Core {

	/**
	 * Attempts to format a string into a valid class name.
	 *
	 * @param   string   class name
	 * @param   boolean  helper class or not?
	 * @return  string
	 */
	public static function classname($name, $helper = FALSE)
	{
		// Remove all invalid characters
		$name = preg_replace('~[^a-z0-9_]+~i', '', (string) $name);

		// Remove leading digits
		$name = ltrim($name, '0..9');

		// Capitalize if needed
		return ($helper) ? strtolower($name) : ucfirst($name);
	}
}