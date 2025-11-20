<?php
include("../config/db.php");

$mensaje = "";

if (isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $db = new Database();
    $conn = $db->connect();

    // Comprobar si el usuario ya existe (mismo nombre exacto)
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario=?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $mensaje = "El nombre de usuario ya existe ❌";
    } else {
        // Insertar nuevo usuario con nombre, apellidos y rol cliente
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellidos, usuario, password, rol) VALUES (?, ?, ?, ?, 'cliente')");
        $stmt->bind_param("ssss", $nombre, $apellidos, $usuario, $password);
        if ($stmt->execute()) {
            $mensaje = "Cuenta creada correctamente ✅";
        } else {
            $mensaje = "Error al registrar usuario ❌";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro - Peluquería</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Crear cuenta</h2>
    <?php if($mensaje): ?>
        <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Nombre</label>
            <input type="text" class="form-control" name="nombre" required>
        </div>
        <div class="mb-3">
            <label>Apellidos</label>
            <input type="text" class="form-control" name="apellidos" required>
        </div>
        <div class="mb-3">
            <label>Usuario</label>
            <input type="text" class="form-control" name="usuario" required>
        </div>
        <div class="mb-3">
            <label>Contraseña</label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <button type="submit" name="registrar" class="btn btn-success">Registrarse</button>
        <p class="mt-3">
            ¿Ya tienes una cuenta?
            <a href="login.php">Inicia sesión aquí</a>
        </p>
    </form>
</div>
</body>
</html>
