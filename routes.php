<?php

$cp = "api\Controllers\\";

$app->post('/cities', 	   	 $cp."CitiesController:post_create");
$app->get('/cities', 		 $cp."CitiesController:get_read");
$app->put('/cities/{id}', 	 $cp."CitiesController:put_update");
$app->delete('/cities/{id}', $cp."CitiesController:delete");

$app->post('/countries', 	   	$cp."CountriesController:post_create");
$app->get('/countries', 		$cp."CountriesController:get_read");
$app->put('/countries/{id}',	$cp."CountriesController:put_update");
$app->delete('/countries/{id}',	$cp."CountriesController:delete");

$app->post('/frequencies', 		  $cp."FrequenciesController:post_create");
$app->get('/frequencies', 		  $cp."FrequenciesController:get_read");
$app->put('/frequencies/{id}',	  $cp."FrequenciesController:put_update");
$app->delete('/frequencies/{id}', $cp."FrequenciesController:delete");

$app->post('/provinces', 	   	 $cp."ProvincesController:post_create");
$app->get('/provinces', 		 $cp."ProvincesController:get_read");
$app->put('/provinces/{id}', 	 $cp."ProvincesController:put_update");
$app->delete('/provinces/{id}',	 $cp."ProvincesController:delete");

$app->post('/radios', 		 $cp."RadiosController:post_create");
$app->get('/radios', 		 $cp."RadiosController:get_read");
$app->put('/radios/{id}',	 $cp."RadiosController:put_update");
$app->delete('/radios/{id}', $cp."RadiosController:delete");

$app->post('/regions', 	   	 	$cp."RegionsController:post_create");
$app->get('/regions', 		 	$cp."RegionsController:get_read");
$app->put('/regions/{id}',		$cp."RegionsController:put_update");
$app->delete('/regions/{id}',	$cp."RegionsController:delete");

$app->post('/requests', 	   	  $cp."RequestsController:post_create");
$app->get('/requests', 		   	  $cp."RequestsController:get_read");
$app->put('/requests/{id}',	   	  $cp."RequestsController:put_update");
$app->delete('/requests/{id}', 	  $cp."RequestsController:delete");
$app->put('/requests/state/{id}', $cp."RequestsController:put_state");

$app->post('/stations', 	   $cp."StationsController:post_create");
$app->get('/stations', 		   $cp."StationsController:get_read");
$app->put('/stations/{id}',	   $cp."StationsController:put_update");
$app->delete('/stations/{id}', $cp."StationsController:delete");

$app->post('/streams', 	   	  $cp."StreamsController:post_create");
$app->get('/streams', 		  $cp."StreamsController:get_read");
$app->put('/streams/{id}',	  $cp."StreamsController:put_update");
$app->delete('/streams/{id}', $cp."StreamsController:delete");

$app->post('/users', 						$cp."UsersController:post_create");
$app->get('/users', 						$cp."UsersController:get_read");
$app->put('/users/{id}', 					$cp."UsersController:put_update");
$app->delete('/users/{id}', 				$cp."UsersController:delete");
$app->post('/users/login', 					$cp."UsersController:post_login");
$app->post('/users/logout', 				$cp."UsersController:post_logout");
$app->post('/users/request-new-password', 	$cp."UsersController:post_request_new_password");
