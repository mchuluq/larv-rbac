<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'web'], function () {
    Route::match(['get', 'post'], '/auth/otp', 'Mchuluq\Larv\Rbac\Controllers\AccountController@doOtp')->name('auth.otp');

    Route::match(['get'], 'account/{account_id?}/{default?}', 'Mchuluq\Larv\Rbac\Controllers\AccountController@accountSwitch')->name('rbac.account.switch')->middleware(\Mchuluq\Larv\Rbac\Middlewares\Authenticate::with([
        'checkAccount' => false
    ]));
    
    Route::match(['get','post'], 'otp/register', 'Mchuluq\Larv\Rbac\Controllers\AccountController@otpRegister')->name('rbac.otp.register')->middleware(\Mchuluq\Larv\Rbac\Middlewares\Authenticate::class);
    Route::match(['get','post'], 'otp/confirm', function(){
        return redirect(URL()->previous());
    })->middleware(\Mchuluq\Larv\Rbac\Middlewares\ConfirmOtp::class)->name('rbac.otp.confirm');
});
