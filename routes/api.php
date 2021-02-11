<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::get('/ping', function(){
    return ['pong' => true];
});

Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);
Route::post('/auth/logout', [AuthController::class, 'logout']);
Route::post('/user', [AuthController::class, 'create']);


Route::put('/user', [UserController::class, 'update']);
Route::post('/user/avatar', [UserController::class, 'updateAvatar']);
Route::post('/user/cover', [UserController::class, 'updateCover']);

Route::get('/user', [UserController::class, 'read']);
Route::get('/user/feed', [FeedController::class, 'userFeed']);
Route::post('/feed', [FeedController::class, 'create']);


Route::get('/user/photos', [FeedController::class, 'getPhotos']);
Route::get('/user/{id}', [UserController::class, 'read']);
Route::get('/user/{id}/feed', [FeedController::class, 'userFeed']);
Route::get('/feed', [FeedController::class, 'read']);

Route::get('/user/{id}/photos', [FeedController::class, 'getPhotos']);

Route::post('/user/{id}/follow', [UserController::class, 'follow']);
Route::get('/user/{id}/followers', [UserController::class, 'followers']);


Route::post('/post/{id}/comment', [PostController::class, 'comment']);
Route::post('/post/{id}/like', [PostController::class, 'like']);

Route::get('/search', [SearchController::class, 'search']);