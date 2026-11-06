<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Garson Yönetimi paneli verisi: durum, aktivite, masa yoğunluğu ve özet oranlar.
 * Tutar/adisyon toplamları GarsonPerformans'tan gelir, burada yeniden hesaplanmaz.
 */
class GarsonPanel
{
    protected $subeId;

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
    }

    public static function molaEnsure()
    {
        if (Schema::hasTable('garson_mola')) return;
        Schema::create('garson_mola', function ($t) {
            $t->id();
            $t->unsignedBigInteger('sube_id');
            $t->unsignedBigInteger('personel_id');
            $t->timestamp('baslangic')->nullable();
            $t->timestamp('bitis')->nullable();
            $t->index(['sube_id', 'personel_id']);
        });
    }

    /** Molaya al / moladan çıkar. Dönüş: true=molada. */
    public function molaDegistir($personelId)
    {
        self::molaEnsure();
        DB::table('garson_mola')->where('sube_id', $this->subeId)->where('personel_id', $personelId)->whereNull('bitis')->where('baslangic', '<', now()->startOfDay())->update(['bitis' => now()]);
        $acik = DB::table('garson_mola')->where('sube_id', $this->subeId)->where('personel_id', $personelId)->whereNull('bitis')->first();
        if ($acik) {
            DB::table('garson_mola')->where('id', $acik->id)->update(['bitis' => now()]);
            return false;
        }
        DB::table('garson_mola')->insert(['sube_id' => $this->subeId, 'personel_id' => $personelId, 'baslangic' => now()]);
        return true;
    }

    public function veri($statPeriod = 'gunluk', $perfPeriod = 'haftalik')
    {
        self::molaEnsure();
        $simdi = now();
        $bugun = $simdi->copy()->startOfDay();
        $olaylar = $this->olaylar($simdi->copy()->subHours(12)->min($bugun));
        $personel = DB::table('personeller')->where('sube_id', $this->subeId)->pluck('ad', 'id');
        $garsonlar = DB::table('personeller')->where('sube_id', $this->subeId)->where('rol', 'garson')->where('aktif', 1)->orderBy('ad')->get(['id', 'ad']);

        $molada = DB::table('garson_mola')->where('sube_id', $this->subeId)->whereNull('bitis')->where('baslangic', '>=', $bugun)->pluck('baslangic', 'personel_id');
        $mesaide = Schema::hasTable('personel_mesai')
            ? DB::table('personel_mesai')->where('sube_id', $this->subeId)->whereNull('cikis_at')->where('giris_at', '>=', $bugun)->pluck('id', 'personel_id')
            : collect();

        $bugunRapor = $this->rapor('gunluk');
        $perfRapor = $this->rapor($perfPeriod);
        $statRapor = $this->rapor($statPeriod);
        $bugunMap = collect($bugunRapor)->keyBy('id');

        $liste = []; $aktifSay = [];
        foreach ($garsonlar as $g) {
            $id = (int) $g->id;
            $kendi = array_values(array_filter($olaylar, fn ($o) => $o['p'] === $id));
            $son = $kendi[0] ?? null;
            $dk = $son ? max(0, (int) Carbon::parse($son['t'])->diffInMinutes($simdi)) : null;
            $durum = isset($molada[$id]) ? 'mola' : ((isset($mesaide[$id]) || ($dk !== null && $dk <= 60)) ? 'aktif' : 'cevrimdisi');
            if ($durum === 'aktif') $aktifSay[$id] = ($dk !== null && $dk <= 60) ? count(array_filter($kendi, fn ($o) => Carbon::parse($o['t'])->gte($simdi->copy()->subHour()))) : 0;
            $spark = array_fill(0, 12, 0);
            foreach ($kendi as $o) {
                $dilim = max(0, (int) floor(Carbon::parse($o['t'])->diffInMinutes($simdi) / 10));
                if ($dilim < 12) $spark[11 - $dilim]++; // son 2 saat, 10'ar dakika
            }
            $b = $bugunMap[$id] ?? [];
            $liste[] = ['id' => $id, 'ad' => $g->ad, 'durum' => $durum, 'son' => $son['m'] ?? null, 'dk' => $dk, 'spark' => $spark,
                'mola_dk' => isset($molada[$id]) ? (int) Carbon::parse($molada[$id])->diffInMinutes($simdi) : null,
                'adisyon' => (int) ($b['adisyon'] ?? 0), 'kalem' => (int) ($b['kalem'] ?? 0), 'ciro' => (float) ($b['ciro'] ?? 0), 'adim' => (int) ($b['adim'] ?? 0)];
        }
        // Aktif olanlar üstte, sonra mola, sonra çevrimdışı
        $sira = ['aktif' => 0, 'mola' => 1, 'cevrimdisi' => 2];
        usort($liste, fn ($x, $y) => [$sira[$x['durum']], $x['dk'] ?? 99999] <=> [$sira[$y['durum']], $y['dk'] ?? 99999]);

        $dun = $simdi->copy()->subDay();
        $ozet = $this->ozet($bugun, $simdi);
        $ozetDun = $this->ozet($bugun->copy()->subDay(), $dun);
        $memn = $this->memnuniyet($simdi->copy()->subDays(7), $simdi);
        $memnOnce = $this->memnuniyet($simdi->copy()->subDays(14), $simdi->copy()->subDays(7));
        [$statBas] = $this->aralik($statPeriod, $simdi);
        $statOzet = $statPeriod === 'gunluk' ? $ozet : $this->ozet($statBas, $simdi);
        $skorlar = $this->skorlar($statRapor);

        $isi = [
            'saat' => $this->masaYogunluk($simdi->copy()->subHour()),
            'bugun' => $this->masaYogunluk($bugun),
            'hafta' => $this->masaYogunluk($simdi->copy()->subDays(7)),
        ];

        return [
            'ok' => 1,
            'kart' => [
                'aktif' => count(array_filter($liste, fn ($g) => $g['durum'] === 'aktif')),
                'toplam' => count($liste),
                'adim' => array_sum(array_column($bugunRapor, 'adim')),
                'servis_sn' => $ozet['servis_sn'], 'servis_trend' => $this->trend($ozet['servis_sn'], $ozetDun['servis_sn']),
                'tamam' => $ozet['kapanan'], 'tamam_trend' => $this->trend($ozet['kapanan'], $ozetDun['kapanan']),
                'memnuniyet' => $memn, 'memnuniyet_trend' => $this->trend($memn, $memnOnce),
            ],
            'halka' => [
                'doluluk' => $this->doluluk(),
                'servis_sn' => $statOzet['servis_sn'],
                'tamamlama' => $statOzet['acilan'] > 0 ? (int) round(min(1, $statOzet['kapanan'] / $statOzet['acilan']) * 100) : null,
                'verimlilik' => $skorlar ? (int) round(array_sum(array_column($skorlar, 'skor')) / count($skorlar)) : null,
            ],
            'garsonlar' => $liste,
            'aktif_yuk' => $aktifSay,
            'performans' => $this->skorlar($perfRapor),
            'olaylar' => array_map(function ($o) use ($personel) {
                return ['saat' => Carbon::parse($o['t'])->setTimezone('Europe/Istanbul')->format('H:i'), 'tip' => $o['tip'], 'p' => $o['p'], 'ad' => $personel[$o['p']] ?? 'Personel', 'm' => $o['m']];
            }, array_slice($olaylar, 0, 120)),
            'isi' => $isi,
            'cagri' => Schema::hasTable('masa_cagrilari') ? DB::table('masa_cagrilari')->where('sube_id', $this->subeId)->where('durum', 'bekliyor')->count() : 0,
        ];
    }

    protected function aralik($period, $simdi)
    {
        if ($period === 'haftalik') return [$simdi->copy()->subDays(7), $simdi];
        if ($period === 'aylik') return [$simdi->copy()->startOfMonth(), $simdi];
        return [$simdi->copy()->startOfDay(), $simdi];
    }

    /** GarsonPerformans ağır: her 30 sn yenilemede tekrar hesaplanmasın. */
    protected function rapor($period)
    {
        $p = in_array($period, ['gunluk', 'haftalik', 'aylik'], true) ? $period : 'gunluk';
        return Cache::remember('garson_panel_rapor_' . $this->subeId . '_' . $p, 60, function () use ($p) {
            return (new GarsonPerformans($this->subeId))->rapor($p, null)['garsonlar'] ?? [];
        });
    }

    /** En iyi garsona göre göreli skor: ciro %50, kalem %30, adisyon %20. */
    protected function skorlar(array $rapor)
    {
        if (!$rapor) return [];
        $mc = max(array_column($rapor, 'ciro')) ?: 0;
        $mk = max(array_column($rapor, 'kalem')) ?: 0;
        $ma = max(array_column($rapor, 'adisyon')) ?: 0;
        if (!$mc && !$mk && !$ma) return [];
        $out = [];
        foreach ($rapor as $g) {
            $s = .5 * ($mc ? $g['ciro'] / $mc : 0) + .3 * ($mk ? $g['kalem'] / $mk : 0) + .2 * ($ma ? $g['adisyon'] / $ma : 0);
            $out[] = ['id' => $g['id'], 'ad' => $g['ad'], 'skor' => (int) round($s * 100), 'adisyon' => $g['adisyon'], 'ciro' => $g['ciro'], 'kalem' => $g['kalem'],
                'ort_adisyon' => $g['ort_adisyon'], 'masa_dk' => $g['ort_servis_dk'], 'adim' => $g['adim']];
        }
        usort($out, fn ($x, $y) => $y['skor'] <=> $x['skor']);
        return $out;
    }

    protected function ozet($bas, $bit)
    {
        $q = DB::table('adisyonlar')->where('sube_id', $this->subeId)->where('kanal', 'salon');
        $acilan = (clone $q)->whereBetween('acilis', [$bas, $bit])->count();
        $kapanan = (clone $q)->where('durum', 'odendi')->whereBetween('kapanis', [$bas, $bit])->count();
        $servis = null;
        if (Schema::hasColumn('adisyon_kalemleri', 'servis_zamani') && Schema::hasColumn('adisyon_kalemleri', 'hazir_zamani')) {
            $top = 0; $adet = 0;
            $satirlar = DB::table('adisyon_kalemleri as k')->join('adisyonlar as a', 'k.adisyon_id', '=', 'a.id')
                ->where('a.sube_id', $this->subeId)->whereBetween('k.servis_zamani', [$bas, $bit])->whereNotNull('k.hazir_zamani')
                ->get(['k.hazir_zamani', 'k.servis_zamani']);
            foreach ($satirlar as $k) {
                $sn = Carbon::parse($k->hazir_zamani)->diffInSeconds(Carbon::parse($k->servis_zamani));
                if ($sn <= 3600) { $top += $sn; $adet++; } // unutulup sonradan işaretlenenler ortalamayı bozmasın
            }
            if ($adet) $servis = (int) round($top / $adet);
        }
        return ['acilan' => $acilan, 'kapanan' => $kapanan, 'servis_sn' => $servis];
    }

    protected function memnuniyet($bas, $bit)
    {
        if (!Schema::hasTable('memnuniyet')) return null;
        $ort = DB::table('memnuniyet')->where('sube_id', $this->subeId)->where('puan', '>', 0)->whereBetween('created_at', [$bas, $bit])->avg('puan');
        return $ort ? (int) round($ort * 20) : null;
    }

    protected function trend($simdi, $once)
    {
        if ($simdi === null || !$once) return null;
        $fark = (int) round(($simdi - $once) * 100 / $once);
        return $fark === 0 ? null : $fark;
    }

    protected function doluluk()
    {
        if (function_exists('_masaQrEnsure')) _masaQrEnsure($this->subeId);
        $toplam = DB::table('masalar')->where('sube_id', $this->subeId)->where('arsiv', 0)->count();
        if (!$toplam) return null;
        $dolu = DB::table('adisyonlar as a')->join('masalar as m', 'a.masa_id', '=', 'm.id')
            ->where('m.sube_id', $this->subeId)->where('m.arsiv', 0)->where('a.durum', 'acik')->distinct()->count('a.masa_id');
        return (int) round($dolu * 100 / $toplam);
    }

    /** masa_id => [personel_id => işlem sayısı]. İşlem: sipariş kalemi, servis ve hesap alma. */
    protected function masaYogunluk($bas)
    {
        $out = [];
        $ekle = function ($satirlar) use (&$out) {
            foreach ($satirlar as $s) $out[(int) $s->m][(int) $s->g] = ($out[(int) $s->m][(int) $s->g] ?? 0) + (int) $s->n;
        };
        $kalem = fn () => DB::table('adisyon_kalemleri as k')->join('adisyonlar as a', 'k.adisyon_id', '=', 'a.id')
            ->where('a.sube_id', $this->subeId)->whereNotNull('a.masa_id')->where('k.durum', '!=', 'iptal');

        $ekle($kalem()->where('k.created_at', '>=', $bas)
            ->selectRaw('a.masa_id as m, COALESCE(k.personel_id, a.acan_personel_id, 0) as g, COUNT(*) as n')
            ->groupBy('a.masa_id', DB::raw('COALESCE(k.personel_id, a.acan_personel_id, 0)'))->get());

        if (Schema::hasColumn('adisyon_kalemleri', 'servis_personel_id')) {
            $ekle($kalem()->whereNotNull('k.servis_personel_id')->where('k.servis_zamani', '>=', $bas)
                ->selectRaw('a.masa_id as m, k.servis_personel_id as g, COUNT(*) as n')
                ->groupBy('a.masa_id', 'k.servis_personel_id')->get());
        }

        $ekle(DB::table('odemeler as o')->join('adisyonlar as a', 'o.adisyon_id', '=', 'a.id')
            ->where('a.sube_id', $this->subeId)->whereNotNull('a.masa_id')->whereNotNull('o.personel_id')->where('o.created_at', '>=', $bas)
            ->selectRaw('a.masa_id as m, o.personel_id as g, COUNT(*) as n')
            ->groupBy('a.masa_id', 'o.personel_id')->get());

        return (object) $out;
    }

    /** Yeniden eskiye olay listesi: sipariş, masa açma, servis, ödeme, mesai, mola. */
    protected function olaylar($bas)
    {
        $out = [];
        $ad = fn ($a) => $a === null ? 'Masa' : (is_numeric($a) ? 'Masa ' . $a : $a);

        foreach (DB::table('adisyonlar as a')->leftJoin('masalar as m', 'a.masa_id', '=', 'm.id')
            ->where('a.sube_id', $this->subeId)->whereNotNull('a.masa_id')->whereNotNull('a.acan_personel_id')->where('a.acilis', '>=', $bas)
            ->orderByDesc('a.acilis')->limit(200)->get(['a.acan_personel_id as p', 'a.acilis as t', 'm.ad as masa']) as $s) {
            $out[] = ['t' => (string) $s->t, 'p' => (int) $s->p, 'tip' => 'acilis', 'm' => $ad($s->masa) . ' · masa açıldı'];
        }

        // Aynı dakikada aynı masaya girilen ürünler tek sipariş sayılır
        $gruplar = [];
        foreach (DB::table('adisyon_kalemleri as k')->join('adisyonlar as a', 'k.adisyon_id', '=', 'a.id')->leftJoin('masalar as m', 'a.masa_id', '=', 'm.id')
            ->where('a.sube_id', $this->subeId)->whereNotNull('a.masa_id')->where('k.durum', '!=', 'iptal')->where('k.created_at', '>=', $bas)
            ->orderByDesc('k.created_at')->limit(600)->get(['k.personel_id', 'a.acan_personel_id', 'a.id as aid', 'k.created_at as t', 'm.ad as masa']) as $s) {
            $p = (int) ($s->personel_id ?: $s->acan_personel_id);
            if (!$p) continue;
            $anahtar = $s->aid . '|' . $p . '|' . substr((string) $s->t, 0, 16);
            if (!isset($gruplar[$anahtar])) $gruplar[$anahtar] = ['t' => (string) $s->t, 'p' => $p, 'masa' => $s->masa, 'n' => 0];
            $gruplar[$anahtar]['n']++;
        }
        foreach ($gruplar as $g) $out[] = ['t' => $g['t'], 'p' => $g['p'], 'tip' => 'siparis', 'm' => $ad($g['masa']) . ' · sipariş girdi (' . $g['n'] . ' ürün)'];

        if (Schema::hasColumn('adisyon_kalemleri', 'servis_personel_id')) {
            foreach (DB::table('adisyon_kalemleri as k')->join('adisyonlar as a', 'k.adisyon_id', '=', 'a.id')->leftJoin('masalar as m', 'a.masa_id', '=', 'm.id')
                ->where('a.sube_id', $this->subeId)->whereNotNull('a.masa_id')->whereNotNull('k.servis_personel_id')->where('k.servis_zamani', '>=', $bas)
                ->orderByDesc('k.servis_zamani')->limit(200)->get(['k.servis_personel_id as p', 'k.servis_zamani as t', 'm.ad as masa']) as $s) {
                $out[] = ['t' => (string) $s->t, 'p' => (int) $s->p, 'tip' => 'servis', 'm' => $ad($s->masa) . ' · servis edildi'];
            }
        }

        foreach (DB::table('odemeler as o')->join('adisyonlar as a', 'o.adisyon_id', '=', 'a.id')->leftJoin('masalar as m', 'a.masa_id', '=', 'm.id')
            ->where('a.sube_id', $this->subeId)->whereNotNull('a.masa_id')->whereNotNull('o.personel_id')->where('o.created_at', '>=', $bas)
            ->orderByDesc('o.created_at')->limit(200)->get(['o.personel_id as p', 'o.created_at as t', 'm.ad as masa']) as $s) {
            $out[] = ['t' => (string) $s->t, 'p' => (int) $s->p, 'tip' => 'odeme', 'm' => $ad($s->masa) . ' · hesap alındı'];
        }

        if (Schema::hasTable('personel_mesai')) {
            foreach (DB::table('personel_mesai')->where('sube_id', $this->subeId)->where('giris_at', '>=', $bas)->orderByDesc('giris_at')->limit(100)->get(['personel_id as p', 'giris_at as t']) as $s) {
                $out[] = ['t' => (string) $s->t, 'p' => (int) $s->p, 'tip' => 'mesai', 'm' => 'Mesaiye başladı'];
            }
        }
        foreach (DB::table('garson_mola')->where('sube_id', $this->subeId)->where('baslangic', '>=', $bas)->orderByDesc('baslangic')->limit(100)->get(['personel_id as p', 'baslangic as t']) as $s) {
            $out[] = ['t' => (string) $s->t, 'p' => (int) $s->p, 'tip' => 'mola', 'm' => 'Molaya çıktı'];
        }

        usort($out, fn ($x, $y) => strcmp($y['t'], $x['t']));
        return $out;
    }
}
