@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="alert alert-primary">{{__('Get the OTP code from the Google Authenticator app on your device.')}}</div>
            <form method="POST" action="{{ $url }}" class="card">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="otp-input" class="font-weight-bold">{{$label}})</label>
                        <div>
                            <input id="otp-input" type="text" class="form-control @error('otp') is-invalid @enderror" name="otp_input" required placeholder="Kode OTP">
                            @error('otp')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">{{__('Verify code')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection