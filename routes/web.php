<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
/**
*	Добавляем эти маршруты в группу с префиксом API, чтобы разделять логику маршрутизации
*/
$router->group(['prefix' => 'api'], function () use ($router) {
	/**
	*	Добавление нового гостя
	*/
    $router->post('guest', 'GuestController@store');
    /**
    *	Получаем данные по гостю по ID
    */
    $router->get('guest/{id}', 'GuestController@show');
    /**
    *	Обновляем информацию о госте
    */
    $router->put('guest/{id}', 'GuestController@update');
    /**
    *	Удаляем гостя по его ID
    */
    $router->delete('guest/{id}', 'GuestController@delete');
});
