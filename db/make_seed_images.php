<?php
// Generates seed demo images. Idempotent — skips existing files.
// Called by docker-entrypoint.sh on container start.

define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('PUB_IMG_DIR', __DIR__ . '/../public/assets/img/');
define('ADM_IMG_DIR', __DIR__ . '/../admin/assets/img/');

function makeJpeg(string $path, string $label, int $r, int $g, int $b): void
{
    if (file_exists($path)) return;
    $img   = imagecreatetruecolor(400, 300);
    $bg    = imagecolorallocate($img, $r, $g, $b);
    $dark  = imagecolorallocate($img, max(0, $r - 40), max(0, $g - 40), max(0, $b - 40));
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bg);
    imagefilledrectangle($img, 15, 15, 385, 285, $dark);
    $lw = strlen($label) * imagefontwidth(5);
    imagestring($img, 5, (int)((400 - $lw) / 2), 132, $label, $white);
    imagejpeg($img, $path, 90);
    imagedestroy($img);
    echo "Created: $path\n";
}

function makePng(string $path, string $text): void
{
    if (file_exists($path)) return;
    $img   = imagecreatetruecolor(300, 80);
    $bg    = imagecolorallocate($img, 30, 80, 170);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bg);
    $lw = strlen($text) * imagefontwidth(5);
    imagestring($img, 5, (int)((300 - $lw) / 2), 32, $text, $white);
    imagepng($img, $path);
    imagedestroy($img);
    echo "Created: $path\n";
}

if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);

$images = [
    // Scuole — blue
    ['seed_scuola1.jpg',   'SCUOLA 1',   52, 120, 180],
    ['seed_scuola2.jpg',   'SCUOLA 2',   52, 120, 180],
    ['seed_scuola3.jpg',   'SCUOLA 3',   52, 120, 180],
    ['seed_scuola4.jpg',   'SCUOLA 4',   52, 120, 180],
    ['seed_scuola5.jpg',   'SCUOLA 5',   52, 120, 180],
    // Eventi — green
    ['seed_evento1.jpg',   'EVENTO 1',   40, 150,  90],
    ['seed_evento2.jpg',   'EVENTO 2',   40, 150,  90],
    ['seed_evento3.jpg',   'EVENTO 3',   40, 150,  90],
    ['seed_evento4.jpg',   'EVENTO 4',   40, 150,  90],
    ['seed_evento5.jpg',   'EVENTO 5',   40, 150,  90],
    // Progetti — orange
    ['seed_progetto1.jpg', 'PROGETTO 1', 200, 100,  30],
    ['seed_progetto2.jpg', 'PROGETTO 2', 200, 100,  30],
    ['seed_progetto3.jpg', 'PROGETTO 3', 200, 100,  30],
    // Links — purple
    ['seed_link1.jpg',     'LINK 1',     130,  60, 180],
    ['seed_link2.jpg',     'LINK 2',     130,  60, 180],
];

foreach ($images as [$file, $label, $r, $g, $b]) {
    makeJpeg(UPLOADS_DIR . $file, $label, $r, $g, $b);
}

makePng(PUB_IMG_DIR . 'logo.png', 'SVELATI');
makePng(ADM_IMG_DIR . 'logo.png', 'SVELATI');

echo "Done.\n";
