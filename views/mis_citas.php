<?php
session_start();
include("../config/db.php");

// Verificar sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$id_cliente = $_SESSION['id_usuario'];

$db = new Database();
$conn = $db->connect();

// 🟡 Manejar cancelación de cita
$mensaje = "";
if (isset($_POST['cancelar_cita'])) {
    $id_cita = intval($_POST['id_cita']);
    $stmt = $conn->prepare("UPDATE citas SET estado='cancelada' WHERE id=? AND id_cliente=? AND estado='pendiente'");
    $stmt->bind_param("ii", $id_cita, $id_cliente);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $mensaje = "Cita cancelada correctamente ✅";
        echo "<script>
                setTimeout(() => { location.reload(); }, 1000);
              </script>";
    } else {
        $mensaje = "No se pudo cancelar la cita ❌ (ya está completada o cancelada)";
    }
}

// 🔹 Obtener citas del cliente
$citas_result = $conn->query("
    SELECT c.id, s.nombre_servicio, c.fecha, c.hora, c.estado
    FROM citas c
    JOIN servicios s ON c.id_servicio = s.id
    WHERE c.id_cliente = $id_cliente
    ORDER BY c.fecha ASC, c.hora ASC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis Citas - Peluquería</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background-color: #e3f2fd; }
.sidebar { background-color: #0d6efd; min-height: 100vh; color: white; padding-top: 20px; width: 220px; }
.sidebar a { color: white; display: block; padding: 10px 15px; margin: 5px 0; text-decoration: none; border-radius: 5px; }
.sidebar a:hover { background-color: #0b5ed7; }
.card { border: none; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.card-header { background-color: #0d6efd; color: white; font-weight: bold; }
.table th, .table td { vertical-align: middle; }
.btn-cancelar { background-color: #dc3545; color: white; border: none; border-radius: 5px; padding: 5px 10px; }
.btn-cancelar:hover { background-color: #c82333; }
</style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <h3>Peluquería</h3>
        <a href="cliente_dashboard.php">Dashboard</a>
        <a href="mis_citas.php">Mis Citas</a>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <!-- Contenido principal -->
    <div class="flex-grow-1 p-4">
        <div class="card">
            <div class="card-header">Mis Citas</div>
            <div class="card-body">
                <?php if($mensaje): ?>
                    <div class="alert alert-info text-center"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <?php if($citas_result->num_rows > 0): ?>
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Servicio</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($cita = $citas_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cita['nombre_servicio']); ?></td>
                                    <td><?php echo $cita['fecha']; ?></td>
                                    <td><?php echo $cita['hora']; ?></td>
                                    <td>
                                        <?php
                                            $estado = ucfirst($cita['estado']);
                                            $color = match($cita['estado']) {
                                                'pendiente' => 'warning',
                                                'completada' => 'success',
                                                'cancelada' => 'danger',
                                                default => 'secondary'
                                            };
                                            echo "<span class='badge bg-$color'>$estado</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($cita['estado'] === 'pendiente'): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirmarCancelacion();">
                                                <input type="hidden" name="id_cita" value="<?php echo $cita['id']; ?>">
                                                <button type="submit" name="cancelar_cita" class="btn-cancelar">
                                                    <i class="fas fa-times"></i> Cancelar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No tienes citas programadas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarCancelacion() {
    return confirm("¿Seguro que deseas cancelar esta cita?");
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
