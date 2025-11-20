<?php
include __DIR__ . '/../includes/header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

// Crear la conexión
$db = new Database();
$conn = $db->connect();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Mensaje
$mensaje = "";

// Agregar servicio
if (isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];

    $sql = "INSERT INTO servicios (nombre_servicio, precio) VALUES ('$nombre', '$precio')";
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Servicio agregado correctamente ✅";
    } else {
        $mensaje = "Error: " . $conn->error;
    }
}

// Editar servicio
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];

    $sql = "UPDATE servicios SET nombre_servicio='$nombre', precio='$precio' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Servicio actualizado correctamente ✅";
    } else {
        $mensaje = "Error: " . $conn->error;
    }
}

// Obtener servicio para editar
$servicioEditar = null;
if (isset($_GET['edit_id'])) {
    $idEditar = $_GET['edit_id'];
    $res = $conn->query("SELECT * FROM servicios WHERE id=$idEditar");
    $servicioEditar = $res->fetch_assoc();
}

// Eliminar servicio
if (isset($_GET['delete_id'])) {
    $idEliminar = $_GET['delete_id'];
    $conn->query("DELETE FROM servicios WHERE id=$idEliminar");
    header("Location: servicios.php");
    exit;
}

// Búsqueda
$busqueda = "";
if (isset($_GET['buscar'])) {
    $busqueda = $_GET['buscar'];
    $resultado = $conn->query("SELECT * FROM servicios WHERE nombre_servicio LIKE '%$busqueda%' OR precio LIKE '%$busqueda%' ORDER BY nombre_servicio ASC");
} else {
    $resultado = $conn->query("SELECT * FROM servicios ORDER BY nombre_servicio ASC");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Servicios - Peluquería</title>
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
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <h3>Peluquería</h3>
        <a href="dashboard.php">Dashboard</a>
        <a href="clientes.php">Clientes</a>
        <a href="citas.php">Citas</a>
        <a href="servicios.php">Servicios</a>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <!-- Contenido principal -->
    <div class="flex-grow-1 p-4">
        <nav class="navbar mb-4">
            <div class="container-fluid">
                <span class="navbar-text">Usuario: <?php echo $_SESSION['usuario']; ?></span>
            </div>
        </nav>

        <?php if($mensaje): ?>
            <div class="alert mb-4"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if($servicioEditar): ?>
        <div class="card mb-4">
            <div class="card-header">Editar Servicio</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $servicioEditar['id']; ?>">
                    <div class="mb-3">
                        <label>Nombre del servicio</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo $servicioEditar['nombre_servicio']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Precio</label>
                        <input type="number" step="0.01" class="form-control" name="precio" value="<?php echo $servicioEditar['precio']; ?>" required>
                    </div>
                    <button type="submit" name="editar" class="btn btn-primary">Guardar Cambios</button>
                    <a href="servicios.php" class="btn btn-info">Cancelar</a>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Agregar Nuevo Servicio</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Nombre del servicio</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label>Precio</label>
                        <input type="number" step="0.01" class="form-control" name="precio" required>
                    </div>
                    <button type="submit" name="agregar" class="btn btn-primary">Agregar Servicio</button>
                </form>
            </div>
        </div>

        <!-- Buscador -->
        <div class="mb-4">
            <form method="GET" class="d-flex">
                <input type="text" name="buscar" class="form-control me-2" placeholder="Buscar servicio..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn btn-info">Buscar</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">Lista de Servicios</div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $fila['id']; ?></td>
                            <td><?php echo $fila['nombre_servicio']; ?></td>
                            <td><?php echo $fila['precio']; ?> €</td>
                            <td>
                                <a href="servicios.php?edit_id=<?php echo $fila['id']; ?>" class="btn btn-info btn-sm">Editar</a>
                                <a href="servicios.php?delete_id=<?php echo $fila['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que quieres eliminar este servicio?');">Eliminar</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div> <!-- Fin del contenido principal -->
</div> <!-- Fin del contenedor flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
