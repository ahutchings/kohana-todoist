<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Todoist extends Controller {

	public function __construct(Request $request)
	{
		parent::__construct($request);

		// Load the token
		$token = Kohana::config('todoist', FALSE)->token;

		// Create a new instance of Todoist
		$this->todoist = new Todoist($token);
	}

	public function action_index()
	{
		$projects = $this->todoist->get_projects();

		foreach ($projects as $project)
		{
			echo Kohana::debug($this->todoist->get_items($project['project_id']));
		}
	}

} // End Todoist
