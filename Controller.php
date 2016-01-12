<?php namespace api\Controllers;

use \Slim\Container as SlimContainer;

use Illuminate\Database\Capsule\Manager as Capsule; 
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Respect\Validation\Validator as Validator;

use api\Models\Api as Api;

/**
 * Parent controller inherited by others controllers. Contains common object's instantiations and property init
 *
 * @author Luca Brognara
 * @date December 2015
 */

// SEARCH
// public function __construct(SlimContainer $cont)
// protected function get_table_columns($table)
// protected function sendError($error, $requestType, $requestContent)
// protected function sendSuccess($success, $requestType, $requestContent, $results)
// protected function callAPI($method, $url, $data = false)
// protected function generateRandomPassword($length = 10)
// protected function generateToken()
// protected function generateRandomString($length = 10)
// protected function checkToken($token)
// protected function isAdmin($token)
// protected function isReadOnly($token)
// protected function isUser($token, $id)
// protected function userFromToken($token)
// protected function validator($input, $required, $table)
// protected function filter($query, $filters, $columns)

abstract class Controller {

	//protected $exit = false;

	protected $request;		///< Contains the Psr\\Http\\Message\\ServerRequestInterface object
	protected $response;	///< Contains the Psr\\Http\\Message\\ServerResponseInterface object
	protected $params;		///< Params passend in the request
	protected $session;		///< Session data of the logged user

	protected $id;			///< Id passed in the get request to get single record of a certain table
	protected $limit = 50;	
	protected $page = 1;
	protected $total = 0;
	protected $ordBy;
	protected $sortBy;
	protected $filters;		///< contains the filter passed in the request to get a certain collection of data, is in the form: \n
							///< filters[0][field]=ability&filters[0][value]=SU&filters[1][field]=city.name&filters[1][value]=Rov&filters[1][like]=1
	protected $token;
	protected $accepted;
	protected $email;

	protected $error = NULL;

	protected $db;
	protected $constants;

	private $_database;

	/**
	 * Constructor
	 */
	public function __construct(SlimContainer $cont) {

		// CONSTANTS

			$this->constants = require __DIR__ .'/../../config/constants.php';

		// SLIM D.I.

			$this->request = $cont->request;
			$this->response = $cont->response;
			$this->params = $this->request->getParams();

		// INIT PARAMS

			$this->id 		= (isset($this->params['id']) && ($this->params['id']!==NULL)) 				? $this->params['id'] : NULL;
			$this->limit 	= (isset($this->params['limit']) && ($this->params['limit']!==NULL)) 		? intval($this->params['limit']) : 50;
			$this->page 	= (isset($this->params['page']) && ($this->params['page']!==NULL)) 			? intval($this->params['page']) : 1;
			$this->ordBy 	= (isset($this->params['ordBy']) && ($this->params['ordBy']!==NULL)) 		? $this->params['ordBy'] : NULL;
			$this->sortBy 	= (isset($this->params['sortBy']) && ($this->params['sortBy']!==NULL)) 		? $this->params['sortBy'] : 'DESC';
			$this->filters 	= (isset($this->params['filters']) && ($this->params['filters']!==NULL)) 	? $this->params['filters'] : [];
			$this->token 	= (isset($this->params['token']) && ($this->params['token']!==NULL)) 		? $this->params['token'] : NULL;
			$this->accepted = (isset($this->params['accepted']) && ($this->params['accepted']!==NULL)) 	? $this->params['accepted'] : FALSE;
			$this->email 	= (isset($this->params['email']) && ($this->params['email']!==NULL)) 		? $this->params['email'] : NULL;

		// Check if numeric params are numbers

			if ((($this->limit!==NULL) && (!is_numeric($this->limit))) || 
				(($this->page!==NULL) && (!is_numeric($this->page)))) {
				$this->error = $this->constants['typeMismatch'];
			}

			if (!in_array($this->sortBy, array('ASC', 'DESC'))) {
				$this->error = $this->constants['invalidParameter'];
			}

		// Token cheking

			// Check if valid token
			/*if (($this->token != NULL)&&(!$this->checkToken())) {
				$this->error = $this->constants['invalidToken'];
			}*/

		// ELOQUENT

			$this->db = new Capsule;
			$this->_database = require __DIR__ .'/../../config/database.php';
			$this->db->addConnection($this->_database);

			// Set the event dispatcher used by Eloquent models... (optional)
			$this->db->setEventDispatcher(new Dispatcher(new Container));

			// Make this Capsule instance available globally via static methods... (optional)
			$this->db->setAsGlobal();

			// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
			$this->db->bootEloquent();

			/* PER DEBUG */
			/*$this->db->getEventDispatcher()->listen('illuminate.query', function($query, $params, $time, $conn) { 
				try{
					echo "<pre>";
				    print_r(array($query, $params, $time, $conn));
				    echo "</pre>";
				}
				catch(\Exception $e) {
					echo $e->file.' '.$e->line.' '.$e->message;
				}
			});*/

	}	

	/**
     * Get the columns of a db's table
     * 
	 * @author Luca Brognara
 	 * @date December 2015
	 *
 	 * @param string 	$table 
 	 * @param string 	$requestType 	
 	 * @param string 	$requestContent
 	 *
	 * @return array
     */
	protected function get_table_columns($table) {

		$conn_str = 'pgsql:host='.$this->_database['host'];
		$conn_str.= ';dbname='.$this->_database['database'];
		$conn = new \PDO($conn_str, $this->_database['username'], $this->_database['password']);
		$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$sql = 	"SELECT column_name 
    				FROM information_schema.columns 
					WHERE table_name = :table;";

        $stmt = $conn->prepare($sql);
        $stmt->execute(array('table' => $table));

        $struttura = array();

        while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
	        $struttura[] = $row['column_name'];
	    }

	    return $struttura;
	}

	/**
     * Send error response
     * 
	 * @author Luca Brognara
 	 * @date December 2015
	 *
 	 * @param array 	$error 
 	 * @param string 	$requestType 	
 	 * @param string 	$requestContent
 	 *
	 * @return Psr\\Http\\Message\\ResponseInterface
     */
	protected function sendError($error, $requestType, $requestContent) {

		$this->response = $this->response->withStatus($error['code']);

		$response = 	[
							"response" 	=> 	"KO",

							"info" 		=>	[
												"requestType" => $requestType,
												"requestContent" => $requestContent
											],

							"error"	=> 	[
											"code" => $error['code'],
											"errorType" => $error['errorType'],
											"errorDetail" => $error['errorDetail'],
											"description" => $error['description']
										]
						];

		$body = $this->response->getBody();
		$body->write(json_encode($response));

		return $this->response;
	}

	/**
     * Send success response
     * 
	 * @author Luca Brognara
 	 * @date December 2015
	 *
 	 * @param array 	$success 
 	 * @param string 	$requestType 	
 	 * @param string 	$requestContent
 	 * @param array 	$results
 	 *
	 * @return Psr\\Http\\Message\\ResponseInterface
     */
	protected function sendSuccess($success, $requestType, $requestContent, $results) {

		$this->response = $this->response->withStatus($success['code']);

		$response = 	[
						"response" 	=> 	"OK",

						"info" 		=>	[
											"requestType" => $requestType,
											"requestContent" => $requestContent,
											"totalResults" => $this->total,
											"resultsPerPage" => $this->limit,
											"currentPage" => $this->page
										],

						"results" 	=> 	[
											"items" => $results
										],

						"success"	=> 	[
											"code" => $success['code'],
											"type" => $success['type'],
											"detail" => $success['detail'],
											"description" => $success['description']
										]
					];

		$body = $this->response->getBody();
		$body->write(json_encode($response));

		return $this->response;
	}

	/**
     * Call for external API
     *
     * @see Controller::generateRandomString()
     * 
	 * @author Alexandru Popa
 	 * @date 2015
	 *
 	 * @param string 	$method 
 	 * @param string 	$url 	
 	 * @param boolean 	$data 	(default = false)
 	 *
	 * @return JSON
     */
	protected function callAPI($method, $url, $data = false) {
	    $curl = curl_init();

	    switch ($method)
	    {
	        case "POST":
	            curl_setopt($curl, CURLOPT_POST, 1);

	            if ($data)
	                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	            break;
	        case "PUT":
	            curl_setopt($curl, CURLOPT_PUT, 1);
	            break;
	        default:
	            if ($data)
	                $url = sprintf("%s?%s", $url, http_build_query($data));
	    }

	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	    $result = curl_exec($curl);

	    curl_close($curl);

	    return $result;
	}

	/**
     * Generate a random password using generateRandomString()
     *
     * @see Controller::generateRandomString()
     * 
	 * @author Alexandru Popa
 	 * @date 2015
	 *
 	 * @param integer 	$length 	(default = 10)
 	 *
	 * @return string
     */
	protected function generateRandomPassword($length = 10) {
	    return generateRandomString($length);
	}

	/**
     * Generate a token
     *
	 * @author Alexandru Popa
 	 * @date 2015
 	 *
	 * @return string
     */
	protected function generateToken() {
	    return md5(generateRandomString());
	}

	/**
     * Generate random string
     *
	 * @author Alexandru Popa
 	 * @date 2015
 	 *
 	 * @param integer 	$length 	(default = 10)
 	 *
	 * @return string
     */
	protected function generateRandomString($length = 10) {
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

	/**
     * Check if the token is valid
     *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param string 	$token
 	 *
	 * @return boolean
     */
	protected function checkToken($token) {
        $n_api_record = Api::where('token', $token)->count();
        return ($n_api_record>0) ? true : false;
    }

    /**
     * Check if the token passed match with the admin's one
     *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param string 	$token
 	 *
	 * @return boolean
     */
    protected function isAdmin($token) {

    	$api_record = 	Api::where('token', $token)
    						->with('user')
    					->first();

        if($api_record->ability == "SU") {
            return true;
        }
        
        return false;
    }

    /**
     * Check if the token pased if of a user with a RO permission
     *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param string 	$token
 	 *
	 * @return boolean
     */
    protected function isReadOnly($token) {

    	$api_record = 	Api::where('token', $token)
    						->with('user')
    					->first();

        if($api_record->ability == "RO") {
            return true;
        }
        
        return false;
    }

    /**
     * check if the $token is matchable with the user $id passed
     *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param string 	$token
 	 * @param string 	$id
 	 *
	 * @return boolean
     */
    protected function isUser($token, $id) {

    	$api_record = 	Api::where('token', $token)
    						->with('user')
    					->first();

        if($api_record->user->id == $id) {
            return true;
        }
        
        return false;
    }

   /**
     * Look for the user by the $token passed
     *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param string 	$token
 	 *
	 * @return boolean|api\Model\User
     */
    protected function userFromToken($token) {
    	$api_record = Api::where('token', $token)->first();

    	if (count($api_record) > 0) {
    		$user = User::where('id', $api_record->users_id)->first();

    		if (count($user) > 0) {
    			return $user;
    		}

    		return false;
    	}

    	return false;
    }

    /**
     * Validation of the $required fields, checking first if they're present in the $table, than if they're not null
     *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param array 	$query
 	 * @param array 	$required
 	 * @param string 	$table
 	 *
	 * @return boolean|Psr\\Http\\Message\\ResponseInterface
     */
    protected function validator($input, $required, $table) {
    	
    	$columns = $this->get_table_columns($table);

		// Check if field is in user table
		foreach ($input as $key => $item) {
			if (!in_array($item['field'], $columns)) {
				return $this->sendError($this->constants['invalidParameter'], "POST", "RADIOS");
			}
		}

		// Check if params passed are in the required and are valorized
		$validation = true;
		foreach ($required as $key_field => $field) {
			foreach ($input as $key => $item) {
				if (($key_field == $item['field']) && ($item['value'] == NULL)) {
					$validation = false;
					break;
				}
			}
		}

		return $validation;
    }

    /**
	 * Return the elements that are in the bigger array and not in the other one.
	 *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param array $arrai1
 	 * @param array $arrai2
 	 *
	 * @return array
	 */
	protected function diff_2_array($array1, $array2) {
		
		$first = [];
		$second = [];

		if (count($array1) > count($array2)) {
			$first = $array1;
			$second = $array2;
		}
		else {
			$first = $array2;
			$second = $array1;
		}

		foreach ($second as $key => $item) {
			foreach ($first as $key => $inner_item) {
				if ($item['field'] == $inner_item['field']) {
					unset($first[$key]);
					break;
				}
			}
		}

		return $first;
	}

}