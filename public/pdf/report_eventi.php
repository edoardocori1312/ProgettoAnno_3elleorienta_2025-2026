<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db();

// Fetch all visible events with school and study-program data
// GROUP_CONCAT aggregates study programs per school (mirrors MongoDB school.study_address[])
$rows = $conn->query(
    'SELECT e.ID_evento, e.titolo, e.ora_inizio, e.ora_fine,
            s.COD_meccanografico AS cod_scuola, s.nome AS nome_scuola,
            s.via, s.n_civico, c.nome AS nome_citta,
            GROUP_CONCAT(DISTINCT ist.nome ORDER BY si.n_ordine SEPARATOR \'§\') AS indirizzi
     FROM   Eventi e
     LEFT JOIN Scuole           s   ON e.cod_scuola     = s.COD_meccanografico
     LEFT JOIN Citta             c   ON s.id_citta       = c.ID_citta
     LEFT JOIN Scuole_Indirizzi si  ON si.cod_scuola    = s.COD_meccanografico
     LEFT JOIN Indirizzi_studio ist  ON ist.ID_indirizzo = si.id_indirizzo
     WHERE  e.visibile = 1 AND e.data_eliminazione IS NULL
     GROUP  BY e.ID_evento
     ORDER  BY s.nome ASC, e.ora_inizio ASC'
)->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Group events by school (mirrors school.js: schoolEvents.map(school => {...}))
$scuole = [];
foreach ($rows as $row) {
    $cod = $row['cod_scuola'] ?? '__territoriale__';
    if (!isset($scuole[$cod])) {
        $scuole[$cod] = [
            'nome'      => $row['nome_scuola'] ?? '',
            'indirizzi' => $row['indirizzi'] ? explode('§', $row['indirizzi']) : [],
            'eventi'    => [],
        ];
    }
    $scuole[$cod]['eventi'][] = [
        'titolo'    => $row['titolo'],
        'data'      => $row['ora_inizio'] ? date('d/m/Y', strtotime($row['ora_inizio'])) : '',
        'ora_inizio'=> $row['ora_inizio'] ? date('H:i', strtotime($row['ora_inizio'])) : '',
        'ora_fine'  => $row['ora_fine']   ? date('H:i', strtotime($row['ora_fine']))   : '',
    ];
}

// Anno scolastico logic (mirrors school.js line 102: today > 9 ? YYYY/YY+1 : YYYY-1/YY)
$mese = (int) date('n');
if ($mese > 9) {
    $annoScol = date('Y') . '/' . date('y', strtotime('+1 year'));
} else {
    $annoScol = date('Y', strtotime('-1 year')) . '/' . date('y');
}

$oggi     = date('d/m/Y');
$filename = date('D') . '_' . date('d-m-Y') . '_Eventi.pdf';

// ── Build HTML (mirrors school.js lines 100–188 exactly) ─────────────────────

$html  = '<div style="color:#153483; text-align:center; font-size:20pt; position:relative; min-height:50px; padding-top:5px;">';
$html .= '<span style="position:absolute; top:0; left:20px; font-size:12pt;">'
       . 'Piattaforma orientamento territoriale <a href="www.3elleorienta.it">www.3elleorienta.it</a>'
       . '</span>';
$html .= '<span style="position:absolute; top:0; right:20px; font-size:12pt;">'
       . 'anno scolastico ' . htmlspecialchars($annoScol)
       . '</span><br/>';
$html .= '<p style="margin-top:22px;"><strong>Incontri di orientamento presso gli istituti superiori</strong></p>';
$html .= '<p>Attenzione gli eventi possono variare! Documento scaricato il ' . $oggi . '</p>';
$html .= '</div>';

$html .= '<table border="0" style="width:100%; font-size:10pt; border-collapse:collapse; page-break-inside:auto;">';
$html .= '<colgroup>';
$html .= '<col style="width:32%">';
$html .= '<col style="width:8%">';
$html .= '<col style="width:9%">';
$html .= '<col style="width:51%">';
$html .= '</colgroup>';
$html .= '<thead><tr>';
$html .= '<th style="background-color:#E9282D; color:white; padding:5px;">Scuola</th>';
$html .= '<th style="background-color:#EF6A53; color:white; padding:5px;">Data evento</th>';
$html .= '<th style="background-color:#F39980; color:white; padding:5px;">Ora evento</th>';
$html .= '<th style="background-color:#F8B5A0; color:white; padding:5px;">Tipo di incontro</th>';
$html .= '</tr></thead>';
$html .= '<tbody>';

foreach ($scuole as $scuola) {
    $html .= '<tr style="border-bottom:thin dashed; border-color:red;">';

    // Scuola — bg #153483 (mirrors school.js lines 131–139)
    $html .= '<td style="background-color:#153483; color:white; vertical-align:top;'
           . ' font-size:10pt; page-break-inside:avoid; page-break-after:auto; padding:5px 10px 5px 5px;">';
    $html .= '<div>';
    $html .= '<span>' . htmlspecialchars($scuola['nome']) . '</span><br/><br/>';
    foreach ($scuola['indirizzi'] as $ind) {
        $html .= '<div><span> -' . htmlspecialchars(trim($ind)) . '</span><br/></div>';
    }
    $html .= '</div>';
    $html .= '</td>';

    // Data evento — bg #4A5799 (mirrors school.js lines 141–162)
    $html .= '<td style="background-color:#4A5799; color:white; vertical-align:top;'
           . ' font-size:10pt; page-break-inside:avoid; page-break-after:auto; padding:5px 10px 5px 5px;">';
    $html .= '<div><span>';
    foreach ($scuola['eventi'] as $ev) {
        $html .= '<div><span>' . htmlspecialchars($ev['data']) . '</span><br/></div>';
    }
    $html .= '</span><br/></div>';
    $html .= '</td>';

    // Ora evento — bg #797EB3 (mirrors school.js lines 164–172)
    $html .= '<td style="background-color:#797EB3; color:white; vertical-align:top;'
           . ' font-size:10pt; page-break-inside:avoid; page-break-after:auto; padding:5px 10px 5px 5px;">';
    $html .= '<div><span>';
    foreach ($scuola['eventi'] as $ev) {
        $html .= '<div><span>' . htmlspecialchars($ev['ora_inizio'] . ' - ' . $ev['ora_fine']) . '</span><br/></div>';
    }
    $html .= '</span><br/></div>';
    $html .= '</td>';

    // Tipo di incontro — bg #8F92C0 (mirrors school.js lines 173–182)
    $html .= '<td style="background-color:#8F92C0; color:white; vertical-align:top;'
           . ' font-size:10pt; page-break-inside:avoid; page-break-after:auto; padding:5px 10px 5px 5px;">';
    $html .= '<div><span>';
    foreach ($scuola['eventi'] as $ev) {
        $html .= '<div><span>' . htmlspecialchars($ev['titolo']) . '</span><br/></div>';
    }
    $html .= '</span><br/></div>';
    $html .= '</td>';

    $html .= '</tr>';
}

$html .= '</tbody></table>';

// ── Render PDF via Dompdf (mirrors html-pdf A3 landscape options) ─────────────

$options = new Options();
$options->set('isRemoteEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A3', 'landscape');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo $dompdf->output();
