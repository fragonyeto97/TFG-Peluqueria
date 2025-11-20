<?php
session_start();
include("../config/db.php");

$mensaje = "";

if (isset($_POST['login'])) {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario=? LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if (!$conn) {
        die("❌ No hay conexión con la base de datos.");
    }

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        if ($password === $fila['password']) {
            $_SESSION['usuario'] = $fila['usuario'];
            $_SESSION['rol'] = $fila['rol'];    
            $_SESSION['id_usuario'] = $fila['id'];

            if ($fila['rol'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: cliente_dashboard.php");
            }
            exit;
        } else {
            $mensaje = "Contraseña incorrecta ❌";
        }
    } else {
        $mensaje = "Usuario no encontrado ❌";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login - Peluquería</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background-color: #007bff; /* Azul de fondo */
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: Arial, sans-serif;
    }

    .login-card {
        background-color: #ffffff; /* Caja blanca */
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    /* Logo */
    .login-card img {
        width: 120px;
        margin-bottom: 20px;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    /* Botón azul */
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    .text-start label {
        font-weight: bold;
    }
</style>
</head>
<body>
<div class="login-card">
    <!-- Logo -->
<img src="../imagenes/logo.png" alt="Logo Peluquería" width="120">


    <h2 class="mb-4">Iniciar Sesión</h2>

    <?php if($mensaje): ?>
        <div class="alert alert-danger"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3 text-start">
            <label>Usuario</label>
            <input type="text" class="form-control" name="usuario" required>
        </div>
        <div class="mb-3 text-start">
            <label>Contraseña</label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100">Iniciar sesión</button>
    </form>

    <p class="mt-3">
        ¿No tienes una cuenta?
        <a href="registro.php">Regístrate aquí</a>
    </p>
</div>
</body>
</html>
