<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// includi connessione (usa lo stesso file presente nella cartella)
include_once(__DIR__ . '/../connessione.php');

$results = [];

if(isset($conn) && $conn instanceof mysqli){
    $sql = "SELECT id, nome AS title, descrizione AS description, immagine AS image, lat, lng FROM luoghi LIMIT 3";
    if($res = $conn->query($sql)){
        while($row = $res->fetch_assoc()){
            $results[] = [
                'id' => $row['id'],
                'title' => $row['title'] ?? '',
                'description' => $row['description'] ?? '',
                'image' => $row['image'] ?? 'placeholder-card.svg',
                'lat' => isset($row['lat']) ? floatval($row['lat']) : null,
                'lng' => isset($row['lng']) ? floatval($row['lng']) : null
            ];
        }
        $res->free();
    }
}

// se non abbiamo risultati, ritorniamo dei dati di esempio
if(count($results) === 0){
    $results = [
        ['id'=>1,'title'=>'Milano','description'=>'Descrizione per Milano','image'=>'placeholder-card.svg','lat'=>45.4642,'lng'=>9.19],
        ['id'=>2,'title'=>'Roma','description'=>'Descrizione per Roma','image'=>'placeholder-card.svg','lat'=>41.9028,'lng'=>12.4964],
        ['id'=>3,'title'=>'Torino','description'=>'Descrizione per Torino','image'=>'placeholder-card.svg','lat'=>45.0703,'lng'=>7.6869]
    ];
}

echo json_encode(array_values($results), JSON_UNESCAPED_UNICODE);

?>