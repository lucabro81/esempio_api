<?php

namespace api\Models;

use api\Models\Model as Model;

class Radio extends Model {

	protected $table = "radios";

	public function created_by() {
		return $this->belongsTo('api\Models\User', 'created_by', 'id');
	}

	public function updated_by() {
		return $this->belongsTo('api\Models\User', 'updated_by', 'id');
	}

	public function city() {
		return $this->belongsTo('api\Models\City', 'cities_id', 'id');
	}

	public function country() {
		return $this->belongsTo('api\Models\Country', 'countries_id', 'id');
	}

	public function type() {
		return $this->belongsTo('api\Models\Type', 'types_id', 'id');
	}

	public function editorial_group() {
		return $this->belongsTo('api\Models\Editorial_group', 'editorial_groups_id', 'id');
	}

	public function frequencies() {
		return $this->hasMany('api\Models\Frequency', 'radios_id', 'id');
	}

	public function streams() {
		return $this->hasMany('api\Models\Stream', 'radios_id', 'id');
	}

	public function has_genres() {
		return $this->hasMany('api\Models\Radio_has_genre', 'radios_id', 'id');
	}

}
