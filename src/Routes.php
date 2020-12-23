<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'web'], function () {
    // AUTH
    Route::match(['get', 'post'], '/auth/login', 'Mchuluq\Larv\Rbac\Controllers\AccountController@doLogin')->name('auth.login');
    Route::match(['get', 'post'], '/auth/logout', 'Mchuluq\Larv\Rbac\Controllers\AccountController@doLogout')->name('auth.logout');
    Route::match(['get', 'post'], '/auth/otp', 'Mchuluq\Larv\Rbac\Controllers\AccountController@doOtp')->name('auth.otp');

    Route::match(['get', 'post'], '/password/forgot', 'Mchuluq\Larv\Rbac\Controllers\AccountController@passwordForgot')->name('password.email');
    Route::match(['get'], '/password/reset/{token}', 'Mchuluq\Larv\Rbac\Controllers\AccountController@passwordReset')->name('password.reset');
    Route::match(['post'], '/password/reset', 'Mchuluq\Larv\Rbac\Controllers\AccountController@passwordReset')->name('password.update');
    Route::match(['get', 'post'], 'password/confirm', 'Mchuluq\Larv\Rbac\Controllers\AccountController@passwordConfirm')->name('password.confirm');
    
    Route::match(['get'], 'account/{account_id?}/{default?}', 'Mchuluq\Larv\Rbac\Controllers\AccountController@accountSwitch')->name('rbac.account.switch')->middleware(\Mchuluq\Larv\Rbac\Middlewares\Authenticate::with([
        'checkAccount' => false
    ]));
    
    Route::match(['get','post'], 'otp/register', 'Mchuluq\Larv\Rbac\Controllers\AccountController@otpRegister')->name('rbac.otp.register')->middleware(\Mchuluq\Larv\Rbac\Middlewares\Authenticate::class);
    Route::match(['get','post'], 'otp/confirm', function(){
        return redirect(URL()->previous());
    })->middleware(\Mchuluq\Larv\Rbac\Middlewares\ConfirmOtp::class)->name('rbac.otp.confirm');
});
