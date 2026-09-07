<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$publicDir = realpath(__DIR__.'/../public') ?: (__DIR__.'/../public');
$assetDir = $publicDir.'/demo-products';

if (! is_dir($assetDir)) {
    mkdir($assetDir, 0755, true);
}

function demoProductAsset(string $slug, string $title, string $subtitle, string $color = '#4f46e5', string $accent = '#111827'): string
{
    global $assetDir;

    $path = "{$assetDir}/{$slug}.svg";
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeSubtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
    $safeColor = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
    $safeAccent = htmlspecialchars($accent, ENT_QUOTES, 'UTF-8');

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900">
  <defs>
    <linearGradient id="bg" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0%" stop-color="#f8fafc"/>
      <stop offset="100%" stop-color="#eef2ff"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="28" stdDeviation="24" flood-color="#0f172a" flood-opacity=".18"/>
    </filter>
  </defs>
  <rect width="900" height="900" fill="url(#bg)"/>
  <circle cx="700" cy="180" r="120" fill="{$safeColor}" opacity=".16"/>
  <circle cx="180" cy="720" r="150" fill="{$safeAccent}" opacity=".08"/>
  <rect x="180" y="150" width="540" height="540" rx="80" fill="#fff" filter="url(#shadow)"/>
  <rect x="250" y="230" width="400" height="300" rx="56" fill="{$safeColor}" opacity=".92"/>
  <circle cx="342" cy="330" r="58" fill="#fff" opacity=".82"/>
  <rect x="420" y="292" width="158" height="36" rx="18" fill="#fff" opacity=".82"/>
  <rect x="420" y="350" width="112" height="28" rx="14" fill="#fff" opacity=".5"/>
  <path d="M280 522c58-88 130-112 216-72 48 22 88 16 124-18v98H280z" fill="{$safeAccent}" opacity=".32"/>
  <text x="450" y="645" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="44" font-weight="800" fill="#0f172a">{$safeTitle}</text>
  <text x="450" y="702" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="24" font-weight="700" fill="#4f46e5">{$safeSubtitle}</text>
  <text x="450" y="790" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="20" font-weight="700" fill="#64748b">AliShop Demo</text>
</svg>
SVG;

    file_put_contents($path, $svg);

    return "/demo-products/{$slug}.svg";
}

function demoRealAsset(string $filename): string
{
    return "/demo-products-real/{$filename}";
}

function category(string $name, string $description): Category
{
    return Category::updateOrCreate(
        ['name' => $name],
        ['description' => $description],
    );
}

$categories = [
    'Mode' => category('Mode', 'Vetements, chaussures et accessoires.'),
    'Boisson' => category('Boisson', 'Boissons et packs familiaux.'),
    'Epicerie' => category('Epicerie', 'Produits alimentaires et formats economiques.'),
    'Electronique' => category('Electronique', 'Accessoires tech et produits connectes.'),
    'Maison' => category('Maison', 'Cuisine, rangement et maison.'),
    'Beaute' => category('Beaute', 'Parfums, soins et cadeaux.'),
];

$products = [
    [
        'name' => 'T-shirt coton premium',
        'category' => 'Mode',
        'price' => 129,
        'sale_price' => 99,
        'stock' => 38,
        'free_delivery' => false,
        'short_description' => 'T-shirt doux, coupe moderne, plusieurs couleurs et tailles.',
        'long_description' => "T-shirt polyvalent pour homme ou femme. Ideal pour tester les variantes couleur + taille.\nMatiere: coton doux.\nConseil: ajouter une photo differente pour chaque couleur.",
        'variant_options' => [
            'color' => ['Noir', 'Blanc', 'Bleu'],
            'size' => ['S', 'M', 'L', 'XL', 'XXL'],
        ],
        'variant_prices' => [
            'size' => [
                'S' => 99,
                'M' => 99,
                'L' => 99,
                'XL' => 109,
                'XXL' => 119,
            ],
        ],
        'variant_media' => [
            'color' => [
                'Noir' => demoRealAsset('tshirt-noir.png'),
                'Blanc' => demoRealAsset('tshirt-blanc.png'),
                'Bleu' => demoRealAsset('tshirt-bleu.png'),
            ],
        ],
        'image' => demoRealAsset('tshirt-noir.png'),
    ],
    [
        'name' => 'Casquette sport urbaine',
        'category' => 'Mode',
        'price' => 89,
        'sale_price' => null,
        'stock' => 24,
        'free_delivery' => false,
        'short_description' => 'Casquette reglable avec photos par couleur.',
        'long_description' => "Casquette pour style casual, sport et livraison quotidienne.\nCe produit teste couleurs avec photos + taille ajustable.",
        'variant_options' => [
            'color' => ['Noir', 'Beige', 'Navy'],
            'size' => ['Ajustable'],
        ],
        'variant_prices' => [
            'color' => [
                'Noir' => 89,
                'Beige' => 95,
                'Navy' => 95,
            ],
        ],
        'variant_media' => [
            'color' => [
                'Noir' => demoRealAsset('casquette-noir.png'),
                'Beige' => demoRealAsset('casquette-beige.png'),
                'Navy' => demoRealAsset('casquette-navy.png'),
            ],
        ],
        'image' => demoRealAsset('casquette-noir.png'),
    ],
    [
        'name' => 'Farine ble tendre',
        'category' => 'Epicerie',
        'price' => 14.5,
        'sale_price' => null,
        'stock' => 75,
        'free_delivery' => false,
        'short_description' => 'Farine alimentaire disponible en plusieurs poids.',
        'long_description' => "Farine pour pain, gateaux et cuisine quotidienne.\nProduit sans couleur, utile pour tester les variantes poids seulement.",
        'variant_options' => [
            'weight' => ['1kg', '2kg', '5kg', '10kg'],
        ],
        'variant_prices' => [
            'weight' => [
                '1kg' => 14.5,
                '2kg' => 27,
                '5kg' => 62,
                '10kg' => 115,
            ],
        ],
        'image' => demoRealAsset('farine.png'),
    ],
    [
        'name' => 'Coca-Cola pack familial',
        'category' => 'Boisson',
        'price' => 8.5,
        'sale_price' => null,
        'stock' => 120,
        'free_delivery' => false,
        'short_description' => 'Boisson disponible par volume et pack.',
        'long_description' => "Produit boisson pour tester volume + pack.\nLe client peut choisir bouteille 500ml, 1L ou 2L, puis pack individuel ou pack x6.",
        'variant_options' => [
            'weight' => ['500ml', '1L', '2L'],
            'custom' => ['Unite', 'Pack x6', 'Pack x12'],
        ],
        'variant_prices' => [
            '_combinations' => [
                'custom=Unite|weight=500ml' => 8.5,
                'custom=Unite|weight=1L' => 12,
                'custom=Unite|weight=2L' => 16,
                'custom=Pack x6|weight=500ml' => 49,
                'custom=Pack x6|weight=1L' => 69,
                'custom=Pack x6|weight=2L' => 89,
                'custom=Pack x12|weight=500ml' => 95,
                'custom=Pack x12|weight=1L' => 132,
                'custom=Pack x12|weight=2L' => 168,
            ],
        ],
        'image' => demoRealAsset('cola-pack.png'),
    ],
    [
        'name' => 'Ecouteurs Bluetooth TWS',
        'category' => 'Electronique',
        'price' => 179,
        'sale_price' => 149,
        'stock' => 30,
        'free_delivery' => true,
        'short_description' => 'Ecouteurs sans fil avec choix couleur et version.',
        'long_description' => "Ecouteurs compacts pour appels, musique et transport.\nTest couleur + option standard/pro + livraison gratuite produit.",
        'variant_options' => [
            'color' => ['Noir', 'Blanc'],
            'custom' => ['Standard', 'Pro'],
        ],
        'variant_prices' => [
            'custom' => [
                'Standard' => 149,
                'Pro' => 229,
            ],
        ],
        'variant_media' => [
            'color' => [
                'Noir' => demoRealAsset('ecouteurs-noir.png'),
                'Blanc' => demoRealAsset('ecouteurs-blanc.png'),
            ],
        ],
        'image' => demoRealAsset('ecouteurs-noir.png'),
    ],
    [
        'name' => 'Power bank rapide',
        'category' => 'Electronique',
        'price' => 249,
        'sale_price' => null,
        'stock' => 18,
        'free_delivery' => true,
        'short_description' => 'Batterie externe avec choix capacite et couleur.',
        'long_description' => "Power bank USB-C pour telephone, voyage et travail.\nTest couleur + capacite comme 10000mAh ou 20000mAh.",
        'variant_options' => [
            'color' => ['Noir', 'Blanc'],
            'custom' => ['10000mAh', '20000mAh'],
        ],
        'variant_prices' => [
            'custom' => [
                '10000mAh' => 249,
                '20000mAh' => 349,
            ],
        ],
        'variant_media' => [
            'color' => [
                'Noir' => demoRealAsset('powerbank-noir.png'),
                'Blanc' => demoRealAsset('powerbank-blanc.png'),
            ],
        ],
        'image' => demoRealAsset('powerbank-noir.png'),
    ],
    [
        'name' => 'Air fryer compacte',
        'category' => 'Maison',
        'price' => 599,
        'sale_price' => 499,
        'stock' => 9,
        'free_delivery' => false,
        'short_description' => 'Friteuse sans huile disponible en plusieurs capacites.',
        'long_description' => "Produit maison/cuisine avec panier moyen plus eleve.\nStock faible pour tester l alerte < 10.\nOptions: couleur + capacite.",
        'variant_options' => [
            'color' => ['Noir', 'Gris'],
            'custom' => ['3L', '4.5L', '6L'],
        ],
        'variant_prices' => [
            'custom' => [
                '3L' => 499,
                '4.5L' => 649,
                '6L' => 849,
            ],
        ],
        'variant_media' => [
            'color' => [
                'Noir' => demoRealAsset('airfryer-noir.png'),
                'Gris' => demoRealAsset('airfryer-gris.png'),
            ],
        ],
        'image' => demoRealAsset('airfryer-noir.png'),
    ],
    [
        'name' => 'Sneakers urbaines',
        'category' => 'Mode',
        'price' => 349,
        'sale_price' => null,
        'stock' => 21,
        'free_delivery' => false,
        'short_description' => 'Chaussures avec couleur et pointure.',
        'long_description' => "Sneakers legeres pour quotidien.\nProduit parfait pour tester beaucoup de tailles et plusieurs photos couleur.",
        'variant_options' => [
            'color' => ['Noir', 'Blanc', 'Rouge'],
            'size' => ['39', '40', '41', '42', '43', '44', '45'],
        ],
        'variant_prices' => [
            'size' => [
                '39' => 349,
                '40' => 349,
                '41' => 349,
                '42' => 349,
                '43' => 349,
                '44' => 369,
                '45' => 369,
            ],
        ],
        'variant_media' => [
            'color' => [
                'Noir' => demoRealAsset('sneakers-noir.png'),
                'Blanc' => demoRealAsset('sneakers-blanc.png'),
                'Rouge' => demoRealAsset('sneakers-rouge.png'),
            ],
        ],
        'image' => demoRealAsset('sneakers-noir.png'),
    ],
    [
        'name' => 'Parfum Oud Signature',
        'category' => 'Beaute',
        'price' => 199,
        'sale_price' => 169,
        'stock' => 16,
        'free_delivery' => false,
        'short_description' => 'Parfum avec contenance et style.',
        'long_description' => "Parfum cadeau avec bonne marge.\nProduit sans couleur, mais avec contenance et option homme/femme.",
        'variant_options' => [
            'weight' => ['50ml', '100ml'],
            'custom' => ['Homme', 'Femme', 'Mixte'],
        ],
        'variant_prices' => [
            'weight' => [
                '50ml' => 169,
                '100ml' => 299,
            ],
        ],
        'image' => demoRealAsset('parfum-oud.png'),
    ],
    [
        'name' => 'Sac a dos laptop',
        'category' => 'Mode',
        'price' => 229,
        'sale_price' => null,
        'stock' => 27,
        'free_delivery' => false,
        'short_description' => 'Sac ordinateur avec couleur et taille laptop.',
        'long_description' => "Sac pratique pour etudiants, travail et voyage.\nTeste couleur avec photo + taille 15.6 ou 17 pouces.",
        'variant_options' => [
            'color' => ['Noir', 'Gris', 'Bleu'],
            'size' => ['15.6 pouces', '17 pouces'],
        ],
        'variant_prices' => [
            'size' => [
                '15.6 pouces' => 229,
                '17 pouces' => 259,
            ],
        ],
        'variant_media' => [
            'color' => [
                'Noir' => demoRealAsset('sac-noir.png'),
                'Gris' => demoRealAsset('sac-gris.png'),
                'Bleu' => demoRealAsset('sac-bleu.png'),
            ],
        ],
        'image' => demoRealAsset('sac-noir.png'),
    ],
];

foreach ($products as $data) {
    $category = $categories[$data['category']];
    $variantOptions = $data['variant_options'] ?? null;
    $variantMedia = $data['variant_media'] ?? null;
    $variantPrices = $data['variant_prices'] ?? null;

    Product::updateOrCreate(
        ['name' => $data['name']],
        [
            'description' => $data['short_description'],
            'short_description' => $data['short_description'],
            'long_description' => $data['long_description'],
            'price' => $data['price'],
            'sale_price' => $data['sale_price'],
            'sale_ends_at' => $data['sale_price'] ? now()->addMonth() : null,
            'stock' => $data['stock'],
            'free_delivery' => $data['free_delivery'],
            'has_variants' => ! empty($variantOptions),
            'variant_options' => $variantOptions,
            'variant_media' => $variantMedia,
            'variant_prices' => $variantPrices,
            'image' => $data['image'],
            'category_id' => $category->id,
        ],
    );

    echo "Produit demo pret: {$data['name']}\n";
}

echo "10 produits demo AliShop sont prets.\n";
