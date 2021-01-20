@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <form action="{{route('rbac.otp.register')}}" method="post" class="card card-default">
                @csrf
                <div class="card-header">{{__('Set up Google Authenticator')}}</div>
                <div class="card-body">
                    <span>{{__('Set up two-factor-authentication by scanning the barcode below, or adding the following code on the google authenticator app')}} <code>{{ $otp_secret }}</code></span>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $otp_qr_image }}">
                </div>
                <div class="card-footer text-center">
                    <input type="hidden" name="otp_secret" value="{{ $otp_secret }}">
                    <button type="submit" class="btn btn-primary">{{__('Save')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection