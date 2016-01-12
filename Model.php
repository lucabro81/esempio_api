<?php namespace api\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Parent model inherited by others models. Contains common object's instantiations and property init
 *
 * @author Luca Brognara
 * @date December 2015
 */

abstract class Model extends EloquentModel {

	protected $constants;
	private $_database;

	public function __construct() {
		parent::__construct();

		$this->constants = require __DIR__ .'/../../config/constants.php';
		$this->_database = require __DIR__ .'/../../config/database.php';
	}

	/**
	 * Filter for the join tables in read().
	 * TODO: manage the field datetime
	 *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param Illuminate\Database\Eloquent\Builder $query
 	 * @param array 								$filters
 	 * @param array 								$columns
 	 *
	 * @return array
	 */
	protected function filter($query, $filters, $columns) {

		foreach ($filters as $key => $filter) {

			$fields_exploded = explode('.', $filter['field']);

			if (count($fields_exploded) > 1) {
				$field_name = $fields_exploded[1]; // explode() necessary, because value in the form "table.field"
			}
			else {
				$field_name = $filter['field'];
			}

			$field_name = explode('.', $filter['field'])[1]; // explode() necessary, because value in the form "table.field"

			// if the field exist in the table
			if (in_array($field_name, $columns)) {
				switch ((isset($filter['like'])) ? $filter['like'] : 0) {
					case 0:
					default: // field == value
						$query->where($field_name, $filter['value']);
						break;
					case 1:  // field == value%
						$query->where($field_name, 'LIKE', $filter['value'].'%');
						break;
					case 2:  // field == %value
						$query->where($field_name, 'LIKE', '%'.$filter['value']);
						break;
					case 3:  // field == %value%
						$query->where($field_name, 'LIKE', '%'.$filter['value'].'%');
						break;
				}
			}
			else {
				throw new \Exception('invalidFilters', 400);
			}

		}

		return true;
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
	 * Filter the main table of the query by the value passed in $filters
	 *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param Illuminate\Database\Query\Builder $query
 	 * @param array 							 $filters
 	 * @param string 							 $table
 	 *
	 * @return array
	 */
	public function scopeFilter($query, $filters, $table) {

		if (count($filters) == 0) {
			return $query;
		}

		return $query->where(function($query) use ($filters, $table) {

			try {
				$this->filter($query, $filters, $this->get_table_columns($table));
			}
			catch(\Exception $e) {
				throw new \Exception($e->getMessage(), $e->getCode());
			}

		});

	}

	/**
	 * Filter the joined table of the query by the value passed in $filters
	 *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
 	 * @param Illuminate\Database\Query\Builder $query
 	 * @param string 							 $model
 	 * @param array 							 $filters
 	 * @param string 							 $filters
 	 *
	 * @return array
	 */
	public function scopeFilterBy($query, $model, $filters, $table) {

		if (count($filters) == 0) {
			return $query;
		}

		return $query->whereHas($model, function($query) use($filters, $table) {

			try {
				$this->filter($query, $filters, $this->get_table_columns($table));
			}
			catch(\Exception $e) {
				throw new \Exception($e->getMessage(), $e->getCode());
			}

		});

	}

}