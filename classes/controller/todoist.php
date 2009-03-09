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
		// Get a list of all projects
		$projects = $this->todoist->get_projects();

		foreach ($projects as $project)
		{
			// Display the project name
			echo Kohana::debug($project->name);

			// Get all completed items
			$completed = $this->todoist->get_completed_items($project->id);

			foreach ($completed as $item)
			{
				// Display the item content
				echo Kohana::debug($item->content);
			}
		}
	}

} // End Todoist
