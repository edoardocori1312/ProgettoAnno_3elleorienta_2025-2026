<?php
session_start();

if($_POST)
{
    if (validaInput($_POST["email"], $_POST["password"]) && $_POST["email"] != "" && $_POST["password"] != "") 
    {
      include("controlloLogin.php");
    }else
    {
      $_SESSION["errore"] = "Compila tutti i campi";
    } 
}
function validaInput($email, $password)
{
    if(isset($email)&& isset($password))
    {
        $email = trim($email); 
        $password = trim($password);

        return true;
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>3elleorienta Dashboard Login</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow rounded" style="max-width: 450px; width: 100%;">
    

      <!-- SINISTRA: Login -->
      <div class=" bg-white p-4 d-flex flex-column">
        <div class="text-center mb-4">
        <span class="ant-avatar logo ant-avatar-circle ant-avatar-image">
            <img src="logo.png"width="60" height="60"></span>
        </div>

        <form action="login.php" method="POST">

          <!-- Username -->
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
                <input type="text" name="email" id="email" class="form-control">    
          </div>

          <!-- Password -->
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control">
          </div>

          <!-- Remember + Forgot -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="" id="remember" checked>
              <label class="form-check-label" for="remember">
                Ricordami
              </label>
            </div>
              <a href="/forgotPassword" class="text-decoration-none">Password dimenticata?</a>
            </div>

          <!-- Login button -->
          <div class="d-grid mb-2">
            <button type="submit" class="btn btn-primary">Accedi</button>
          </div>

        </form>
      </div>

      <?php
        if (!empty($_SESSION["errore"])) {
          $msg = htmlspecialchars($_SESSION["errore"], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
          echo '
            <div class="mt-3">
              <div class="alert alert-danger alert-dismissible fade show w-100 shadow-sm" role="alert">
                <strong class="me-2">Errore:</strong>' . $msg . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
              </div>
            </div>
          ';
          unset($_SESSION["errore"]);
        }
      ?>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
