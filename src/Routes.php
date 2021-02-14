<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'web'], function () {
    // OTP auth
    Route::match(['get', 'post'], '/auth/otp', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@doOtp')->name('auth.otp');

    // select account
    Route::get('account/{account_id?}', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@accountSwitch')->name('rbac.account.switch')->middleware(\Mchuluq\Larv\Rbac\Http\Middlewares\Authenticate::with([
        'checkAccount' => false
    ]));
    
    // OTP register/unregister
    Route::get('user/otp', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@otpRequest')->name('rbac.otp.request')->middleware('rbac-auth');
    Route::post('user/otp', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@otpRegister')->name('rbac.otp.register')->middleware('rbac-auth');
    Route::delete('user/otp', 'Mchuluq\Larv\Rbac\Http\Controllers\AccountController@otpUnregister')->name('rbac.otp.unregister')->middleware('rbac-auth');

    // webauthn
    Route::post('webauthn/login/details','Mchuluq\Larv\Rbac\Http\Controllers\WebauthnController@loginDetails')->name('webauthn.login.details');
    Route::post('webauthn/login', 'Mchuluq\Larv\Rbac\Http\Controllers\WebauthnController@login')->name('webauthn.login');
    Route::post('webauthn/create/details', 'Mchuluq\Larv\Rbac\Http\Controllers\WebAuthnController@createDetails')->middleware('rbac-auth')->name('webauthn.create.details');
    Route::post('webauthn/create', 'Mchuluq\Larv\Rbac\Http\Controllers\WebAuthnController@create')->middleware('rbac-auth')->name('webauthn.create');
    Route::match(['get','delete'],'webauthn/user', 'Mchuluq\Larv\Rbac\Http\Controllers\WebAuthnController@user')->middleware('rbac-auth')->name('webauthn.user');
});
