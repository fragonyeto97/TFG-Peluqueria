<?php
include __DIR__ . '/../includes/header.php';
include("../config/db.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Conexión a DB
$db = new Database();
$conn = $db->connect();

// Mensaje
$mensaje = "";

// Obtener clientes y servicios para dropdowns
$clientesRes = $conn->query("SELECT * FROM usuarios WHERE rol = 'cliente' ORDER BY nombre ASC, apellidos ASC");
$clientesArr = $clientesRes->fetch_all(MYSQLI_ASSOC);

$serviciosRes = $conn->query("SELECT * FROM servicios ORDER BY nombre_servicio ASC");
$serviciosArr = $serviciosRes->fetch_all(MYSQLI_ASSOC);

// Agregar cita
if (isset($_POST['agregar'])) {
    $id_cliente = $_POST['id_cliente'];
    $id_servicio = $_POST['id_servicio'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    $stmt = $conn->prepare("INSERT INTO citas (id_cliente, id_servicio, fecha, hora, estado) VALUES (?, ?, ?, ?, 'Pendiente')");
    $stmt->bind_param("iiss", $id_cliente, $id_servicio, $fecha, $hora);

    if ($stmt->execute()) {
        header("Location: citas.php?success=1");
        exit;
    } else {
        $mensaje = "Ocurrió un error al agregar la cita.";
    }
}

// Editar cita
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $id_cliente = $_POST['id_cliente'];
    $id_servicio = $_POST['id_servicio'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];

    $stmt = $conn->prepare("UPDATE citas SET id_cliente=?, id_servicio=?, fecha=?, hora=? WHERE id=?");
    $stmt->bind_param("iissi", $id_cliente, $id_servicio, $fecha, $hora, $id);

    if ($stmt->execute()) {
        header("Location: citas.php?success=2");
        exit;
    } else {
        $mensaje = "Ocurrió un error al actualizar la cita.";
    }
}

// Obtener cita para editar
$citaEditar = null;
if (isset($_GET['edit_id'])) {
    $idEditar = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM citas WHERE id=?");
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $res = $stmt->get_result();
    $citaEditar = $res->fetch_assoc();
}

// Cambiar estado de cita
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $accion = $_GET['accion'];
    $id = intval($_GET['id']);
    $nuevoEstado = null;

    if ($accion === 'completar') {
        $nuevoEstado = 'Completada';
    } elseif ($accion === 'cancelar') {
        $nuevoEstado = 'Cancelada';
    } elseif ($accion === 'pendiente') {
        $nuevoEstado = 'Pendiente';
    }

    if ($nuevoEstado !== null) {
        $stmt = $conn->prepare("UPDATE citas SET estado = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $nuevoEstado, $id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: citas.php?success=4");
        exit;
    }
}

// Eliminar cita
if (isset($_GET['delete_id'])) {
    $idEliminar = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM citas WHERE id=?");
    $stmt->bind_param("i", $idEliminar);
    $stmt->execute();
    header("Location: citas.php?success=3");
    exit;
}

// Búsqueda
$busqueda = "";
if (isset($_GET['buscar'])) {
    $busqueda = $_GET['buscar'];
    $sql = "
        SELECT citas.id, usuarios.nombre AS cliente, usuarios.apellidos AS apellidos,
               servicios.nombre_servicio AS servicio, citas.fecha, citas.hora, citas.estado
        FROM citas
        INNER JOIN usuarios ON citas.id_cliente = usuarios.id
        INNER JOIN servicios ON citas.id_servicio = servicios.id
        WHERE (usuarios.nombre LIKE ? OR usuarios.apellidos LIKE ? OR servicios.nombre_servicio LIKE ? OR citas.fecha LIKE ? OR citas.hora LIKE ?)
        ORDER BY citas.fecha ASC, citas.hora ASC
    ";
    $like = "%$busqueda%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $resultado = $conn->query("
        SELECT citas.id, usuarios.nombre AS cliente, usuarios.apellidos AS apellidos,
               servicios.nombre_servicio AS servicio, citas.fecha, citas.hora, citas.estado
        FROM citas
        INNER JOIN usuarios ON citas.id_cliente = usuarios.id
        INNER JOIN servicios ON citas.id_servicio = servicios.id
        ORDER BY citas.fecha ASC, citas.hora ASC
    ");
}

// Mensajes
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 1: $mensaje = "Cita agregada correctamente ✅"; break;
        case 2: $mensaje = "Cita actualizada correctamente ✅"; break;
        case 3: $mensaje = "Cita eliminada correctamente ✅"; break;
        case 4: $mensaje = "Estado de la cita actualizado correctamente ✅"; break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Citas - Peluquería</title>
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
                <span class="navbar-text">Usuario: <?php echo $_SESSION['usuario']; ?> (<?php echo $_SESSION['rol']; ?>)</span>
            </div>
        </nav>

        <?php if($mensaje): ?>
            <div class="alert mb-4"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if($citaEditar): ?>
        <div class="card mb-4">
            <div class="card-header">Editar Cita</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $citaEditar['id']; ?>">
                    <div class="mb-3">
                        <label>Cliente</label>
                        <select class="form-control" name="id_cliente" required>
                            <?php foreach($clientesArr as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php if($c['id']==$citaEditar['id_cliente']) echo 'selected'; ?>>
                                    <?php echo $c['nombre'] . ' ' . $c['apellidos']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Servicio</label>
                        <select class="form-control" name="id_servicio" required>
                            <?php foreach($serviciosArr as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php if($s['id']==$citaEditar['id_servicio']) echo 'selected'; ?>>
                                    <?php echo $s['nombre_servicio']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Fecha</label>
                        <input type="date" class="form-control" name="fecha" value="<?php echo $citaEditar['fecha']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Hora</label>
                        <input type="time" class="form-control" name="hora" value="<?php echo $citaEditar['hora']; ?>" required>
                    </div>
                    <button type="submit" name="editar" class="btn btn-primary">Guardar Cambios</button>
                    <a href="citas.php" class="btn btn-info">Cancelar</a>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Agregar Nueva Cita</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Cliente</label>
                        <select class="form-control" name="id_cliente" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach($clientesArr as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre'] . ' ' . $c['apellidos']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Servicio</label>
                        <select class="form-control" name="id_servicio" required>
                            <option value="">Seleccione un servicio</option>
                            <?php foreach($serviciosArr as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['nombre_servicio']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Fecha</label>
                        <input type="date" class="form-control" name="fecha" required>
                    </div>
                    <div class="mb-3">
                        <label>Hora</label>
                        <input type="time" class="form-control" name="hora" required>
                    </div>
                    <button type="submit" name="agregar" class="btn btn-primary">Agregar Cita</button>
                </form>
            </div>
        </div>

        <!-- Buscador -->
        <div class="mb-4">
            <form method="GET" class="d-flex">
                <input type="text" name="buscar" class="form-control me-2" placeholder="Buscar cita..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn btn-info">Buscar</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">Lista de Citas</div>
            <div class="card-body">
                <table class="table table-striped">
                  <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                  </thead>

                    <tbody>
                        <?php while($row = $resultado->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['cliente']; ?></td>
        <td><?php echo $row['servicio']; ?></td>
        <td><?php echo $row['fecha']; ?></td>
        <td><?php echo $row['hora']; ?></td>
        <td>
           <span class="badge 
    <?php 
        $estado = strtolower(trim($row['estado']));
        echo ($estado === 'completada') ? 'bg-success' : 
             (($estado === 'cancelada') ? 'bg-danger' : 'bg-warning');
    ?>">
    <?php echo ucfirst($estado); ?>
</span>

        </td>
        <td>
            <?php if ($row['estado'] != 'Completada'): ?>
                <a href="citas.php?accion=completar&id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">✅ Completar</a>
            <?php endif; ?>

            <?php if ($row['estado'] != 'Cancelada'): ?>
                <a href="citas.php?accion=cancelar&id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">❌ Cancelar</a>
            <?php endif; ?>

            <?php if ($row['estado'] != 'Pendiente'): ?>
                <a href="citas.php?accion=pendiente&id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">🔁 Pendiente</a>
            <?php endif; ?>

            <a href="citas.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">Editar</a>
            <a href="citas.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar esta cita?');">Eliminar</a>
        </td>
    </tr>
<?php endwhile; ?>

                    </tbody>
                </table>
            </div>
        </div>

    </div> <!-- fin contenido principal -->
</div> <!-- fin flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
