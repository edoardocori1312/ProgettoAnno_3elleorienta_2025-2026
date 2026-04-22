<?php 
    class risultato { //classe risultato ritornata in output
        public ?string $errore = null;
        public $result = null;

        public function isSuccess(): bool //per semplicità dal lato frontend si può chiamare questa funzione per verificare se l'operazione è andata a buon fine.
        {
            return $this->errore === null; //ritorna se errore è settato
        }
    }

    $file_connessione = "db.php";
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


    function inserisciEvento($idUtente, $cod_scuola, $titolo, $descrizione, $target, $ora_inizio, $ora_fine, $visibile, $prenotabile, $via_P, $n_civico_P, $coordinate, $descrizioneBreve)
    {
        $risultato = new risultato(); 

        $res = null; 

        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
        $stmt = null;

        if(!$validaCampi()) //------------------------------------------------------------------- da fare valida campi
        {
            $risultato->errore = "Errore nei campi";
            return $risultato;
        }
        
        $dati = GetScuolaRuoloUtente($idUtente, $conn); 
        $ruolo = $dati->tipo;
        $cod = $dati->cod_scuola;
        if($ruolo == "ADMIN")
        {
            if(!$cod_scuola == null){
                $IsValid = IsValidSchool($cod_scuola)
                if($IsValid == null){
                    $risultato->errore = "errore nella verifica del codice meccanografico";
                    return $risultato;
                }
                if($IsValid){
                    $cod = $cod_scuola;
                }
            }
        }
        else if ($ruolo == null)
        {
            $risultato->errore = "errore nel trovare il ruolo dell'utente";
            return $risultato; 
        }

        $isScolastico = false;
        if($target == "SCOLASTICO"){
            $isScolastico = true;
        }

        //---------------------------------------------------------------------------------------------aggiungere gestione gestione target scolastico

        $sql = "insert into eventi (titolo, descrizione, target, ora_inizio, ora_fine, visibile, prenotabile, via_P, n_civico_P, coordinate, descrizione_breve) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
            
        if($stmt === false)
        {
            $risultato->errore = "errore nella preparazione dello statement";
        }

        $stmt->bind_param("sssssiisiss", $titolo, $secrizione, $target, $ora_inizio, $ora_fine, $visibile, $prenotabile, $via_P, $n_civico_P, $coordinate, $descrizioneBreve); 

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
                        $res = $stmt->get_result(); 
                    }
                }

                if(controlloRisultato($res))
                {
                    $risultato->result = $res; 
                }
                else
                {
                    $risultato->errore = "La query ha ritornato un risultato non valido"; 
                }
            }
            $stmt->close(); 
            $conn->close(); 
            return $risultato; 
        }
    }

    function eliminaEvento($data_eliminazione, $idEvento)
    {
        $risultato = new risultato(); 
        $res = null;
        
        global $HOSTDB, $USERDB, $NOMEDB, $PASSDB; 
        $conn = new mysqli ($HOSTDB, $USERDB, $PASSDB, $NOMEDB);
        $stmt = null;

        if(!verificaPermessi($idUtente, $idEvento, $conn))
        {
            $risultato->errore = "errore nei permessi"; 
        }
        else
        {
            $sql = "update eventi set data_eliminazione = ? where ID_evento = ?"; 

            $stmt = $conn->prepare($sql); 

            if($stmt === false)
            {
                $risultato->errore = "errore nella preparazione dello statement"; 
            }

            $stmt->bind_param("si", $data_eliminazione, $idEvento); 

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
                var_dump($risultato);
                $permesso = $risultato->res;
                if($permesso=1){
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


    

    


?>