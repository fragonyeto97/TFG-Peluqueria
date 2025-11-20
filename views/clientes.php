<?php
include __DIR__ . '/../includes/header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Conexión BD
$db = new Database();
$conn = $db->connect();

$mensaje = "";

// =========================
// AGREGAR CLIENTE
// =========================
if (isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $usuario = $_POST['usuario'];
    $password = $_POST['password']; // texto plano

    $rol = 'cliente';

    $stmt = $conn->prepare("
        INSERT INTO usuarios (nombre, apellidos, usuario, password, rol)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("sssss", $nombre, $apellidos, $usuario, $password, $rol);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: clientes.php?success=1");
        exit;
    } else {
        $mensaje = "Error: " . $conn->error;
        $stmt->close();
    }
}

// =========================
// EDITAR CLIENTE
// =========================
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        UPDATE usuarios SET nombre=?, apellidos=?, usuario=?, password=? 
        WHERE id=? AND rol='cliente'
    ");
    $stmt->bind_param("ssssi", $nombre, $apellidos, $usuario, $password, $id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: clientes.php?success=2");
        exit;
    } else {
        $mensaje = "Error: " . $conn->error;
        $stmt->close();
    }
}

// =========================
// OBTENER CLIENTE PARA EDITAR
// =========================
$clienteEditar = null;
if (isset($_GET['edit_id'])) {
    $idEditar = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id=? AND rol='cliente'");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $res = $stmt->get_result();
    $clienteEditar = $res->fetch_assoc();
    $stmt->close();
}

// =========================
// ELIMINAR CLIENTE
// =========================
if (isset($_GET['delete_id'])) {
    $idEliminar = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=? AND rol='cliente'");
    $stmt->bind_param("i", $idEliminar);
    $stmt->execute();
    $stmt->close();
    header("Location: clientes.php?success=3");
    exit;
}

// =========================
// BÚSQUEDA
// =========================
$busqueda = "";
if (isset($_GET['buscar'])) {
    $busqueda = $_GET['buscar'];
    $like = "%$busqueda%";
    $stmt = $conn->prepare("SELECT * FROM usuarios 
                            WHERE rol='cliente' AND (nombre LIKE ? OR apellidos LIKE ? OR usuario LIKE ?) 
                            ORDER BY id ASC");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $resultado = $conn->query("SELECT * FROM usuarios WHERE rol='cliente' ORDER BY id ASC");
}

// Mensajes
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 1: $mensaje = "Cliente agregado correctamente ✅"; break;
        case 2: $mensaje = "Cliente actualizado correctamente ✅"; break;
        case 3: $mensaje = "Cliente eliminado correctamente ✅"; break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Clientes - Peluquería</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #e3f2fd; }
.navbar { background-color: #0d6efd !important; }
.navbar .navbar-brand, .navbar .nav-link { color: white !important; }
.sidebar { background-color: #0d6efd; min-height: 100vh; color: white; padding-top: 20px; }
.sidebar a { color: white; display: block; padding: 10px 15px; margin: 5px 0; text-decoration: none; border-radius: 5px; }
.sidebar a:hover { background-color: #0b5ed7; }
.card { border: none; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.card-header { background-color: #0d6efd; color: white; font-weight: bold; }
.btn-primary { background-color: #0d6efd; border: none; }
.btn-primary:hover { background-color: #0b5ed7; }
.btn-info { background-color: #0dcaf0; color: white; border: none; }
.btn-info:hover { background-color: #31d2f2; }
.btn-danger { background-color: #dc3545; border: none; color: white; }
.btn-danger:hover { background-color: #bb2d3b; }
.table thead { background-color: #0d6efd; color: white; }
.alert { border-radius: 10px; border: 1px solid #0d6efd; background-color: #bbdefb; color: #0d6efd; }
</style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h3>Peluquería</h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="clientes.php">Clientes</a>
        <a href="citas.php">Citas</a>
        <a href="servicios.php">Servicios</a>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <div class="flex-grow-1 p-4">
        <nav class="navbar mb-4">
            <div class="container-fluid">
                <span class="navbar-text">Usuario: <?php echo $_SESSION['usuario']; ?></span>
            </div>
        </nav>

        <?php if($mensaje): ?>
            <div class="alert mb-4"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <!-- FORMULARIO EDITAR CLIENTE -->
        <?php if($clienteEditar): ?>
        <div class="card mb-4">
            <div class="card-header">Editar Cliente</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $clienteEditar['id']; ?>">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo $clienteEditar['nombre']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Apellidos</label>
                        <input type="text" class="form-control" name="apellidos" value="<?php echo $clienteEditar['apellidos']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Usuario</label>
                        <input type="text" class="form-control" name="usuario" value="<?php echo $clienteEditar['usuario']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Contraseña</label>
                        <input type="text" class="form-control" name="password" value="<?php echo $clienteEditar['password']; ?>" required>
                    </div>
                    <button type="submit" name="editar" class="btn btn-primary">Guardar Cambios</button>
                    <a href="clientes.php" class="btn btn-info">Cancelar</a>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- FORMULARIO AGREGAR CLIENTE -->
        <div class="card mb-4">
            <div class="card-header">Agregar Nuevo Cliente</div>
            <div class="card-body">
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
                        <input type="text" class="form-control" name="password" required>
                    </div>
                    <button type="submit" name="agregar" class="btn btn-primary">Agregar Cliente</button>
                </form>
            </div>
        </div>

        <!-- BÚSQUEDA -->
        <div class="mb-4">
            <form method="GET" class="d-flex">
                <input type="text" name="buscar" class="form-control me-2" placeholder="Buscar cliente..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn btn-info">Buscar</button>
            </form>
        </div>

        <!-- LISTA CLIENTES -->
        <div class="card">
            <div class="card-header">Lista de Clientes</div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellidos</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $fila['id']; ?></td>
                            <td><?php echo $fila['nombre']; ?></td>
                            <td><?php echo $fila['apellidos']; ?></td>
                            <td><?php echo $fila['usuario']; ?></td>
                            <td>
                                <a href="clientes.php?edit_id=<?php echo $fila['id']; ?>" class="btn btn-info btn-sm">Editar</a>
                                <a href="clientes.php?delete_id=<?php echo $fila['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que quieres eliminar este cliente?');">Eliminar</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
