@extends('layout.app')
@section('title', 'WhatsApp Yönetimi')
@section('baslik', '🟢 WhatsApp Yönetimi')

@section('content')
{{-- WhatsApp Yönetimi sayfasını SİSTEM İÇİNDE (panel sidebar/header ile) göster.
     Kendi karanlık temalı standalone sayfası olduğu için iframe ile gömülür (bozulmadan). --}}
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm" style="height: calc(100vh - 140px); min-height: 520px;">
    <iframe src="/wa-yonetim" title="WhatsApp Yönetimi" style="width:100%; height:100%; border:0; display:block;"></iframe>
</div>
@endsection
