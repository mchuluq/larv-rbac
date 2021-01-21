@extends('layouts.app')

@section('content')
<div class="container">
      <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">{{__('rbac::rbac.list_select_account')}}</div>
                <div class="list-group list-group-flush">
                    @foreach($accounts as $acc)
                    <a href="{{route('rbac.account.switch', ['account_id' => $acc->id])}}" class="list-group-item list-group-item-action @if($acc->id == $user->account_id) list-group-item-primary @endif"">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">{{$acc->accountable_id}}</h5>
                            <small>{{$acc->group_id}}</small>
                        </div>
                        <code class="mb-1">{{$acc['accountable_type']}}</code>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection