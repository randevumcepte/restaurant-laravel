<?php

/*
| QR Menu renk paletleri (kartela). Her restoran (sube) kendi temasini secer.
| subeler.tema kolonunda anahtar tutulur; masa_menu bu paleti CSS degiskenlerine basar.
| ana/ana2/ana3 = birincil aksan gradient rengi; ink = aksan uzerindeki yazi rengi;
| glow = arka plan isima tonu. Altin (--gold) tum paletlerde sabit luks tamamlayicidir.
*/

return [

    'altin' => [
        'ad' => 'Altın & Siyah', 'emoji' => '👑',
        'ana' => '#F6DFA0', 'ana2' => '#E9C46A', 'ana3' => '#C9962F',
        'ink' => '#3a2600', 'glow' => 'rgba(233,196,106,.16)',
    ],
    'mor' => [
        'ad' => 'Mor & Altın', 'emoji' => '🔮',
        'ana' => '#8B3BEA', 'ana2' => '#A855F7', 'ana3' => '#6D28D9',
        'ink' => '#ffffff', 'glow' => 'rgba(139,59,234,.14)',
    ],
    'bordo' => [
        'ad' => 'Şarap Kırmızısı', 'emoji' => '🍷',
        'ana' => '#C41E3A', 'ana2' => '#E23E58', 'ana3' => '#8E1428',
        'ink' => '#ffffff', 'glow' => 'rgba(196,30,58,.15)',
    ],
    'zumrut' => [
        'ad' => 'Zümrüt Yeşili', 'emoji' => '🌿',
        'ana' => '#1E8A5F', 'ana2' => '#27A874', 'ana3' => '#14603F',
        'ink' => '#ffffff', 'glow' => 'rgba(30,138,95,.15)',
    ],
    'lacivert' => [
        'ad' => 'Royal Mavi', 'emoji' => '💎',
        'ana' => '#2E5BD6', 'ana2' => '#4B7BF0', 'ana3' => '#1E3EA0',
        'ink' => '#ffffff', 'glow' => 'rgba(46,91,214,.15)',
    ],
    'bakir' => [
        'ad' => 'Bakır & Siyah', 'emoji' => '🔥',
        'ana' => '#C2571E', 'ana2' => '#E0762F', 'ana3' => '#8E3D12',
        'ink' => '#ffffff', 'glow' => 'rgba(194,87,30,.15)',
    ],

];
