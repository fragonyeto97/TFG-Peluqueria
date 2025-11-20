<?php
session_start();
include("../config/db.php");

if (isset($_POST['login'])) {
    $usuario = $_POST['nombre_usuario'];
    $clave = $_POST['contraseña'];

    // Busca el usuario en la base de datos
    $sql = "SELECT * FROM usuarios WHERE nombre_usuario='$usuario'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ⚠️ Si aún no usas password_hash, usa comparación simple (solo para pruebas)
        if ($clave === $user['contraseña']) {
            $_SESSION['usuario'] = $user['nombre_usuario'];
            $_SESSION['rol'] = $user['rol'];
            header("Location: ../views/dashboard.php");
        } else {
            echo "<script>alert('Contraseña incorrecta');window.location='../views/login.php';</script>";
        }
    } else {
        echo "<script>alert('Usuario no encontrado');window.location='../views/login.php';</script>";
    }
}
?>

