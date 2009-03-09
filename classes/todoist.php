<?php defined('SYSPATH') or die('No direct script access.');

class Todoist_Core {

	const API_URL = 'http://todoist.com/API/';

	// Todoist API token
	protected $api_key = '';

	public function __construct($api_key)
	{
		if (empty($api_key))
		{
			throw new Kohana_Exception('Invalid API token');
		}

		// Set the API key
		$this->api_key = $api_key;
	}

	public function get_projects()
	{
		// Build the query string
		$query = http_build_query(array('token' => $this->api_key));

		$request = remote::get(Todoist::API_URL.'getProjects?'.$query);

		if ( ! $request['status'])
		{
			throw new Kohana_Exception('Unable to retrieve project list: :error',
				array(':error' => $request['response']));
		}

		return json_decode($request['response']);
	}

	public function get_items($project, $uncomplete = TRUE)
	{
		// Build the query string
		$query = http_build_query(array('token' => $this->api_key, 'project_id' => $project));

		$request = remote::get(Todoist::API_URL.'getUncompletedItems?'.$query);

		if ( ! $request['status'])
		{
			throw new Kohana_Exception('Unable to retrieve items for :project: :error',
				array('project' => $project, ':error' => $request['response']));
		}

		return json_decode($request['response']);
	}

} // End Todoist
