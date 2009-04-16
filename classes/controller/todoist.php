<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Todoist extends Controller {

	public function __construct(Request $request)
	{
		parent::__construct($request);

		// Create a new Todoist instance
		$this->todoist = Todoist::instance();
	}

	public function action_index()
	{
		// Get a list of all projects
		$projects = $this->todoist->get_projects();

		foreach ($projects as $project)
		{
			// Display the project name
			echo '<h1>', $project['name'], '</h1>';

			// Get all completed items
			$completed = $this->todoist->get_uncompleted_items($project['id']);

			echo '<ul>';
			foreach ($completed as $item)
			{
				// Display the item content
				echo '<li>', $item['content'], '</li>';
			}
			echo '</ul>';
		}
	}

} // End Todoist
