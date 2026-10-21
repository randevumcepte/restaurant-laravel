@extends('layout.app')
@section('title', 'Çark Kazananlar')
@section('rk_ikon', 'emoji_events')
@section('baslik', 'Çark Kazananlar')
@section('rk_alt', 'Çarkı çeviren müşteriler ve kazandıkları ödüller')
@section('content')
<style>
.ckw-tabs { display:flex; gap:6px; background:#fff; border-radius:16px; padding:8px; box-shadow:0 4px 24px rgba(0,0,0,.08); margin-bottom:20px; flex-wrap:wrap; }
.ckw-tab { flex:1; min-width:150px; text-align:center; padding:13px 10px; border-radius:12px; font-size:15px; font-weight:700; color:#636e72; text-decoration:none; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:8px; }
.ckw-tab:hover { background:rgba(108,92,231,.07); color:#6c5ce7; }
.ckw-tab.active { background:linear-gradient(135deg,#6c5ce7,#a29bfe); color:#fff; box-shadow:0 6px 18px rgba(108,92,231,.3); }
</style>
<div style="padding:20px 4px">
    <div class="ckw-tabs">
        <a href="/cark-yonetim" class="ckw-tab"><span class="material-icons-outlined">casino</span> Çarkıfelek</a>
        <a href="/cark-kazananlar" class="ckw-tab active"><span class="material-icons-outlined">emoji_events</span> Çark Kazananlar</a>
    </div>

    @php
        $tipAd = function($t) {
            return [
                'puan' => '💰 Puan', 'yuzde' => '% İndirim', 'tutar' => '₺ İndirim',
                'hizmet_indirimi' => '✂️ Hizmet İnd.', 'urun_indirimi' => '📦 Ürün İnd.', 'paket_indirimi' => '🎁 Paket İnd.',
                'tekrar_dene' => '🔄 Tekrar', 'tekrar' => '🔄 Tekrar', 'bos' => '— Boş',
            ][$t] ?? $t;
        };
    @endphp

    <div class="rk-card" style="padding:0;overflow:hidden">
        @if ($rows->isEmpty())
            <div style="padding:50px 20px;text-align:center;color:var(--sub)">
                <span class="material-icons-outlined" style="font-size:44px;opacity:.4">casino</span>
                <div style="margin-top:10px;font-weight:700">Henüz çark çevrilmemiş</div>
                <div style="font-size:13px;margin-top:4px">Müşteriler çarkı çevirdikçe burada listelenecek.</div>
            </div>
        @else
            <table style="width:100%;border-collapse:collapse;font-size:14px">
                <thead>
                    <tr style="background:var(--card2);color:var(--sub2);text-align:left">
                        <th style="padding:12px 16px;font-weight:700">Müşteri</th>
                        <th style="padding:12px 16px;font-weight:700">Kazandığı</th>
                        <th style="padding:12px 16px;font-weight:700">Tip</th>
                        <th style="padding:12px 16px;font-weight:700">Kupon</th>
                        <th style="padding:12px 16px;font-weight:700">Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr style="border-top:1px solid var(--line)">
                            <td style="padding:11px 16px;color:var(--ink);font-weight:600">
                                {{ trim(($r->m_ad ?? '').' '.($r->m_soyad ?? '')) ?: 'Müşteri' }}
                                <div style="font-size:12px;color:var(--sub);font-weight:400">{{ $r->m_tel ?? '' }}</div>
                            </td>
                            <td style="padding:11px 16px;color:var(--ink)">{{ $r->dilim_ad }}</td>
                            <td style="padding:11px 16px;color:var(--sub2)">{{ $tipAd($r->tip) }}</td>
                            <td style="padding:11px 16px">
                                @if ($r->kupon_kodu)
                                    <span style="font-family:monospace;font-weight:700;letter-spacing:1px;color:#7C3AED;background:rgba(124,58,237,.1);padding:3px 8px;border-radius:6px">{{ $r->kupon_kodu }}</span>
                                @else
                                    <span style="color:var(--sub)">—</span>
                                @endif
                            </td>
                            <td style="padding:11px 16px;color:var(--sub)">{{ \Carbon\Carbon::parse($r->created_at)->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
