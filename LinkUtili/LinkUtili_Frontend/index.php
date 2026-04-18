<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheletro 3elleorienta</title>
    <link rel="stylesheet" href="stile.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <?php include("navbar.html"); ?>
    <main class="py-5" style="min-height: 80vh;">
        <div class="container-fluid">

            <!-- Riga Principale -->
        <div class="row g-4 row-content">
            <!-- Colonna Sinistra -->
            <div class="col-lg-2 col-md-2 col-sm-12">
                Inserisci qui il tuo contenuto se necessiti di una colonna a sinistra
            </div>

            <!-- Colonna Centrale -->
            <div class="col-lg-8 col-md-8 col-sm-12">
                Inserisci qui il tuo contenuto se necessiti di una colonna centrale
            </div>

            <!-- Colonna Destra -->
            <div class="col-lg-2 col-md-2 col-sm-12">
                Inserisci qui il tuo contenuto se necessiti di una colonna a destra
            </div>
        </div>
        </div>
    </main>
    <?php include("footer.html"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>