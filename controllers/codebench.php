<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Codebench — Squeeze every millisecond out of those regexes!
 *
 * @author     Geert De Deckere <geert@idoe.be>
 * @license    BSD License
 */
class Codebench_Controller extends Template_Controller {

	public $template = 'codebench';

	public function __call($method, $arguments)
	{
		// Convert submitted class name to URI segment
		if (isset($_POST['class']))
			url::redirect('codebench/'.format::classname($_POST['class']));

		// Pull the name of the requested Codebench class from the URL and clean it
		$class = $this->template->class = format::classname($this->uri->segment(2, ''));

		// Try to load the class, then run it
		if ( ! empty($class) AND Kohana::auto_load($class))
		{
			$codebench = new $class;
			$this->template->codebench = $codebench->run();
		}
	}
}