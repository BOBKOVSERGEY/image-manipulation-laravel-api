<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\V1\ImageManipulationController;
use \App\Http\Controllers\V1\AlbumController;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('v1')->group(function() {
        Route::apiResources([
            'album' => AlbumController::class,
        ]);
        Route::get('image', [ImageManipulationController::class, 'index']);
        Route::get('image/by-album/{album}', [ImageManipulationController::class, 'byAlbum']);
        Route::get('image/{image}', [ImageManipulationController::class, 'show']);
        Route::post('image/resize', [ImageManipulationController::class, 'resize']);
        Route::delete('image/{image}', [ImageManipulationController::class, 'destroy']);
    });

});


