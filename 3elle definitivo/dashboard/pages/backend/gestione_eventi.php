<?php 
    class risultato { //classe risultato ritornata in output
        public ?string $errore = null;
        public $result = null;

        public function isSuccess(): bool //per semplicità dal lato frontend si può chiamare questa funzione per verificare se l'operazione è andata a buon fine.
        {
            return $this->errore === null; //ritorna se errore è settato
        }
    }

    $file_connessione = "../connessione/db.php";
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
        

        $query = "SELECT e.* FROM Eventi e";
        $utente = null;
        $params ="";
        $conditions = [];
        $values = [];


        // Acquisisco utente
        $utente = GetScuolaRuoloUtente($idUtente, $conn);

        
        // JOIN città
        if($citta != null){
            if($tipoRicerca == true){
                $query .= " INNER JOIN Citta c ON c.ID_citta = e.id_citta";
            } else {
                $query .= " LEFT JOIN Citta c ON c.ID_citta = e.id_citta";
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
        
        $sql = "SELECT e.* FROM Eventi e"; //query da eseguire
        $res = null;

        try{ //try per evitare che il programma crepi

            global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
            $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");
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

   function modificaEventoScolastico($idUtente, $idEvento, $titolo, $descrizione, $ora_inizio, $ora_fine, $visibile, $prenotabile, $descrizioneBreve, $foto)
    {
        include("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/pictures/gestFoto.php");
    
        $risultato = new risultato(); 
        $coordinate = null;

        $res = null;
        
        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");
        $stmt = null;

            if(!verificaPermessi($idUtente, $idEvento, $conn))
            {
                $risultato->errore = "errore nei permessi";
                return $risultato;  
            }
            else
            {
                $id_foto = uploadFoto($conn, $foto);
            
                $sql = "update Eventi set titolo = ?, descrizione = ?, ora_inizio = ?, ora_fine = ?, visibile = ?, prenotabile = ?, descrizione_breve = ?, id_foto = ? where ID_evento = ?";
                
                $stmt = $conn->prepare($sql); 

                if($stmt === false)
                {
                    $risultato->errore = "errore nella creazione dello statement"; 
                    return $risultato; 
                } 
                else
                {
                    $stmt->bind_param("ssssiisii", $titolo, $descrizione, $ora_inizio, $ora_fine, $visibile, $prenotabile, $descrizioneBreve, $id_foto, $idEvento);
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
    
    function modificaEventoTerritoriale($idUtente, $idEvento, $titolo, $descrizione, $ora_inizio, $ora_fine, $visibile, $prenotabile, $via_P, $n_civico_P, $punto, $descrizioneBreve, $id_citta, $foto)
    {
        include("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/pictures/gestFoto.php");
        $risultato = new risultato(); 

        $res = null;
        
        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");
        $stmt = null;

            if(!verificaPermessi($idUtente, $idEvento, $conn))
            {
                $risultato->errore = "errore nei permessi";
                return $risultato;  
            }
            else
            {
                $id_foto = uploadFoto($conn, $foto);

                $sql = "update Eventi set titolo = ?, descrizione = ?, ora_inizio = ?, ora_fine = ?, visibile = ?, prenotabile = ?, via_P = ?, n_civico_P = ?, coordinate = ST_GeomFromText(?, 4326), descrizione_breve = ?, id_citta = ?, id_foto = ? where ID_evento = ?";
                
                $stmt = $conn->prepare($sql); 

                if($stmt === false)
                {
                    $risultato->errore = "errore nella creazione dello statement"; 
                    return $risultato;
                } 
                else
                {
                    $stmt->bind_param("ssssiissssiii", $titolo, $descrizione, $ora_inizio, $ora_fine, $visibile, $prenotabile, $via_P, $n_civico_P, $punto, $descrizioneBreve, $id_citta, $id_foto, $idEvento);
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

    function eliminaEvento($idEvento, $idUtente)
    {
        $risultato = new risultato(); 
        $res = null;
        
        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");
        $stmt = null;

        if(!verificaPermessi($idUtente, $idEvento, $conn))
        {
            $risultato->errore = "errore nei permessi"; 
            return $risultato; 
        }
        else
        {
            $sql = "update Eventi set data_eliminazione = NOW() where ID_evento = ?";

            $stmt = $conn->prepare($sql); 

            if($stmt === false)
            {
                $risultato->errore = "errore nella preparazione dello statement"; 
            }

            $stmt->bind_param("i", $idEvento); 

            if(!$stmt->execute())
            {
                $risultato->errore = $stmt->error; 
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

        $sql = "select *, ST_X(coordinate) AS longitudine, ST_Y(coordinate) AS latitudine from Eventi where ID_evento = ?"; 

        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");
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

    function insertEventoTerritoriale($titolo, $descrizione, $ora_inizio, $ora_fine, $target, $visibile, $prenotabile, $via_P, $n_civico_P, $punto, $descrizione_breve, $id_citta, $foto)
    {
        include("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/pictures/gestFoto.php");
        $risultato = new risultato(); 

        try {
            global $HOSTDB, $USERDB, $NOMEDB, $PASSDB;
            $sql = "insert into Eventi(titolo, descrizione, ora_inizio, ora_fine, target, visibile, prenotabile, via_P, n_civico_P, coordinate, descrizione_breve, id_citta, id_foto)
                    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ST_GeomFromText(?, 4326), ?, ?, ?)";
            $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
            //Carico foto. Passare al metodo  $_FILES[“foto”]
            $id_foto = uploadFoto($conn, $foto);
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiissssii", $titolo, $descrizione, $ora_inizio, $ora_fine, $target, $visibile, $prenotabile, $via_P, $n_civico_P, $punto, $descrizione_breve, $id_citta, $id_foto);
            if ($stmt->execute() === true) { //controllo se l'esecuzione dello statement è andata a buon fine
                $risultato->result = $stmt->insert_id;
                //Associo la foto all'evento
                assocEventiFoto($conn, $id_foto, $risultato->result);
            }
            else
            {
                $risultato->errore = "Errore";
                return $risultato;
            }
        } catch (Throwable $e) {
            $risultato->errore = "Errore durante l'esecuzione della query";
            return $risultato;
        }

        $conn->close();
        $stmt->close();
        return $risultato;
    }


    function insertEventoScolastico($titolo, $descrizione, $ora_inizio, $ora_fine, $target, $visibile, $prenotabile, $descrizione_breve, $cod_scuola, $foto)
    {
        include("/home/uawit4pc/domains/3elleorienta.sviluppo.host/public_html/pictures/gestFoto.php");
        $risultato = new risultato();

        try {
            global $HOSTDB, $USERDB, $NOMEDB, $PASSDB;
            $sql = "insert into Eventi(titolo, descrizione, ora_inizio, ora_fine, target, visibile, prenotabile, descrizione_breve,cod_scuola, id_foto) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $conn = new mysqli($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
            $id_foto = uploadFoto($conn, $foto);
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiissi", $titolo, $descrizione, $ora_inizio, $ora_fine, $target, $visibile, $prenotabile, $descrizione_breve, $cod_scuola, $id_foto);
            if ($stmt->execute() === true) {
                $risultato->result = $stmt->insert_id;
                assocEventiFoto($conn, $id_foto, $risultato->result);
            }
        } catch (Throwable $e) {
            $risultato->errore = "Errore durante l'esecuzione della query";
            return $risultato;
        }

        $conn->close();
        $stmt->close();
        return $risultato;
    }

    //ritorna il ruolo e codice scuola di un utente
    function GetScuolaRuoloUtente($idUtente, $conn) {
        $sql = "SELECT tipo, cod_scuola FROM Utenti WHERE ID_utente = ?"; //------------------------------------------------- da usare procedura
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


    function recuperaIDCitta($citta)
    {
        $risultato = new risultato();

        $sql = "select ID_citta from Citta where nome = ?"; 

        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");

        $res = null; 

        $stmt = $conn->prepare($sql); 

        if($stmt === false)
        {
            $risultato->errore = "errore nella preparazione dello statement"; 
            return $risultato; 
        }
        else
        {
            $stmt->bind_param("s", $citta); 
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

    function controllaData($data_inizio, $data_fine)
    {
        if($data_fine <= $data_inizio)
        {
            return false; 
        }
        return true; 
    }


    function recuperaNomeCitta($id_citta)
    {
        $risultato = new risultato();

        $sql = "select nome from Citta where ID_citta = ?"; 

        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");

        $res = null; 

        $stmt = $conn->prepare($sql); 

        if($stmt === false)
        {
            $risultato->errore = "errore nella preparazione dello statement"; 
            return $risultato; 
        }
        else
        {
            $stmt->bind_param("i", $id_citta); 
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
                FROM Utenti u
                JOIN Eventi e ON e.ID_evento = ?
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
              FROM Scuole
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
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");
        $stmt = null;

        if(!verificaPermessi($idUtente, $idEvento, $conn))
        {
            $risultato->errore = "errore nei permessi"; 
            return $risultato; 
        }
        else
        {
            $sql = "update Eventi set data_eliminazione = null where ID_evento = ?";

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

    function clonaEvento($idEvento, $idUtente)
    {
        $risultato = new risultato(); 
        $res = null;
        
        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");
        $stmtEvento = null;

        if(!verificaPermessi($idUtente, $idEvento, $conn))
        {
            $risultato->errore = "errore nei permessi"; 
            return $risultato; 
        }
        else
        {
            $sql = "select * from Eventi where ID_evento = ?";

            $stmtEvento = $conn->prepare($sql); 

            if($stmtEvento === false)
            {
                $risultato->errore = "errore nella preparazione dello statement "; 
                return $risultato; 
            }

            $stmtEvento->bind_param("i", $idEvento); 

            if(!$stmtEvento->execute())
            {
                $risultato->errore = "errore nell'esecuzione dello statement"; 
            }
            else
            {
                $resEvento = $stmtEvento->get_result();
                $evento = $resEvento->fetch_object(); 
                $nome = "Copia di ". $evento->titolo;
                if($evento->target == "SCOLASTICO")
                {
                    $sqlInserimentoEvento = "insert into Eventi (titolo, descrizione, target, ora_inizio, ora_fine, visibile, prenotabile, descrizione_breve, cod_scuola, id_foto) values (?,?,?,?,?,?,?,?,?,?)";
                } 
                else
                {
                    $sqlInserimentoEvento = "insert into Eventi (titolo, descrizione, target, ora_inizio, ora_fine, visibile, prenotabile, via_P, n_civico_P, coordinate, descrizione_breve, id_citta, cod_scuola, id_foto) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                }
                $stmtInserimento = $conn->prepare($sqlInserimentoEvento); 
                if($stmtInserimento === false)
                {
                    $risultato->errore = "errore nella preparazione dello statement ";
                    return $risultato; 
                }
                if($evento->target == "SCOLASTICO")
                {
                    $stmtInserimento->bind_param("sssssiissi", $nome, $evento->descrizione, $evento->target, $evento->ora_inizio, $evento->ora_fine, $evento->visibile, $evento->prenotabile, $evento->descrizione_breve, $evento->cod_scuola, $evento->id_foto); 
                } 
                else
                {
                    $stmtInserimento->bind_param("sssssiisissisi", $nome, $evento->descrizione, $evento->target, $evento->ora_inizio, $evento->ora_fine, $evento->visibile, $evento->prenotabile, $evento->via_P, $evento->n_civico_P, $evento->coordinate, $evento->descrizione_breve, $evento->id_citta, $evento->cod_scuola, $evento->id_foto);
                }
                if($stmtInserimento->execute())
                {
                    $risultato->result = true; 
                }
                else
                {
                    $risultato->errore = "errore nell'esecuzione dello statement";
                }
            }
        }
        $stmtEvento->close();
        $stmtInserimento->close(); 
        $conn->close(); 
        return $risultato;
    }

    function pathFoto($idEvento)
    {
        $risultato = new risultato();

        $sql = "select id_foto from Eventi where ID_evento = ?"; 

        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");

        $res = null; 

        $stmt = $conn->prepare($sql); 

        if($stmt === false)
        {
            $risultato->errore = "errore nella preparazione dello statement"; 
            return $risultato; 
        }
        else
        {
            $stmt->bind_param("i", $idEvento); 
            if(!$stmt->execute())
            {
                $risultato->errore = "errore nell'esecuzione dello statement"; 
                return;
            }
            else
            {
                $resPath = $stmt->get_result(); 
                $foto = $resPath->fetch_object(); 
                
                $sqlPathFoto = "select path_foto from Foto where ID_foto = ?"; 

                $stmtFoto = $conn->prepare($sqlPathFoto); 

                if($stmtFoto === false)
                {
                    $risultato->errore = "errore nello statement"; 
                    return $risultato; 
                }
                else
                {
                    $stmtFoto->bind_param("i", $foto->id_foto); 

                    if($stmtFoto->execute())
                    {
                        $resFotoPath = $stmtFoto->get_result(); 
                        $risultato->result = $resFotoPath; 
                    }
                    else
                    {
                        $risultato->errore = "errore nell'esecuzione dello statement"; 
                    }
                }
            }
            $stmt->close(); 
            $conn->close(); 
            return $risultato; 
        }
    }

    function recuperaCodScuola($nome)
    {
        $risultato = new risultato();

        $sql = "select COD_meccanografico from Scuole where nome = ?"; 

        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");

        $res = null; 

        $stmt = $conn->prepare($sql); 

        if($stmt === false)
        {
            $risultato->errore = "errore nella preparazione dello statement"; 
            return $risultato; 
        }
        else
        {
            $stmt->bind_param("s", $nome); 
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

    function recuperaNomeScuola($cod_scuola)
    {
        $risultato = new risultato();

        $sql = "select nome from Scuole where COD_meccanografico = ?"; 

        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB); $conn->set_charset("utf8mb4");

        $res = null; 

        $stmt = $conn->prepare($sql); 

        if($stmt === false)
        {
            $risultato->errore = "errore nella preparazione dello statement"; 
            return $risultato; 
        }
        else
        {
            $stmt->bind_param("s", $cod_scuola); 
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
    

    function disegnaTabella($riga)
    {
        $tabellaEventi = ""; 
        $id = htmlspecialchars($riga['ID_evento'] ?? $riga['id'] ?? '');
        $titolo = htmlspecialchars($riga['titolo'] ?? '');

        $resScuola = recuperaCodScuola($riga['COD_meccanografico']);
        if($resScuola->isSuccess() && $resIDCitta->result)
        {
            $infoScuola = $resScuola->result->fetch_assoc(); 
            $nomeScuola = $infoScuola["nome"];
        } 

        $target = htmlspecialchars($riga['target'] ?? '');
        $dataEliminazione = htmlspecialchars($riga['data_eliminazione'] ?? '');
                            
        // Logica per lo stato di eliminazione
        $isEliminato = !empty($dataEliminazione);
        $rigaOpaca = $isEliminato ? 'opacity-50 bg-light' : ''; 
                            
        $tabellaEventi .= "<tr class='{$rigaOpaca}'>";
        $tabellaEventi .= "  <td class='px-3'>{$id}</td>";
        $tabellaEventi .=  "  <td>{$titolo}</td>";
        // Mostra solo i primi 50 caratteri della descrizione
        $tabellaEventi .=  "  <td>" . $nomeScuola . "</td>";
        $tabellaEventi .= "  <td class='text-center'>{$target}</td>";
        $tabellaEventi .= "  <td>" . ($isEliminato ? $dataEliminazione : '<span class="badge bg-success">Attivo</span>') . "</td>";
                            
        // Colonna Azioni
        $tabellaEventi .= "  <td class='text-end'>";
                            
        // Bottone Modifica (effettua un vero submit)
        $tabellaEventi .= "<form method='POST' action='index.php' style='display:inline;'>
						<input type='hidden' name='page' value='eventi'>
                        <input type='hidden' name='idEvento' value='{$id}'>
                        <input type='hidden' name='azione' value='modifica'>
                        <button type='submit' class='btn btn-sm btn-outline-primary me-1'>
                            <i class='bi bi-pencil'></i> Modifica
                        </button>
                    </form>";

        $tabellaEventi .= "<form method='POST' action='index.php' style='display:inline;'>
						<input type='hidden' name='page' value='eventi'>
                        <input type='hidden' name='idEvento' value='{$id}'>
                        <input type='hidden' name='azione' value='clona'>
                        <button type='submit' class='btn btn-sm btn-outline-secondary me-1'>
                            <i class='bi bi-copy'></i> Clona
                        </button>
                    </form>";
                            
        // Bottoni Elimina / Ripristina (Condizionali)
        if (!$isEliminato) {
            $tabellaEventi .= "
                            <form method='POST' action='index.php' style='display:inline;'>
								<input type='hidden' name='page' value='eventi'>
                                <input type='hidden' name='idEvento' value='{$id}'>
                                <input type='hidden' name='azione' value='elimina'>
                                <button type='submit' class='btn btn-sm btn-outline-danger'
                                    onclick='return confirm(\"Sei sicuro?\")'>
                                            <i class='bi bi-trash'></i> Elimina
                                </button>
                            </form>";
        } else {
            $tabellaEventi .= " <form method='POST' action='index.php' style='display:inline;'> 
									<input type='hidden' name='page' value='eventi'>
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