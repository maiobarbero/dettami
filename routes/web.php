<?php

use App\Http\Controllers\Dettami;
use Illuminate\Support\Facades\Route;

Route::view('/', 'dettami');

Route::post('/recorder/upload', Dettami::class);
