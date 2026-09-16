<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

/**
 * GARSON PERFORMANS + ISI HARITASI (POS aktivitesinden, donanimsiz).
 * - Karne: her garsonun period icinde adisyon/ciro/kalem/ort.servis + saat dagilimi + adim.
 * - Isi haritasi: masalarin POS x,y konumu + garsonun o masadaki is agirligi (kalem sayisi/ciro)
 *   -> salon planinda "en cok nerede calisti" sicaklik haritasi. GPS/beacon YOK.
 */
class GarsonPerformans
{
    protected $subeId;

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
    }

    /** period -> [baslangic, bitis] Carbon. */
    protected function aralik($period)
    {
        $bitis = now();
        switch ($period) {
            case 'haftalik': $bas = now()->subDays(7); break;
            case 'aylik':    $bas = now()->startOfMonth(); break;
            case 'yillik':   $bas = now()->startOfYear(); break;
            default:         $bas = now()->startOfDay(); break;   // gunluk
        }
        return [$bas, $bitis];
    }

    /**
     * @param string   $period   gunluk|haftalik|aylik|yillik
     * @param int|null $garsonId  Verilirse isi haritasi da o garson icin doner.
     */
    public function rapor($period = 'gunluk', $garsonId = null)
    {
        [$bas, $bitis] = $this->aralik($period);

        $garsonlar = DB::table('personeller')
            ->where('sube_id', $this->subeId)->where('rol', 'garson')->where('aktif', 1)
            ->orderBy('ad')->get(['id', 'ad']);

        // Period icinde acilan salon adisyonlari
        $adisyonlar = DB::table('adisyonlar')
            ->where('sube_id', $this->subeId)->where('kanal', 'salon')
            ->whereBetween('acilis', [$bas, $bitis])
            ->get(['id', 'acan_personel_id', 'masa_id', 'toplam', 'durum', 'acilis', 'kapanis']);

        // Kalemler (kim ekledi -> yoksa adisyonu acan). Isi + kalem sayaci icin.
        $adIds = $adisyonlar->pluck('id')->all();
        $kalemler = empty($adIds) ? collect() : DB::table('adisyon_kalemleri')
            ->whereIn('adisyon_id', $adIds)->where('durum', '!=', 'iptal')
            ->get(['adisyon_id', 'personel_id', 'tutar']);

        $adToMasa = []; $adToAcan = [];
        foreach ($adisyonlar as $a) { $adToMasa[$a->id] = $a->masa_id; $adToAcan[$a->id] = $a->acan_personel_id; }

        // Garson bazli toplamlar
        $ciro = []; $adisyonSay = []; $kalemSay = []; $servisTop = []; $servisAdet = []; $saat = [];
        foreach ($adisyonlar as $a) {
            $g = (int) $a->acan_personel_id;
            if (!$g) continue;
            $adisyonSay[$g] = ($adisyonSay[$g] ?? 0) + 1;
            $h = (int) Carbon::parse($a->acilis)->format('G');
            $saat[$g][$h] = ($saat[$g][$h] ?? 0) + 1;
            if ($a->durum === 'odendi') {
                $ciro[$g] = ($ciro[$g] ?? 0) + (float) $a->toplam;
                if ($a->kapanis) {
                    $dk = Carbon::parse($a->acilis)->diffInMinutes(Carbon::parse($a->kapanis));
                    $servisTop[$g] = ($servisTop[$g] ?? 0) + $dk; $servisAdet[$g] = ($servisAdet[$g] ?? 0) + 1;
                }
            }
        }
        foreach ($kalemler as $k) {
            $g = (int) ($k->personel_id ?: ($adToAcan[$k->adisyon_id] ?? 0));
            if (!$g) continue;
            $kalemSay[$g] = ($kalemSay[$g] ?? 0) + 1;
        }

        // Adim (period toplami)
        $adim = $this->adimlar($bas, $bitis);

        $out = [];
        foreach ($garsonlar as $g) {
            $id = (int) $g->id;
            $sd = [];
            for ($h = 8; $h <= 23; $h++) $sd[] = ['saat' => $h, 'adisyon' => (int) ($saat[$id][$h] ?? 0)];
            $out[] = [
                'id' => $id, 'ad' => $g->ad,
                'adisyon' => (int) ($adisyonSay[$id] ?? 0),
                'ciro' => round($ciro[$id] ?? 0, 2),
                'kalem' => (int) ($kalemSay[$id] ?? 0),
                'ort_adisyon' => ($adisyonSay[$id] ?? 0) > 0 ? round(($ciro[$id] ?? 0) / $adisyonSay[$id], 2) : 0,
                'ort_servis_dk' => ($servisAdet[$id] ?? 0) > 0 ? round($servisTop[$id] / $servisAdet[$id]) : 0,
                'adim' => (int) ($adim[$id] ?? 0),
                'saat_dagilim' => $sd,
            ];
        }
        // Ciroya gore sirala (en iyi ustte)
        usort($out, fn ($x, $y) => $y['ciro'] <=> $x['ciro']);

        $res = ['ok' => 1, 'period' => $period, 'garsonlar' => $out];

        // Isi haritasi: garson secildiyse o garson, yoksa TUM garsonlar (salonun genel yogunlugu)
        $res['isi'] = $this->isiHaritasi($adisyonlar, $kalemler, $adToMasa, $adToAcan, $garsonId ? (int) $garsonId : null);
        return $res;
    }

    /** Masa bazli is agirligi (kalem sayisi + ciro) + POS x,y konumu. */
    protected function isiHaritasi($adisyonlar, $kalemler, $adToMasa, $adToAcan, $garsonId)
    {
        // masa_id => ['kalem'=>n, 'ciro'=>x]
        $agirlik = [];
        foreach ($kalemler as $k) {
            $g = (int) ($k->personel_id ?: ($adToAcan[$k->adisyon_id] ?? 0));
            if ($garsonId && $g !== $garsonId) continue;
            $masaId = $adToMasa[$k->adisyon_id] ?? null;
            if (!$masaId) continue;
            $agirlik[$masaId]['kalem'] = ($agirlik[$masaId]['kalem'] ?? 0) + 1;
            $agirlik[$masaId]['ciro'] = ($agirlik[$masaId]['ciro'] ?? 0) + (float) $k->tutar;
        }

        $masalar = DB::table('masalar as m')->leftJoin('bolgeler as b', 'm.bolge_id', '=', 'b.id')
            ->where('m.sube_id', $this->subeId)
            ->get(['m.id', 'm.ad', 'm.bolge_id', 'm.x', 'm.y', 'b.ad as bolge_ad']);

        $maxKalem = 0;
        $list = [];
        foreach ($masalar as $m) {
            $kl = (int) ($agirlik[$m->id]['kalem'] ?? 0);
            if ($kl > $maxKalem) $maxKalem = $kl;
            $list[] = [
                'id' => (int) $m->id, 'ad' => $m->ad, 'bolge_id' => (int) $m->bolge_id, 'bolge_ad' => $m->bolge_ad,
                'x' => (int) $m->x, 'y' => (int) $m->y,
                'agirlik' => $kl, 'ciro' => round($agirlik[$m->id]['ciro'] ?? 0, 2),
            ];
        }
        $bolgeler = DB::table('bolgeler')->where('sube_id', $this->subeId)->orderBy('sira')->get(['id', 'ad']);
        return ['masalar' => $list, 'bolgeler' => $bolgeler, 'max_agirlik' => $maxKalem, 'garson_id' => $garsonId];
    }

    // ---------------- ADIM ----------------
    protected function adimTablo()
    {
        if (Schema::hasTable('personel_adim')) return;
        try {
            Schema::create('personel_adim', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->unsignedBigInteger('personel_id');
                $t->date('tarih');
                $t->unsignedInteger('adim')->default(0);
                $t->timestamp('updated_at')->nullable();
                $t->unique(['personel_id', 'tarih']);
                $t->index(['sube_id', 'tarih']);
            });
        } catch (\Throwable $e) {}
    }

    /** period icindeki gunluk adim toplamlari [personel_id => adim]. */
    protected function adimlar($bas, $bitis)
    {
        $this->adimTablo();
        try {
            $rows = DB::table('personel_adim')->where('sube_id', $this->subeId)
                ->whereBetween('tarih', [$bas->copy()->toDateString(), $bitis->copy()->toDateString()])
                ->selectRaw('personel_id, SUM(adim) as t')->groupBy('personel_id')->get();
            $map = [];
            foreach ($rows as $r) $map[(int) $r->personel_id] = (int) $r->t;
            return $map;
        } catch (\Throwable $e) { return []; }
    }

    /** Garsonun BUGUNku adim toplamini kaydet (telefon sensoru gonderir). */
    public function adimKaydet($personelId, $adim)
    {
        $this->adimTablo();
        $adim = max(0, (int) $adim);
        $bugun = now()->toDateString();
        try {
            $var = DB::table('personel_adim')->where('personel_id', (int) $personelId)->where('tarih', $bugun)->first();
            if ($var) {
                // Gun icinde sadece artan degeri yaz (sensor sifirlanirsa geriye gitmesin)
                if ($adim > (int) $var->adim) {
                    DB::table('personel_adim')->where('id', $var->id)->update(['adim' => $adim, 'updated_at' => now()]);
                }
            } else {
                DB::table('personel_adim')->insert([
                    'sube_id' => $this->subeId, 'personel_id' => (int) $personelId, 'tarih' => $bugun,
                    'adim' => $adim, 'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {}
        return ['ok' => 1];
    }
}
