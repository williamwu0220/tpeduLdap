@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-default" style="margin-top: 20px">
                <div class="card-header">驗證您的電子郵件地址</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            一個全新的電子郵件地址確認連結已經寄送到您的信箱。
                        </div>
                    @endif

                    在您繼續動作之前，請先開啟您的電子郵件信箱，尋找您的郵件地址驗證信，並點擊確認按鈕。<br>
                    如果您未收到驗證信，<a href="{{ route('verification.resend') }}">請點擊這裡為您重新傳送！</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection