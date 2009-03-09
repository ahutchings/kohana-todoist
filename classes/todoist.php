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

	protected function get_response($method, array $params = NULL)
	{
		// Add the token to the parameters
		$params['token'] = $this->api_key;

		// Make an API request
		$request = remote::get(Todoist::API_URL.$method.'?'.http_build_query($params, NULL, '&'));

		if ($request['status'] === FALSE)
		{
			throw new Kohana_Exception('Todoist API request for :method failed: :error',
				array(':method' => $method, ':error' => $request['response']));
		}

		// Decode the response
		return json_decode($request['response']);
	}

	public function get_labels()
	{
		return $this->get_response('getLabels');
	}

	public function get_projects()
	{
		return $this->get_response('getProjects');
	}

	public function get_project($project)
	{
		// Add the project ID to the parameters
		$params = array('project_id' => $project);

		return $this->get_response('getProject', $params);
	}

	public function get_items(array $ids)
	{
		// Add the IDs to the parameters
		$params['ids'] = '['.implode(', ', $ids).']';

		return $this->get_response('getItemsById', $params);
	}

	public function get_uncompleted_items($project)
	{
		// Add the project ID to the parameters
		$params = array('project_id' => $project);

		return $this->get_response('getCompletedItems', $params);
	}

	public function get_completed_items($project, $offset = NULL)
	{
		// Add the project ID to the parameters
		$params = array('project_id' => $project);

		if (is_integer($offset) OR ctype_digit($offset))
		{
			// Add the offset
			$params['offset'] = (int) $offset;
		}

		return $this->get_response('getCompletedItems', $params);
	}

	public function complete_items(array $ids)
	{
		// Add the IDs to the parameters
		$params['ids'] = '['.implode(', ', $ids).']';

		return $this->get_response('completeItems', $params);
	}

	public function delete_items($project, array $ids)
	{
		// Add the project ID to the parameters
		$params = array('project_id' => $project);

		// Add the IDs to the parameters
		$params['ids'] = '['.implode(', ', $ids).']';

		return $this->get_response('deleteItems', $params);
	}

} // End Todoist
