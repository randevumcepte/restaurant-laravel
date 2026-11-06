<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * İşletme Düzeni "Masa Planı" ile Salon Şeması'nı eşitler.
 * Plan alan başına çalışır (koordinat 0-1000); şema tüm katı gösterir. Bağ: şemadaki alan dikdörtgeni.
 * Şema karşılığı olan objeler (kasa, bar, giriş...) yalnızca şemada, bitki/duvar/ışık yalnızca planda tutulur.
 */
class SalonPlan
{
    const SEMA_TIPLERI = ['kasa', 'bar', 'giris', 'mutfak', 'wc', 'otopark', 'depo', 'diger'];
    const PLAN_TIPLERI = ['bitki', 'duvar', 'isik'];

    protected $subeId;

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
    }

    public static function ensure()
    {
        if (function_exists('_isletmePlanEnsure')) _isletmePlanEnsure();
        if (function_exists('_salonSemaTablo')) _salonSemaTablo();
    }

    protected function planOku()
    {
        return json_decode((string) DB::table('isletme_plan')->where('sube_id', $this->subeId)->value('veri'), true) ?: [];
    }

    protected function semaOku()
    {
        $v = json_decode((string) DB::table('salon_sema')->where('sube_id', $this->subeId)->value('veri'), true);
        return is_array($v) ? $v : [];
    }

    protected function semaYaz(array $veri)
    {
        $veri += ['katlar' => ['Zemin Kat'], 'parsel' => [], 'bolgeler' => [], 'noktalar' => [], 'masalar' => []];
        $veri['masalar'] = (object) $veri['masalar']; // boşken [] olursa Flutter Map beklerken bozulur
        DB::table('salon_sema')->updateOrInsert(['sube_id' => $this->subeId], ['veri' => json_encode($veri, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
    }

    protected function alanlar()
    {
        return DB::table('bolgeler')->where('sube_id', $this->subeId)->orderBy('sira')->orderBy('id')->get(['id', 'ad']);
    }

    protected function masalarAlanda()
    {
        $q = DB::table('masalar')->where('sube_id', $this->subeId);
        if (Schema::hasColumn('masalar', 'arsiv')) $q->where('arsiv', 0);
        return $q->get(['id', 'bolge_id'])->groupBy(fn ($m) => (int) $m->bolge_id);
    }

    protected function ic($r, $x, $y)
    {
        return $x >= $r['x'] && $x <= $r['x'] + $r['w'] && $y >= $r['y'] && $y <= $r['y'] + $r['h'];
    }

    /** Alanın şemadaki dikdörtgeni: kayıtlı/aynı adlı, yoksa masalarının çevresi, o da yoksa boş bir yer. */
    protected function dikdortgen(array &$sema, $bolge, $masaIdler, $olustur = false)
    {
        $sema['bolgeler'] = $sema['bolgeler'] ?? [];
        foreach ($sema['bolgeler'] as $i => $b) {
            if ((int) ($b['bolge_id'] ?? 0) === (int) $bolge->id) return $sema['bolgeler'][$i];
        }
        $adK = mb_strtolower(trim((string) $bolge->ad));
        foreach ($sema['bolgeler'] as $i => $b) {
            if (mb_strtolower(trim((string) ($b['ad'] ?? ''))) === $adK) {
                $sema['bolgeler'][$i]['bolge_id'] = (int) $bolge->id;
                return $sema['bolgeler'][$i];
            }
        }
        $xs = []; $ys = []; $kat = 0;
        foreach ($masaIdler as $mid) {
            $y = $sema['masalar'][$mid] ?? null;
            if (!is_array($y)) continue;
            $xs[] = (float) ($y['x'] ?? 0); $ys[] = (float) ($y['y'] ?? 0); $kat = (int) ($y['kat'] ?? 0);
        }
        if ($xs) {
            $x = max(0, min($xs) - 90); $yy = max(0, min($ys) - 90);
            $r = ['kat' => $kat, 'x' => $x, 'y' => $yy, 'w' => max(240, max($xs) - $x + 90), 'h' => max(160, max($ys) - $yy + 90)];
        } else {
            $r = null;
            for ($kat = 0; !$r && $kat < 12; $kat++) for ($sat = 0; $sat < 4 && !$r; $sat++) {
                $a = ['kat' => $kat, 'x' => 30, 'y' => 15 + $sat * 245, 'w' => 940, 'h' => 235];
                $cakisma = false;
                foreach ($sema['bolgeler'] as $b) {
                    if ((int) ($b['kat'] ?? 0) !== $kat) continue;
                    if ($a['x'] < $b['x'] + $b['w'] && $a['x'] + $a['w'] > $b['x'] && $a['y'] < $b['y'] + $b['h'] && $a['y'] + $a['h'] > $b['y']) { $cakisma = true; break; }
                }
                if (!$cakisma) $r = $a;
            }
            $r = $r ?: ['kat' => 0, 'x' => 30, 'y' => 15, 'w' => 940, 'h' => 235];
        }
        $r += ['ad' => $bolge->ad, 'bolge_id' => (int) $bolge->id];
        if ($olustur) {
            $sema['bolgeler'][] = $r;
            $sema['katlar'] = $sema['katlar'] ?? ['Zemin Kat'];
            while (count($sema['katlar']) <= (int) $r['kat']) $sema['katlar'][] = 'Kat ' . count($sema['katlar']);
        }
        return $r;
    }

    /** Sayfaların okuduğu plan: masa id => konum, `_alan` => alan başına oran + objeler. */
    public function oku()
    {
        self::ensure();
        $plan = $this->planOku();
        $sema = $this->semaOku();
        $alanMeta = (array) ($plan['_alan'] ?? []);
        $gruplar = $this->masalarAlanda();
        $cikti = ['_alan' => []];
        foreach ($plan as $k => $v) if (is_numeric($k)) $cikti[$k] = $v;
        foreach ($alanMeta as $bid => $a) if (!isset($cikti['_alan'][$bid])) $cikti['_alan'][$bid] = $a;

        foreach ($this->alanlar() as $b) {
            $ids = ($gruplar[(int) $b->id] ?? collect())->pluck('id')->map(fn ($x) => (int) $x)->all();
            $r = $this->dikdortgen($sema, $b, $ids);
            // Planda yeri olmayan masa şemadan yer alır (şemada elle dizilmiş salon korunur)
            foreach ($ids as $mid) {
                $varOlan = $plan[$mid] ?? null;
                if (is_array($varOlan) && (int) ($varOlan['b'] ?? 0) === (int) $b->id) continue;
                $y = $sema['masalar'][$mid] ?? null;
                if (!is_array($y)) continue;
                $cikti[$mid] = ['b' => (int) $b->id] + $this->semadanPlana($r, $y);
            }
            $objeler = [];
            foreach ((array) ($sema['noktalar'] ?? []) as $nk) {
                if ((int) ($nk['kat'] ?? 0) !== (int) ($r['kat'] ?? 0) || !$this->ic($r, (float) ($nk['x'] ?? 0), (float) ($nk['y'] ?? 0))) continue;
                $p = $this->semadanPlana($r, $nk);
                $objeler[] = ['t' => in_array($nk['tip'] ?? '', self::SEMA_TIPLERI, true) ? $nk['tip'] : 'diger', 'ad' => (string) ($nk['ad'] ?? ''),
                    'x' => $p['x'], 'y' => $p['y'], 'boy' => (float) ($nk['plan_boy'] ?? 1), 'rot' => (int) ($nk['rot'] ?? 0)];
            }
            $a = $alanMeta[$b->id] ?? [];
            $cikti['_alan'][$b->id] = ['oran' => (float) ($a['oran'] ?? 2.35), 'kayitli' => !empty($a['kayitli']),
                'dekor' => array_merge((array) ($a['dekor'] ?? []), $objeler)];
        }
        $cikti['_alan'] = (object) $cikti['_alan'];
        return $cikti;
    }

    protected function semadanPlana($r, $y)
    {
        return ['x' => (int) round(max(0, min(1000, ((float) ($y['x'] ?? 0) - $r['x']) / max(1, $r['w']) * 1000))),
            'y' => (int) round(max(0, min(1000, ((float) ($y['y'] ?? 0) - $r['y']) / max(1, $r['h']) * 1000)))];
    }

    /** $alanlar: bolge_id => [oran, konumlar{masa_id:{x,y,sekil,boy,rot}}, objeler[{t,ad,x,y,boy,rot}]] */
    public function kaydet(array $alanlar)
    {
        self::ensure();
        DB::transaction(function () use ($alanlar) {
            $plan = $this->planOku();
            $sema = $this->semaOku();
            $bolgeler = $this->alanlar()->keyBy('id');
            $gruplar = $this->masalarAlanda();
            $plan['_alan'] = (array) ($plan['_alan'] ?? []);
            $sema['masalar'] = (array) ($sema['masalar'] ?? []);
            $sema['noktalar'] = (array) ($sema['noktalar'] ?? []);

            foreach ($alanlar as $bid => $gelen) {
                $bid = (int) $bid;
                if (!is_array($gelen) || ($bid !== 0 && !isset($bolgeler[$bid]))) continue;
                $ids = ($gruplar[$bid] ?? collect())->pluck('id')->map(fn ($x) => (int) $x)->all();
                $r = $bid ? $this->dikdortgen($sema, $bolgeler[$bid], $ids, true) : null;

                foreach ((array) ($gelen['konumlar'] ?? []) as $mid => $k) {
                    $mid = (int) $mid;
                    if (!in_array($mid, $ids, true) || !is_array($k)) continue;
                    $x = max(0, min(1000, (int) ($k['x'] ?? 0))); $y = max(0, min(1000, (int) ($k['y'] ?? 0)));
                    $sat = ['b' => $bid, 'x' => $x, 'y' => $y];
                    if (in_array($k['sekil'] ?? null, ['kare', 'yuvarlak', 'uzun'], true)) $sat['sekil'] = $k['sekil'];
                    if (isset($k['boy'])) $sat['boy'] = max(.5, min(2, (float) $k['boy']));
                    if (isset($k['rot'])) $sat['rot'] = ((int) $k['rot'] % 360 + 360) % 360;
                    $plan[$mid] = $sat;
                    $upd = [];
                    if ($r) {
                        $sx = $r['x'] + $x / 1000 * $r['w']; $sy = $r['y'] + $y / 1000 * $r['h'];
                        $sema['masalar'][$mid] = ['kat' => (int) ($r['kat'] ?? 0), 'x' => round($sx, 1), 'y' => round($sy, 1), 'boy' => (float) (($sema['masalar'][$mid]['boy'] ?? 0) ?: 110)] + (array) ($sema['masalar'][$mid] ?? []);
                        $upd = ['x' => (int) $sx, 'y' => (int) $sy]; // POS/ısı haritası şema ile aynı kalsın
                    }
                    if (in_array($sat['sekil'] ?? null, ['kare', 'yuvarlak'], true)) $upd['sekil'] = $sat['sekil'];
                    if ($upd) DB::table('masalar')->where('id', $mid)->where('sube_id', $this->subeId)->update($upd);
                }

                $dekor = [];
                $yeniNokta = [];
                foreach ((array) ($gelen['objeler'] ?? []) as $o) {
                    if (!is_array($o)) continue;
                    $t = (string) ($o['t'] ?? '');
                    $x = max(0, min(1000, (int) ($o['x'] ?? 0))); $y = max(0, min(1000, (int) ($o['y'] ?? 0)));
                    $boy = max(.3, min(3, (float) ($o['boy'] ?? 1))); $rot = ((int) ($o['rot'] ?? 0) % 360 + 360) % 360;
                    $ad = mb_substr(trim((string) ($o['ad'] ?? '')), 0, 40);
                    if (in_array($t, self::PLAN_TIPLERI, true)) {
                        $dekor[] = ['t' => $t, 'x' => $x, 'y' => $y, 'boy' => $boy, 'rot' => $rot];
                    } elseif (in_array($t, self::SEMA_TIPLERI, true)) {
                        if ($r) $yeniNokta[] = ['kat' => (int) ($r['kat'] ?? 0), 'tip' => $t, 'ad' => $ad, 'x' => round($r['x'] + $x / 1000 * $r['w'], 1), 'y' => round($r['y'] + $y / 1000 * $r['h'], 1), 'plan_boy' => $boy, 'rot' => $rot];
                        else $dekor[] = ['t' => $t, 'ad' => $ad, 'x' => $x, 'y' => $y, 'boy' => $boy, 'rot' => $rot];
                    }
                }
                if ($r) {
                    // Bu alandaki noktalar planda gösterilip geri gönderildi: yenileriyle değişir
                    $sema['noktalar'] = array_values(array_filter($sema['noktalar'], fn ($nk) => (int) ($nk['kat'] ?? 0) !== (int) ($r['kat'] ?? 0) || !$this->ic($r, (float) ($nk['x'] ?? 0), (float) ($nk['y'] ?? 0))));
                    $sema['noktalar'] = array_merge($sema['noktalar'], $yeniNokta);
                }
                $plan['_alan'][$bid] = ['oran' => max(1, min(3, (float) ($gelen['oran'] ?? 2.35))), 'kayitli' => true, 'dekor' => $dekor];
            }
            DB::table('isletme_plan')->updateOrInsert(['sube_id' => $this->subeId], ['veri' => json_encode($plan, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
            $this->semaYaz($sema);
        });
    }

    /** Şema (web/Flutter) kaydedilince değişen alanın masa konumları plana da geçer. */
    public function semadanSonra($eski, $yeni)
    {
        self::ensure();
        $eski = is_array($eski) ? $eski : []; $yeni = is_array($yeni) ? $yeni : [];
        $plan = $this->planOku();
        $gruplar = $this->masalarAlanda();
        $degisti = false;
        foreach ($this->alanlar() as $b) {
            $ids = ($gruplar[(int) $b->id] ?? collect())->pluck('id')->map(fn ($x) => (int) $x)->all();
            $e = $eski; $y = $yeni;
            $rEski = $this->dikdortgen($e, $b, $ids);
            $rYeni = $this->dikdortgen($y, $b, $ids);
            $alanDegisti = array_intersect_key($rEski, ['x' => 1, 'y' => 1, 'w' => 1, 'h' => 1]) != array_intersect_key($rYeni, ['x' => 1, 'y' => 1, 'w' => 1, 'h' => 1]);
            if (!$alanDegisti) foreach ($ids as $mid) {
                if (($eski['masalar'][$mid]['x'] ?? null) != ($yeni['masalar'][$mid]['x'] ?? null) || ($eski['masalar'][$mid]['y'] ?? null) != ($yeni['masalar'][$mid]['y'] ?? null)) { $alanDegisti = true; break; }
            }
            if (!$alanDegisti) continue;
            foreach ($ids as $mid) {
                if (!is_array($plan[$mid] ?? null) || !is_array($yeni['masalar'][$mid] ?? null)) continue;
                $plan[$mid] = $this->semadanPlana($rYeni, $yeni['masalar'][$mid]) + $plan[$mid];
                $degisti = true;
            }
        }
        if ($degisti) DB::table('isletme_plan')->updateOrInsert(['sube_id' => $this->subeId], ['veri' => json_encode($plan, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
    }

    /** Bölge yeniden adlandırılınca şemadaki etiketi de değişir. */
    public function alanAdiDegisti($bolgeId, $eskiAd, $yeniAd)
    {
        self::ensure();
        $sema = $this->semaOku();
        if (empty($sema['bolgeler'])) return;
        $eskiK = mb_strtolower(trim((string) $eskiAd));
        foreach ($sema['bolgeler'] as $i => $b) {
            if ((int) ($b['bolge_id'] ?? 0) === (int) $bolgeId || (empty($b['bolge_id']) && mb_strtolower(trim((string) ($b['ad'] ?? ''))) === $eskiK)) {
                $sema['bolgeler'][$i]['ad'] = $yeniAd;
                $sema['bolgeler'][$i]['bolge_id'] = (int) $bolgeId;
                $this->semaYaz($sema);
                return;
            }
        }
    }
}
