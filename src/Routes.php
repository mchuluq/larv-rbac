<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'web'], function () {
    // select account
    Route::get('account/{account_id?}', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@accountSwitch')->name('rbac.account.switch')->middleware('auth');

    Route::get('devices', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@devices')->name('rbac.devices')->middleware('auth');
    Route::delete('devices/{id?}', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@destroy')->name('rbac.devices.destroy')->middleware('auth');

});
