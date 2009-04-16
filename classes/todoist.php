<?php defined('SYSPATH') or die('No direct script access.');
/**
 * API for Todoist.com
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Todoist_Core {

	// Secure Todoist API base URL
	const API_URL = 'https://todoist.com/API/';

	// Unkown error
	const ERROR_UNKNOWN            = 0x4100;

	// Login error code
	const ERROR_TOKEN              = 0x4101;

	// Login error code
	const ERROR_LOGIN              = 0x4110;

	// Registration error codes
	const ERROR_ALREADY_REGISTERED = 0x4120;
	const ERROR_TOO_SHORT_PASSWORD = 0x4121;
	const ERROR_INVALID_EMAIL      = 0x4122;
	const ERROR_INVALID_TIMEZONE   = 0x4123;
	const ERROR_INVALID_FULL_NAME  = 0x4124;

	// Instances by token
	protected static $instances = array();

	/**
	 * Returns an instance for the given token. If not token is given,
	 * the token will be loaded from the Todoist configuration file.
	 *
	 * @param   string   API token
	 * @return  Todoist
	 */
	public static function instance($token = NULL)
	{
		if ($token === NULL)
		{
			// Get the default token
			$token = Kohana::config('todoist')->token;
		}

		if ( ! isset(Todoist::$instances[$token]))
		{
			// Create a new instance for this token
			Todoist::$instances[$token] = new Todoist($token);
		}

		return Todoist::$instances[$token];
	}

	/**
	 * Attempts to login and return the user profile data.
	 *
	 * @throws  Todoist_Exception
	 * @param   string   email address
	 * @param   string   password
	 * @return  array
	 */
	public static function login($email, $password)
	{
		$params = array(
			'email'    => $email,
			'password' => $password);

		try
		{
			// Make an API request
			$response = remote::get(Todoist::API_URL.'login?'.http_build_query($params, NULL, '&'));
		}
		catch (Kohana_Exception $e)
		{
			throw new Todoist_Exception('API :method request failed, API may be offline',
				array(':method' => 'login'),
				Todoist::ERROR_UNKNOWN);
		}

		// Decode the response
		$response = json_decode($response);

		if ($response === 'LOGIN_ERROR')
		{
			// Login failed for some reason
			throw new Todoist_Exception('Login failed, check your email and password',
				NULL,
				Todoist::ERROR_LOGIN);
		}

		return $response;
	}

	/**
	 * Registers a new user with the given information.
	 *
	 * @throws  Todoist_Exception
	 * @param   string   full name
	 * @param   string   email address
	 * @param   string   password, at least 5 characters
	 * @param   string   time zone
	 * @return  array
	 */
	public static function register($full_name, $email, $password, $timezone)
	{
		$params = array(
			'full_name' => $full_name,
			'email'     => $email,
			'password'  => $password,
			'timezone'  => $timezone);

		try
		{
			// Make an API request
			$response = remote::get(Todoist::API_URL.'register?'.http_build_query($params, NULL, '&'));
		}
		catch (Kohana_Exception $e)
		{
			throw new Todoist_Exception('API :method request failed, API may be offline',
				array(':method' => 'register'),
				Todoist::ERROR_UNKNOWN);
		}

		// Decode the response
		$response = json_decode($response);

		if ( ! is_string($response))
		{
			// Successful registration
			return $response;
		}

		switch ($response)
		{
			case 'ALREADY_REGISTERED':
				throw new Todoist_Exception('An user has already registered with :email',
					array(':email' => $user['email']),
					Todoist::ERROR_ALREADY_REGISTERED);
			break;
			case 'TOO_SHORT_PASSWORD':
				throw new Todoist_Exception('Password must be at least :length characters long',
					array(':length' => 5),
					Todoist::ERROR_TOO_SHORT_PASSWORD);
			break;
			case 'INVALID_EMAIL':
				throw new Todoist_Exception('Invalid email address', NULL, Todoist::ERROR_INVALID_EMAIL);
			break;
			case 'INVALID_TIMEZONE':
				throw new Todoist_Exception('Invalid time zone', NULL, Todoist::ERROR_INVALID_TIMEZONE);
			break;
			default:
				throw new Todoist_Exception('Unknown error :error',
					array(':error' => $response), Todoist::ERROR_UNKNOWN);
			break;
		}
	}

	/**
	 * Returns a list of the supported timezones.
	 *
	 * @return  array
	 */
	public static function timezones()
	{
		// Get the cached timezones
		$timezones = Kohana::cache('todoist_timezones');

		if ( ! is_array($timezones))
		{
			try
			{
				// Get the timezone list
				$timezones = json_decode(remote::get(Todoist::API_URL.'getTimezones'));
			}
			catch (Kohana_Exception $e)
			{
				throw new Todoist_Exception('API :method request failed, API may be offline',
					array(':method' => 'getTimezones'),
					Todoist::ERROR_UNKNOWN);
			}

			// Cache the timezone list
			Kohana::cache('todoist_timezones', $timezones);
		}

		return $timezones;
	}

	// Todoist API token
	protected $_token = '';

	/**
	 * Sets the API token.
	 *
	 * @param   string  API token
	 * @return  void
	 */
	public function __construct($token)
	{
		if (empty($token))
		{
			// A valid token must be supplied to all responses
			throw new Todoist_Exception('A valid token is required for all operations', NULL, Todoist::ERROR_TOKEN);
		}

		// Set the API token
		$this->_token = $token;
	}

	/**
	 * Get a list of all labels.
	 *
	 * @return  array
	 */
	public function get_labels()
	{
		return $this->_request('getLabels');
	}

	/**
	 * Get a list of all projects.
	 *
	 * @return   array
	 */
	public function get_projects()
	{
		return $this->_request('getProjects');
	}

	/**
	 * Get the details of a project by id.
	 *
	 * @param   integer   project id
	 * @return  array
	 */
	public function get_project($project)
	{
		// Add the project ID to the parameters
		$params = array('project_id' => $project);

		return $this->_request('getProject', $params);
	}

	/**
	 * Get a list of uncompleted items of a project.
	 *
	 * @param   integer   project id
	 * @return  array
	 */
	public function get_uncompleted_items($project)
	{
		// Add the project ID to the parameters
		$params = array('project_id' => $project);

		return $this->_request('getUncompletedItems', $params);
	}

	/**
	 * Get a list of completed items of a project.
	 *
	 * @param   integer   project id
	 * @return  array
	 */
	public function get_completed_items($project, $offset = NULL)
	{
		// Add the project ID to the parameters
		$params = array('project_id' => $project);

		if (is_integer($offset) OR ctype_digit($offset))
		{
			// Add the offset
			$params['offset'] = (int) $offset;
		}

		return $this->_request('getCompletedItems', $params);
	}

	/**
	 * Add a new item to a project and return the details.
	 *
	 * @param   integer  project id
	 * @param   array    item details: content, [date_string], [priority]
	 * @return  array
	 */
	public function add_item($project, array $item)
	{
		// Specify the project
		$params['project_id'] = $project;

		// Content is required
		$params['content'] = $item['content'];

		if (isset($item['date_string']))
		{
			// Date string is optional
			$params['date_string'] = $item['date_string'];
		}

		if (isset($item['priority']))
		{
			// Normalize the priority to be between 1 and 4
			$params['priority'] = max(min($item['priority'], 4), 1);
		}

		return $this->_request('addItem', $params);
	}

	/**
	 * Update an existing item by id and return the details.
	 *
	 * @param   integer  item id
	 * @param   array    item details: content, [date_string], [priority]
	 * @return  array
	 */
	public function update_item($id, $item)
	{
		// Specify the project
		$params['id'] = $id;

		if ( ! is_array($item))
		{
			// Must always be an array
			$item = array('content' => $item);
		}

		if (isset($item['content']))
		{
			// Content is optional for updates
			$params['content'] = $item['content'];
		}

		if (isset($item['date_string']))
		{
			// Date string is optional
			$params['date_string'] = $item['date_string'];
		}

		if (isset($item['priority']))
		{
			// Normalize the priority to be between 1 and 4
			$params['priority'] = max(min($item['priority'], 1), 4);
		}

		return $this->_request('updateItem', $params);
	}

	/**
	 * Get the details of a list of items.
	 *
	 * @param   array  item ids
	 * @return  array
	 */
	public function get_items(array $ids)
	{
		// Add the IDs to the parameters
		$params['ids'] = json_encode($ids);

		return $this->_request('getItemsById', $params);
	}

	/**
	 * Mark a list of items completed.
	 *
	 * @param   array  item ids
	 * @return  array
	 */
	public function complete_items(array $ids)
	{
		// Add the IDs to the parameters
		$params['ids'] = json_encode($ids);

		return $this->_request('completeItems', $params);
	}

	/**
	 * Delete a list of items in a project.
	 *
	 * @param   integer  project id
	 * @param   array    item ids
	 * @return  void
	 */
	public function delete_items($project, array $ids)
	{
		// Add the project ID to the parameters
		$params = array('project_id' => $project);

		// Add the IDs to the parameters
		$params['ids'] = json_encode($ids);

		return $this->_request('deleteItems', $params);
	}

	/**
	 * Returns search results.
	 *
	 * @param   array  query strings
	 * @return  array
	 */
	public function query(array $queries)
	{
		// Add the queries to the parameters
		$params['queries'] = json_encode($queries);

		return $this->_request('query', $params);
	}

	/**
	 * Makes an API request and returns the response.
	 *
	 * @param   string  method to call
	 * @param   array   query parameters
	 * @return  array
	 */
	protected function _request($method, array $params = NULL)
	{
		// Add the token to the parameters
		$params['token'] = $this->_token;

		// Make an API request
		$response = remote::get(Todoist::API_URL.$method.'?'.http_build_query($params, NULL, '&'));

		try
		{
		}
		catch (Kohana_Exception $e)
		{
			throw new Todoist_Exception('API :method request failed, API may be offline',
				array(':method' => $method));
		}

		// Return the decode response
		return json_decode($response, TRUE);
	}

} // End Todoist
