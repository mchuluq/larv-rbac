<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'web'], function () {
    Route::match(['get', 'post'], '/auth/otp', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@doOtp')->name('auth.otp');

    Route::get('account/{account_id?}', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@accountSwitch')->name('rbac.account.switch')->middleware(\Mchuluq\Larv\Rbac\Http\Middlewares\Authenticate::with([
        'checkAccount' => false
    ]));
    
    Route::get('user/otp', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@otpRequest')->name('rbac.otp.request')->middleware('rbac-auth');
    Route::post('user/otp', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@otpRegister')->name('rbac.otp.register')->middleware('rbac-auth');
    Route::delete('user/otp', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@otpUnregister')->name('rbac.otp.unregister')->middleware('rbac-auth');
});
