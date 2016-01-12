<?php namespace api\Controllers;

use api\Controllers\Controller as Controller;

use api\Models\Radio as Radio;

/**
 * Controller to manage radios's record manipulation. \n
 * Respond to every request with "/radios/*" uri.
 *
 * @author Luca Brognara
 * @date December 2015
 */

// SEARCH
// public function __construct($cont)
// public function post_create()
// public function get_read()
// public function put_update($req, $res, $args)
// public function delete($req, $res, $args)

class RadiosController extends Controller {

	/**
	 * Constructor
	 */
	public function __construct($cont) {

		/* PER DEBUG */
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		parent::__construct($cont);
	}

	//////////////////////////////////////////////////////
	//////////////////////// CRUD ////////////////////////
	//////////////////////////////////////////////////////

	/**
	 * Create new radio
	 *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
	 * @return Psr\\Http\\Message\\ResponseInterface
	 */
	public function post_create() {
		// Token required
		if ($this->token == NULL) {
			return $this->sendError($this->constants['tokenRequired'], "GET", "RADIOS");
		}

		// Check if admin
		if (!$this->isAdmin($this->token)) {
			return $this->sendError($this->constants['authorizationRequired'], "GET", "RADIOS");
		}

		$radio = NULL;

		try {

			// ##### Validation #####

				$input = $this->params['data'];

				$required = array('name' => NULL, 'contries_id' => NULL);

				if (!$this->validation($input, $required, 'radios')) {
					return $this->sendError($this->constants['invalidParameter'], "POST", "RADIOS");
				}

			// ##### Registration #####

				$radio = new Radio();
				foreach ($input as $key => $item) {
					$radio->{$item['field']} = $item['value'];
				}
				$radio->save();

				return $this->sendSuccess($this->constants['success'], 
										  "GET", 
										  "RADIOS",
										  $input);

		}
		catch(\Exception $e) {

			// Delete record just created

			if ($radio!==NULL) {
				Radio::where('id', $radio->id)->delete();
			}

			/* PER DEBUG */ print_r($e->getMessage());
			return $this->sendError($this->constants['generic'], "GET", "RADIOS");
		}
	}

	/**
	 * Return one radio if "id" passed, or a list of radios
	 * TODO: info only if token passed
	 *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
	 * @return Psr\\Http\\Message\\ResponseInterface
	 */
	public function get_read() {
		if ($this->error != NULL){
			return $this->sendError($this->error, "GET", "RADIOS");
		}

		// Token required
		/*if ($this->token == NULL) {
			return $this->sendError($this->constants['tokenRequired'], "GET", "RADIOS");
		}*/

		try {
			$results = [];

			// Only one radio
			if ($this->id !== NULL) {
				$results = 	Radio::where('id', $this->id)
								->with('created_by')
								->with('updated_by')
								->with('city.province.region')
								->with('country')
								->with('editorial_group')
							->first();

				$this->total = 1;
			}
			// Radios' list
			else {

				// query string like this filters[0][field]=ability&filters[0][value]=SU&filters[1][field]=city.name&filters[1][value]=Rovigo
				$filters = $this->filters;

				// ##### Filter for the main table #####
					$filters_main = [];
					foreach ($filters as $key => $filter) {
						if (count(explode('.', $filter['field']))==1) {
							$filters_main[] = $filter;
						}
					}

				// ##### Set filters for join table #####
					$filters = $this->diff_2_array($filters, $filters_main); // array_diff not applicable
					$filters_createdby = [];
					$filters_updatedby = [];
					$filters_city = [];
					$filters_province = [];
					$filters_region = [];
					$filters_country = [];
					$filters_editorialgroup = [];
					foreach ($filters as $key => $filter) {

						$field_exploded = explode('.', $filter['field']);

						switch ($field_exploded[0]) { // at index 0 there's the city field
							case "created_by":
								$filters_createdby[] = $filter;
								break;
							case "updated_by":
								$filters_updatedby[] = $filter;
								break;
							case "city":
								$filters_city[] = $filter;
								break;
							case "province":
								$filters_province[] = $filter;
								break;
							case "region":
								$filters_region[] = $filter;
								break;
							case "country":
								$filters_country[] = $filter;
								break;
							case "editorial_group":
								$filters_editorialgroup[] = $filter;
								break;
						}
					}

				// ##### Filters for other tables #####

					$results = 	Radio::with('created_by')
									->with('updated_by')
									->with('city.province.region')
									->with('country')
									->with('editorial_group');

					$results->filter($filters_main, 'radios');
					$results->filterBy('created_by', $filters_createdby, 'users');
					$results->filterBy('updated_by', $filters_updatedby, 'users');
					$results->filterBy('city.province.region', $filters_region, 'regions');
					$results->filterBy('country', $filters_country, 'countries');
					$results->filterBy('editorial_group', $filters_editorialgroup, 'editorial_groups');

					/*$users_columns = $this->get_table_columns('users');
					$radios_columns = $this->get_table_columns('radios');
					$cities_columns = $this->get_table_columns('cities');
					$provinces_columns = $this->get_table_columns('provinces');
					$region_columns = $this->get_table_columns('regions');
					$countries_columns = $this->get_table_columns('contries');
					$editorialgroups_columns = $this->get_table_columns('editorial_groups');

					$results = 	Radio::with('created_by')
									->with('updated_by')
									->with('city.province.region')
									->with('country')
									->with('editorial_group');

					// FILTER RADIOS TABLE
					if (count($filters_main)>0) {
						$results->where(function($query) use ($filters_main, $radios_columns) {

							$this->exit = false;
							foreach ($filters_main as $key => $filter) {

								// if the field exist in the table
								if (in_array($filter['field'], $radios_columns)) {
									switch ((isset($filter['like'])) ? $filter['like'] : 0) {
										case 0:
										default: // field == value
											$query->where($filter['field'], $filter['value']);
											break;
										case 1:  // field == value%
											$query->where($filter['field'], 'LIKE', $filter['value'].'%');
											break;
										case 2:  // field == %value
											$query->where($filter['field'], 'LIKE', '%'.$filter['value']);
											break;
										case 3:  // field == %value%
											$query->where($filter['field'], 'LIKE', '%'.$filter['value'].'%');
											break;
									}
								}
								else {
									$this->exit = true; // $ilter['field'] doesn't match with the $radios_columns so exit
									break;
								}

							}
						});
						
						if ($this->exit) {
							return $this->sendError($this->constants['invalidFilters'], "GET", "RADIOS");
						}
					}

					// FILTER CREATED_BY TABLE
					if (count($filters_createdby)>0) {
						$results->whereHas('created_by', function($query) use($filters_createdby, $users_columns) {
							$this->exit = $this->filter($query, $filters_createdby, $users_columns);
						});
						
						if ($this->exit) {
							return $this->sendError($this->constants['invalidFilters'], "GET", "RADIOS");
						}
					}

					// FILTER UPDATED_BY TABLE
					if (count($filters_updatedby)>0) {
						$results->whereHas('created_by', function($query) use($filters_updatedby, $users_columns) {
							$this->exit = $this->filter($query, $filters_updatedby, $users_columns);
						});
						
						if ($this->exit) {
							return $this->sendError($this->constants['invalidFilters'], "GET", "RADIOS");
						}
					}

					// FILTER CITY TABLE
					if (count($filters_city)>0) {
						$results->whereHas('city', function($query) use($filters_city, $cities_columns) {
							$this->exit = $this->filter($query, $filters_city, $cities_columns);
						});

						if ($this->exit) {
							return $this->sendError($this->constants['invalidFilters'], "GET", "RADIOS");
						}
					}

					// FILTER PROVINCE TABLE
					if (count($filters_province)>0) {
						$results->whereHas('city.province', function($query) use($filters_province, $provinces_columns) {
							$this->exit = $this->filter($query, $filters_province, $provinces_columns);
						});

						if ($this->exit) {
							return $this->sendError($this->constants['invalidFilters'], "GET", "RADIOS");
						}
					}

					// FILTER REGION TABLE
					if (count($filters_region)>0) {
						$results->whereHas('city.province.region', function($query) use($filters_region, $region_columns) {
							$this->exit = $this->filter($query, $filters_region, $region_columns);
						});

						if ($this->exit) {
							return $this->sendError($this->constants['invalidFilters'], "GET", "RADIOS");
						}
					}

					// FILTER COUNTRY TABLE
					if (count($filters_country)>0) {
						$results->whereHas('country', function($query) use($filters_country, $countries_columns) {
							$this->exit = $this->filter($query, $filters_country, $countries_columns);
						});

						if ($this->exit) {
							return $this->sendError($this->constants['invalidFilters'], "GET", "RADIOS");
						}
					}

					// FILTER EDITORIAL GROUP TABLE
					if (count($filters_editorialgroup)>0) {
						$results->whereHas('editorial_group', function($query) use($filters_editorialgroup, $editorialgroups_columns) {
							$this->exit = $this->filter($query, $filters_editorialgroup, $editorialgroups_columns);
						});

						if ($this->exit) {
							return $this->sendError($this->constants['invalidFilters'], "GET", "RADIOS");
						}
					}*/

					$this->total = $results->count();

					$results = 	$results
								->take($this->limit)
								->skip($this->limit * ($this->page-1))
								->get();

			} // end ($this->id !== NULL) else

			if (count($results) > 0) {
				return $this->sendSuccess($this->constants['success'], 
										  "GET", 
										  "RADIOS",
										  $results);
			}

			// NO RESULTS
			return $this->sendSuccess($this->constants['noContent'], 
									  "GET", 
									  "RADIOS", 
									  $results);
		}
		catch(\Exception $e) {
			print_r($e->getMessage());
			return $this->sendError($this->constants['generic'], "GET", "RADIOS");
		}
	}

	/**
	 * Update radio
	 *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $req
	 * @param Psr\\Http\\Message\\ResponseInterface 		$res
	 * @param array 									$args
	 *
	 * @return Psr\\Http\\Message\\ResponseInterface
	 */
	public function put_update($req, $res, $args) {
		// Token required
		if ($this->token == NULL) {
			return $this->sendError($this->constants['tokenRequired'], "GET", "RADIOS");
		}

		// Check if admin
		if (!$this->isAdmin($this->token)) {
			return $this->sendError($this->constants['authorizationRequired'], "GET", "RADIOS");
		}

		$radio_bkp = Radio::where('id', $this->id)->first();

		try {

			// ##### Validation #####

				$input = $this->params['data'];

				$required = array('name' => NULL, 'contries_id' => NULL);

				if (!$this->validation($input, $required, 'radios')) {
					return $this->sendError($this->constants['invalidParameter'], "POST", "RADIOS");
				}

			// ##### Registration #####

				$radio = Radio::where('id', $this->id);
				$prop_array = [];
				foreach ($input as $key => $item) {
					$prop_array[$item['field']] = $item['value'];
				}
				$radio->update($prop_array);

				return $this->sendSuccess($this->constants['success'], 
										  "GET", 
										  "RADIOS",
										  $input);

		}
		catch(\Exception $e) {

			// Restore backup of the record

			Radio::where('id', $radio_bkp->id)
				->update([
							"name" => $radio_bkp->name,
							"website" => $radio_bkp->website,
							"likes" => $radio_bkp->likes,
							"slogan" => $radio_bkp->slogan,
							"child_of_radio" => $radio_bkp->child_of_radio,
							"countries_id" => $radio_bkp->countries_id,
							"cities_id" => $radio_bkp->cities_id,
							"types_id" => $radio_bkp->types_id,
							"editorial_groups_id" => $radio_bkp->editorial_groups_id,
							"created_on" => $radio_bkp->created_on,
							"created_by" => $radio_bkp->created_by,
							"updated_on" => $radio_bkp->updated_on,
							"updated_by" => $radio_bkp->updated_by,
							"notes" => $radio_bkp->notes,
							"image1" => $radio_bkp->image1,
							"image2" => $radio_bkp->image2,
							"email" => $radio_bkp->email,
							"twitter" => $radio_bkp->twitter,
							"facebook" => $radio_bkp->facebook,
							"image_id" => $radio_bkp->image_id
						 ]);

			/* PER DEBUG */ print_r($e->getMessage());
			return $this->sendError($this->constants['generic'], "GET", "RADIOS");
		}
	}

	/**
	 * Delete (or soft delete) a radio.
	 * TODO: soft deleting
	 *
	 * @author Luca Brognara
 	 * @date December 2015
 	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $req
	 * @param Psr\\Http\\Message\\ResponseInterface 		$res
	 * @param array 									$args
	 *
	 * @return Psr\\Http\\Message\\ResponseInterface
	 */
	public function delete($req, $res, $args) {
		$id = $args['id'];

		// Token required
		if ($this->token == NULL) {
			return $this->sendError($this->constants['tokenRequired'], "GET", "RADIOS");
		}

		// Check if admin
		if (!$this->isAdmin($this->token)) {
			return $this->sendError($this->constants['authorizationRequired'], "GET", "RADIOS");
		}

		try {
			Radio::where('id', $id)->delete();
		}
		catch(\Exception $e) {
			/* PER DEBUG */ print_r($e->getMessage());
			return $this->sendError($this->constants['generic'], "DELETE", "RADIOS");
		}
	}

	/////////////////////////////////////////////////////////
	//////////////////////// ACTIONS ////////////////////////
	/////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////
	//////////////////////// PRIVATE ////////////////////////
	/////////////////////////////////////////////////////////

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
	 
	private function filter($query, $filters, $columns) {

		$exit = false;
		foreach ($filters as $key => $filter) {

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
				$exit = true; // $field_name doesn't match with the $columns so exit
				break;
			}

		}

		return $exit;
	}*/
}