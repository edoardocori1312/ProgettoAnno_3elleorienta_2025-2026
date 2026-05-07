<?php 
    class risultato { //classe risultato ritornata in output
        public ?string $errore = null;
        public $result = null;

        public function isSuccess(): bool //per semplicità dal lato frontend si può chiamare questa funzione per verificare se l'operazione è andata a buon fine.
        {
            return $this->errore === null; //ritorna se errore è settato
        }
    }

    $file_connessione = "../config/db.php";
    @include($file_connessione); //includo il file, warning soppressi (error handling nella funzione)

    //controlla il risultato, ritorna true se il risultato è valido
    function controlloRisultato($res){
        if($res) //se il risultato è settato
        {
            if($res instanceof mysqli_result) //controllo se il tipo di oggetto corrisponde
            {
                return true; 
            } 
        }
        return false; 
    }


    function bindParams($stmt, $types, array $values) {
        if ($types != "") {
            // L'operatore ... spacchetta l'array in argomenti singoli
            $stmt->bind_param($types, ...$values);
        }
    }
    
    //filtri: generale, titolo, target, range date
    function filtriVisualizza($statement, $conn, $idUtente, $tipoRicerca, $titolo, $desc, $target, $data_inizio, $data_fine, $citta){
        

        $query = "SELECT e.* FROM eventi e";
        $utente = null;
        $params ="";
        $conditions = [];
        $values = [];


        // Acquisisco utente
        $utente = GetScuolaRuoloUtente($idUtente, $conn);

        
        // JOIN città
        if($citta != null){
            if($tipoRicerca == true){
                $query .= " INNER JOIN citta c ON c.ID_citta = e.id_citta";
            } else {
                $query .= " LEFT JOIN citta c ON c.ID_citta = e.id_citta";
            }
        }

        // controllo tipo utente
        if($utente->tipo == "ADMIN"){
            // nessun filtro
        }
        else if($utente->tipo == "SCOLASTICO"){
            $conditions[] = "e.cod_scuola = ?";
            $params .= "s";
            $values[] = $utente->cod_scuola;
        }
        else{
            return false;
        }

        // filtri
        if($titolo != null){
            $conditions[] = "e.titolo LIKE ?";
            $params .= "s";
            $values[] = "%".$titolo."%";
        }

        if($desc != null){
            $conditions[] = "e.descrizione LIKE ?";
            $params .= "s";
            $values[] = "%".$desc."%";
        }

        if($target != null){
            $conditions[] = "e.target = ?";
            $params .= "s";
            $values[] = $target;
        }

        if($data_inizio != null){
            $conditions[] = "e.data_inizio >= ?";
            $params .= "s";
            $values[] = $data_inizio;
        }

        if($data_fine != null){
            $conditions[] = "e.data_fine <= ?";
            $params .= "s";
            $values[] = $data_fine;
        }

        if($citta != null){
            $conditions[] = "c.nome LIKE ?";
            $params .= "s";
            $values[] = "%".$citta."%";
        }

        // costruzione query finale
        if(count($conditions) > 0){
            if($tipoRicerca){
                $query .= " WHERE " . implode(" AND ", $conditions);
            } else {
                $query .= " WHERE " . implode(" OR ", $conditions);
            }
        }



        if(!$statement=$conn->prepare($query)){ //controllo creazione statement
            return false;
        }


        bindParams($statement, $params, $values);
        return $statement;
    }


    function visualizzaEventi($idUtente, $tipoRicerca, $titolo, $desc, $target, $data_inizio, $data_fine, $citta) {
        
        $risultato = new risultato(); //creo oggetto risultato
        
        $sql = "SELECT e.* FROM eventi e"; //query da eseguire
        $res = null;

        try{ //try per evitare che il programma crepi

            global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
            $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
            $stmt = null;
            //modifica query e bind param dinamico in base ai filtri
            $filtri = filtriVisualizza($stmt, $conn, $idUtente, $tipoRicerca, $titolo, $desc, $target, $data_inizio, $data_fine, $citta);
            if(!$filtri){
                $risultato->errore = "Errore durante la preparazione della query";
                return $risultato;
            }
            else{
                $stmt = $filtri;
            }


            if($stmt->execute() === true) { //controllo se l'esecuzione dello statement è andata a buon fine
                $res=$stmt->get_result();
            }
        }
        catch(Throwable $e){ //catch di tutti i Throwable, include sia errori che eccezioni
            global $file_connessione;
           if(!file_exists($file_connessione)){ //se il file è mancante o non trovato ritorno l'errore appropriato
                $risultato->errore = "Errore durante l'inclusione del file di configurazione";
            }
            else{
               $risultato->errore = "Errore durante l'esecuzione della query";
            }
            
            return $risultato; //interrompo l'esecuzione della funzione
        }

        if(controlloRisultato($res)) //se il risultato è settato
        {
            $risultato->result = $res; //inserisco il risultato nell'oggetto  
        }
        else
        {
            $risultato->errore = "La query ha ritornato un risultato non valido"; 
        }

        $conn->close(); 
        $stmt->close(); 
        return $risultato; //ritorno il risultato
        
    }

    //per controllo permessi utente:
    //per verificare che l'utente ha i permessi per modificare, verngono passati, oltre ai nuovi dati, gli id del dato da moficare e dell'utenute
    //vengono fatte quert al db per prendere il ruolo e codice meccanografico della scuola a cui è legato l'utente e il codice a cui è legato il dato
    //se l'utente è amministratore viene skippato il secondo controllo. Se è un utente ordinario viene confrontato il suo cod meccanografico con quello del dato
    //viene concessa la modifica solo se i codici coincidono, altrimenti viene inviato un errore nell'oggetto risultato

   function modificaEvento($idUtente, $idEvento, $titolo, $descrizione, $target, $ora_inizio, $ora_fine, $visibile, $prenotabile, $via_P, $n_civico_P, $coordinate, $descrizioneBreve)
    {
        $risultato = new risultato(); 

        $res = null;
        
        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
        $stmt = null;

        if(!validaCampi())
        {
            $risultato->errore = "errore nell'inserimento dei campi"; 
        }
        else
        {
            if(!verificaPermessi($idUtente, $idEvento, $conn))
            {
                $risultato->errore = "errore nei permessi";
                return $risultato;  
            }
            else
            {
                $sql = "update eventi set titolo = ?, descrizione = ?, target = ?, ora_inizio = ?, ora_fine = ?, visibile = ?, prenotabile = ?, via_P = ?, n_civico_P = ?, coordinate = ?, descrizione_breve = ? where ID_evento = ?";
                
                $stmt = $conn->prepare($sql); 

                if($stmt === false)
                {
                    $risultato->errore = "errore nella creazione dello statement"; 
                } 
                else
                {
                    $stmt->bind_param("sssssiisissi", $titolo, $descrizione, $target, $ora_inizio, $ora_fine, $visibile, $prenotabile, $via_P, $n_civico_P, $coordinate, $descrizioneBreve, $idEvento);
                    if(!$stmt->execute())
                    {
                        $risultato->errore = "errore nell'esecuzione dello statement";
                    }
                    else
                    {
                        $risultato->result = true; 
                    }
                }
            }
            $stmt->close(); 
            $conn->close(); 
            return $risultato; 
        }
    }

    function eliminaEvento($idEvento, $idUtente)
    {
        $risultato = new risultato(); 
        $res = null;
        
        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
        $stmt = null;

        if(!verificaPermessi($idUtente, $idEvento, $conn))
        {
            $risultato->errore = "errore nei permessi"; 
            return $risultato; 
        }
        else
        {
            $sql = "update eventi set data_eliminazione = if(data_eliminazione is NULL, NOW(), data_eliminazione) where ID_evento = ?";

            $stmt = $conn->prepare($sql); 

            if($stmt === false)
            {
                $risultato->errore = "errore nella preparazione dello statement"; 
            }

            $stmt->bind_param("i", $idEvento); 

            if(!$stmt->execute())
            {
                $risultato->errore = "errore nell'esecuzione dello statement"; 
            }
            else
            {
                $risultato->result = true;  
            }
        }
        $stmt->close(); 
        $conn->close(); 
        return $risultato;

    }

    function compilaCampi($id_evento)
    {
        $risultato = new risultato(); 

        $res = null; 

        $sql = "select * from eventi where ID_evento = ?"; 

        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
        $stmt = null;

        $stmt = $conn->prepare($sql); 

        if($stmt === false)
        {
            $risultato->errore = "errore nella preparazione dello statement"; 
        }
        $stmt->bind_param("i", $id_evento);
        if(!$stmt->execute())
        {
            $risultato->errore = "errore nell'esecuzione dello statement"; 
        }
        else
        {
            $res = $stmt->get_result(); 
        }
        if(controlloRisultato($res))
        {
            $risultato->result = $res; 
        }
        else
        {
            $risultato->errore = "La query ha ritornato un risultato non valido"; 
        }

        $stmt->close(); 
        $conn->close(); 
        return $risultato;


    }

    function insertEventoTerritoriale($titolo, $descrizione, $ora_inizio, $ora_fine, $visibile, $prenotabile, $via_P, $n_civico_P, $coordinate, $descrizione_breve, $id_citta, $cod_scuola, $foto){
        //include("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/pictures/gestFoto.php");
        $risultato = new risultato(); 

        try {
            global $HOSTDB, $USERDB, $NOMEDB, $PASSDB;
            $sql = "CALL AddEventoTerritorio(?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
            //Carico foto. Passare al metodo  $_FILES[“foto”]
            //$id_foto = uploadFoto($conn, $foto);
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssiissssisi", $titolo, $descrizione, $ora_inizio, $ora_fine, $visibile, $prenotabile, $via_P, $n_civico_P, $coordinate, $descrizione_breve, $id_citta, $cod_scuola, $id_foto);
            if ($stmt->execute() === true) { //controllo se l'esecuzione dello statement è andata a buon fine
                $risultato->result = $stmt->insert_id;
                //Associo la foto all'evento
                //assocEventiFoto($conn, $id_foto, $risultato->result);
            }
        } catch (Throwable $e) {
            $risultato->errore = "Errore durante l'esecuzione della query";
            return $risultato;
        }

        $conn->close();
        $stmt->close();
        return $risultato;
    }


    function insertEventoScolastico($titolo, $descrizione, $ora_inizio, $ora_fine, $visibile, $prenotabile, $descrizione_breve, $idCitta, $cod_scuola, $foto){
        //include("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/pictures/gestFoto.php");
        $risultato = new risultato();

        try {
            global $HOSTDB, $USERDB, $NOMEDB, $PASSDB;
            $sql = "insert into eventi (titolo,descrizione,ora_inizio,ora_fine,visibile,prenotabile,descrizione_breve,id_citta,cod_scuola,id_foto) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
            $id_foto = null; 
            //$id_foto = uploadFoto($conn, $foto);
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssiisisi", $titolo, $descrizione, $ora_inizio, $ora_fine, $visibile, $prenotabile, $descrizione_breve, $idCitta, $cod_scuola, $id_foto);
            if ($stmt->execute() === true) {
                $risultato->result = $stmt->insert_id;
                //assocEventiFoto($conn, $id_foto, $risultato->result);
            }
        } catch (Throwable $e) {
            $risultato->errore = "Errore durante l'esecuzione della query";
            return $risultato;
        }
        $stmt->close();
        $conn->close();
        return $risultato;
    }

    function validaCampi()
    {
        
    }

    //ritorna il ruolo e codice scuola di un utente
    function GetScuolaRuoloUtente($idUtente, $conn) {
        $sql = "SELECT tipo, cod_scuola FROM utenti WHERE ID_utente = ?"; //------------------------------------------------- da usare procedura
        if($stmt = $conn->prepare($sql)){ 
            
            $stmt->bind_param("i", $idUtente);

            if($stmt->execute()) { 
                $res = $stmt->get_result();
                if(!$utente = $res->fetch_object()){
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        return $utente;
    }

    function coordinateScuola($conn, $idUtente)
    {
        $risultato = new risultato();

        $sql = "select via, n_civico, coordinate from scuole s inner join utenti u on u.cod_scuola = s.COD_meccanografico where u.ID_utente = ?"; 

        $res = null; 

        $stmt = $conn->prepare($sql); 

        if($stmt === false)
        {
            $risultato->errore = "errore nella preparazione dello statement"; 
            return $risultato; 
        }
        else
        {
            $stmt->bind_param("i", $idUtente); 
            if(!$stmt->execute())
            {
                $risultato->errore = "errore nell'esecuzione dello statement"; 
                return;
            }
            else
            {
                $res = $stmt->get_result(); 
            }
            if(controlloRisultato($res))
            {
                $risultato->result = $res; 
            }
            else
            {
                $risultato->errore = "La query ha ritornato un risultato non valido"; 
            }

            $stmt->close(); 
            $conn->close(); 
            return $risultato; 
        }

    }

    //dato un utente ed un evento verifica se l'utente ha il permesso di effettuare modifiche ad esso
    function verificaPermessi($idUtente, $idEvento, $conn){
        $sql = "SELECT 1 AS res
                FROM utenti u
                JOIN eventi e ON e.ID_evento = ?
                WHERE u.ID_utente = ?
                AND (
                    u.tipo = 'ADMIN'
                    OR u.cod_scuola = e.cod_scuola
                );";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ii", $idEvento, $idUtente);


            if($stmt->execute()) 
            { 
                $res = $stmt->get_result();
                $risultato = $res->fetch_object(); 
                $permesso = $risultato->res;
                if($permesso==1){
                    return true;
                }
                else{
                    return false;
                }
            } 
            else 
            {
                return null;
            }
        }
        else{
            return null;
        }


    }

    function IsValidSchool($cod, $conn){
        $sql="SELECT 1 AS res
              FROM scuole
              WHERE COD_meccanografico = ?";

        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $cod);

            if($stmt->execute()) 
            { 
                $res = $stmt->get_result();
                $risultato = $res->fetch_object(); 
                if($risultato->res == 1){
                    return true;
                }
                else{
                    return false;
                }
            } 
            else 
            {
                return null;
            }
        }
        else{
            return null;
        }
        
        
    }

    function ripristinaEvento($idEvento, $idUtente)
    {
        $risultato = new risultato(); 
        $res = null;
        
        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
        $stmt = null;

        if(!verificaPermessi($idUtente, $idEvento, $conn))
        {
            $risultato->errore = "errore nei permessi"; 
            return $risultato; 
        }
        else
        {
            $sql = "update eventi set data_eliminazione = null where ID_evento = ?";

            $stmt = $conn->prepare($sql); 

            if($stmt === false)
            {
                $risultato->errore = "errore nella preparazione dello statement"; 
            }

            $stmt->bind_param("i", $idEvento); 

            if(!$stmt->execute())
            {
                $risultato->errore = "errore nell'esecuzione dello statement"; 
            }
            else
            {
                $risultato->result = true;  
            }
        }
        $stmt->close(); 
        $conn->close(); 
        return $risultato;
    }

    function disegnaTabella($riga)
    {
        $tabellaEventi = ""; 
        // Adatta i nomi delle chiavi (es. 'ID_evento', 'titolo') ai nomi reali delle colonne del tuo database!
        $id = htmlspecialchars($riga['ID_evento'] ?? $riga['id'] ?? '');
        $titolo = htmlspecialchars($riga['titolo'] ?? '');
        $descrizione = htmlspecialchars($riga['descrizione'] ?? '');
        $target = htmlspecialchars($riga['target'] ?? '');
        $dataEliminazione = htmlspecialchars($riga['data_eliminazione'] ?? '');
        $foto = htmlspecialchars($riga['foto'] ?? 'default.png'); // Adatta al tuo DB
                            
        // Logica per lo stato di eliminazione
        $isEliminato = !empty($dataEliminazione);
        $rigaOpaca = $isEliminato ? 'opacity-50 bg-light' : ''; // Rende la riga visivamente disattivata
                            
        $tabellaEventi .= "<tr class='{$rigaOpaca}'>";
        $tabellaEventi .= "  <td class='px-3'>{$id}</td>";
        $tabellaEventi .=  "  <td>{$titolo}</td>";
        // Mostra solo i primi 50 caratteri della descrizione
        $tabellaEventi .=  "  <td>" . substr($descrizione, 0, 50) . (strlen($descrizione) > 50 ? '...' : '') . "</td>";
        $tabellaEventi .= "  <td class='text-center'>{$target}</td>";
        $tabellaEventi .= "  <td>" . ($isEliminato ? $dataEliminazione : '<span class="badge bg-success">Attivo</span>') . "</td>";
        $tabellaEventi .=  "  <td><img src='../percorso/foto/{$foto}' alt='foto' style='width: 40px; height: 40px; object-fit: cover; border-radius: 4px;'></td>";
                            
        // Colonna Azioni
        $tabellaEventi .= "  <td class='text-end'>";
                            
        // Bottone Modifica (Sempre presente)
        $tabellaEventi .= "<form method='POST' action='eventi.php' style='display:inline;'>
                                <input type='hidden' name='idEvento' value='{$id}'>
                                <input type='hidden' name='azione' value='modifica'>
                                <button type='button' class='btn btn-sm btn-outline-primary me-1' data-bs-toggle='modal' data-bs-target='#modalEventiModifica' data-id='{$id}'>
                                    <i class='bi bi-pencil'></i> Modifica
                                </button>
                            </form>";
                            
        // Bottoni Elimina / Ripristina (Condizionali)
        if (!$isEliminato) {
            $tabellaEventi .= "
                            <form method='POST' action='eventi.php' style='display:inline;'>
                                <input type='hidden' name='idEvento' value='{$id}'>
                                <input type='hidden' name='azione' value='elimina'>
                                <button type='submit' class='btn btn-sm btn-outline-danger'
                                    onclick='return confirm(\"Sei sicuro?\")'>
                                            <i class='bi bi-trash'></i> Elimina
                                </button>
                            </form>";
        } else {
            $tabellaEventi .= " <form method='POST' action='eventi.php' style='display:inline;'> 
                                    <input type='hidden' name='idEvento' value='{$id}'> 
                                    <input type='hidden' name='azione' value='ripristina'> 
                                    <button type='submit' class='btn btn-sm btn-outline-warning' > 
                                        <i class='bi bi-arrow-counterclockwise'></i> Ripristina 
                                </button> 
                                </form>";
        }
                            
        $tabellaEventi .= "  </td>";
        $tabellaEventi .= "</tr>";

        return $tabellaEventi; 
    }


    

    


?>