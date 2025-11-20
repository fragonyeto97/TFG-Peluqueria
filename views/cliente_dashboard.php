<?php
session_start();
include("../config/db.php");

// Verificar sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$rol = $_SESSION['rol'];
$id_cliente = $_SESSION['id_usuario']; // Usamos id de la tabla usuarios

$db = new Database();
$conn = $db->connect();

// Consultas resumen del cliente
$total_mis_citas = $conn->query("SELECT COUNT(*) AS total FROM citas WHERE id_cliente = $id_cliente")->fetch_assoc()['total'] ?? 0;
$total_citas_pendientes = $conn->query("SELECT COUNT(*) AS total FROM citas WHERE id_cliente = $id_cliente AND estado='pendiente'")->fetch_assoc()['total'] ?? 0;
$total_citas_completadas = $conn->query("SELECT COUNT(*) AS total FROM citas WHERE id_cliente = $id_cliente AND estado='completada'")->fetch_assoc()['total'] ?? 0;

// Obtener servicios disponibles
$servicios_result = $conn->query("SELECT * FROM servicios ORDER BY nombre_servicio ASC");

// Manejar creación de cita
$mensaje = "";
if (isset($_POST['crear_cita'])) {
    $servicio_id = intval($_POST['servicio']);
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (!$servicio_id || !$fecha || !$hora) {
        $mensaje = "Debes completar todos los campos ❌";
    } else {
        try {
            $ahora = new DateTime();
            $fecha_hora_seleccionada = new DateTime("$fecha $hora:00");

            if ($fecha_hora_seleccionada < $ahora) {
                $mensaje = "No puedes reservar una cita en una fecha u hora pasada ❌";
            } else {
                // Verificar horario permitido
                $dia_semana = $fecha_hora_seleccionada->format('N'); // 1=Lunes ... 7=Domingo
                $hora_cita = $fecha_hora_seleccionada->format('H:i');

                // Definir horario laboral
                $hora_apertura = "09:00";
                $hora_cierre = "20:00";

                if ($dia_semana == 7) {
                    $mensaje = "La peluquería está cerrada los domingos ❌";
                } elseif ($hora_cita < $hora_apertura || $hora_cita > $hora_cierre) {
                    $mensaje = "La peluquería solo acepta citas entre $hora_apertura y $hora_cierre 🕘";
                } else {
                    // Verificar disponibilidad (ignorar canceladas)
                    $check = $conn->prepare("
                        SELECT COUNT(*) AS total FROM citas
                        WHERE fecha = ? 
                          AND hora = ? 
                          AND id_servicio = ? 
                          AND (LOWER(estado) = 'pendiente' OR LOWER(estado) = 'completada')
                    ");
                    $check->bind_param("ssi", $fecha, $hora, $servicio_id);
                    $check->execute();
                    $resultado_check = $check->get_result();
                    $row_check = $resultado_check->fetch_assoc();
                    $ocupadas = intval($row_check['total'] ?? 0);

                    if ($ocupadas > 0) {
                        $mensaje = "La hora seleccionada ya está ocupada ❌";
                    } else {
                        // Insertar cita
                        $stmt = $conn->prepare("
                            INSERT INTO citas (id_cliente, id_servicio, fecha, hora, estado)
                            VALUES (?, ?, ?, ?, 'pendiente')
                        ");
                        $stmt->bind_param("iiss", $id_cliente, $servicio_id, $fecha, $hora);
                        if ($stmt->execute()) {
                            $mensaje = "Cita creada correctamente ✅";
                            $total_mis_citas++;
                            $total_citas_pendientes++;
                        } else {
                            $mensaje = "Error al crear la cita ❌";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $mensaje = "Error al procesar la fecha u hora: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard Cliente - Peluquería</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background-color: #e3f2fd; }
.navbar { background-color: #0d6efd !important; }
.navbar .navbar-brand, .navbar .nav-link { color: white !important; }
.sidebar { background-color: #0d6efd; min-height: 100vh; color: white; padding-top: 20px; width: 220px; }
.sidebar a { color: white; display: block; padding: 10px 15px; margin: 5px 0; text-decoration: none; border-radius: 5px; }
.sidebar a:hover { background-color: #0b5ed7; }
.card { border: none; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.card-header { background-color: #0d6efd; color: white; font-weight: bold; }
.summary-card { text-align: center; background: white; border-radius: 10px; padding: 20px; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
.summary-card h3 { margin: 0; color: #0d6efd; font-size: 2rem; }
.summary-card p { margin: 5px 0 0; color: #555; font-weight: bold; }
.summary-card i { font-size: 2rem; margin-bottom: 10px; color: #0d6efd; }
</style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-3">
        <h3>Peluquería</h3>
        <a href="cliente_dashboard.php">Dashboard</a>
        <a href="mis_citas.php">Mis Citas</a>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <div class="flex-grow-1 p-4">
        <nav class="navbar mb-4">
            <div class="container-fluid">
                <span class="navbar-text text-white">Bienvenido, <?php echo htmlspecialchars($usuario); ?></span>
            </div>
        </nav>

        <div class="card mb-4">
            <div class="card-header">Panel Cliente</div>
            <div class="card-body">
                <h4>Bienvenido, <?php echo htmlspecialchars($usuario); ?>!</h4>
                <p><strong>Rol:</strong> <?php echo htmlspecialchars($rol); ?></p>
                <hr>
                <p>Desde aquí puedes crear tus citas:</p>

                <?php if($mensaje): ?>
                    <div class="alert alert-info"><?php echo $mensaje; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label>Servicio</label>
                        <select name="servicio" class="form-select" required>
                            <option value="">Selecciona un servicio</option>
                            <?php 
                            if ($servicios_result) $servicios_result->data_seek(0);
                            while($servicio = $servicios_result->fetch_assoc()): ?>
                                <option value="<?php echo $servicio['id']; ?>"><?php echo htmlspecialchars($servicio['nombre_servicio']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Hora</label>
                        <input 
                            type="time" 
                            name="hora" 
                            class="form-control" 
                            required 
                            min="09:00" 
                            max="20:00" 
                            step="1800"
                            title="Horario disponible: de 09:00 a 20:00"
                        >
                        <small class="text-muted">Horario disponible: de 09:00 a 20:00</small>
                    </div>
                    <button type="submit" name="crear_cita" class="btn btn-primary">Crear Cita</button>
                </form>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="summary-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo $total_mis_citas; ?></h3>
                    <p>Total Citas</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $total_citas_pendientes; ?></h3>
                    <p>Citas Pendientes</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $total_citas_completadas; ?></h3>
                    <p>Citas Completadas</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
