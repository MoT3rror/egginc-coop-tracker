<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'Home@index')->name('home');

Route::get('current-contracts', 'CurrentContracts@index')->name('current-contracts');

Route::get('current-contract-status-signed/{guildId}/{contractId}', 'CurrentContracts@status')
    ->name('contract-status')
    ->middleware('signed')
;

Route::get('current-contract-status/{guildId}/{contractId}', 'CurrentContracts@guildStatus')
    ->name('contract-guild-status')
    ->middleware('auth')
;

Route::get('guild/{guildId}', 'Guild@index')
    ->middleware('auth')
    ->name('guild.index')
;

Route::get('guild/{guildId}/settings', 'Guild@settings')
    ->middleware('auth')
    ->name('guild.settings')
;

Route::get('login/discord', 'Discord@redirect')->name('discord-login');

Route::get('login/discord/callback', 'Discord@callback');

Route::get('logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');
