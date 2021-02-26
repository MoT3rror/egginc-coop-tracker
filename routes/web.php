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
    ->middleware(['auth', 'be-member-of-guild'])
;

Route::get('guild/{guildId}', 'Guild@index')
    ->middleware(['auth', 'be-member-of-guild'])
    ->name('guild.index')
;

Route::get('guild/{guildId}/settings', 'Guild@settings')
    ->middleware(['auth', 'be-admin-of-guild'])
    ->name('guild.settings')
;

Route::post('guild/{guildId}/settings', 'Guild@settingsSave')
    ->middleware(['auth', 'be-admin-of-guild'])
    ->name('guild.settingsSave')
;

Route::get('make-coops/{guildId}/{contractId}', 'CurrentContracts@makeCoops')
    ->name('make-coops')
    ->middleware(['auth', 'be-admin-of-guild'])
;

Route::post('make-coops/{guildId}/{contractId}', 'CurrentContracts@makeCoopsSave')
    ->middleware(['auth', 'be-admin-of-guild'])
;

Route::get('login/discord', 'Discord@redirect')->name('discord-login');

Route::get('login/discord/callback', 'Discord@callback');

Route::get('logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');
