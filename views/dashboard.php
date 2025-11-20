<?php
session_start();
include("../config/db.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$usuario = $_SESSION['usuario'];
$rol = $_SESSION['rol'];

$db = new Database();
$conn = $db->connect();

// Consultas de resumen
$total_clientes = $conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol='cliente'")->fetch_assoc()['total'];
$total_servicios = $conn->query("SELECT COUNT(*) AS total FROM servicios")->fetch_assoc()['total'];
$total_citas_pendientes = $conn->query("SELECT COUNT(*) AS total FROM citas WHERE estado='pendiente'")->fetch_assoc()['total'];
$total_citas_completadas = $conn->query("SELECT COUNT(*) AS total FROM citas WHERE estado='completada'")->fetch_assoc()['total'];

// Selección de mes y año
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

// Facturación del mes seleccionado
$facturacion_mes = $conn->query("
    SELECT SUM(s.precio) AS total
    FROM citas c
    INNER JOIN servicios s ON c.id_servicio = s.id
    WHERE c.estado = 'completada'
      AND MONTH(c.fecha) = $mes
      AND YEAR(c.fecha) = $anio
")->fetch_assoc()['total'];
if (!$facturacion_mes) $facturacion_mes = 0;

// Facturación anual del año seleccionado
$facturacion_anual = $conn->query("
    SELECT SUM(s.precio) AS total
    FROM citas c
    INNER JOIN servicios s ON c.id_servicio = s.id
    WHERE c.estado = 'completada'
      AND YEAR(c.fecha) = $anio
")->fetch_assoc()['total'];
if (!$facturacion_anual) $facturacion_anual = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard - Peluquería</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <span class="navbar-text text-white">Bienvenido, <?php echo htmlspecialchars($usuario); ?></span>
            </div>
        </nav>

        <div class="card mb-4">
            <div class="card-header">Panel Principal</div>
            <div class="card-body">
                <h4>Bienvenido, <?php echo htmlspecialchars($usuario); ?>!</h4>
                <p><strong>Rol:</strong> <?php echo htmlspecialchars($rol); ?></p>

                <hr>
                <p>Desde aquí puedes acceder a la gestión de los distintos apartados:</p>
                <ul>
                    <li><a href="clientes.php">Gestión de Clientes</a></li>
                    <li><a href="citas.php">Gestión de Citas</a></li>
                    <li><a href="servicios.php">Gestión de Servicios</a></li>
                </ul>
            </div>
        </div>

        <!-- 📊 Resumen de datos -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="summary-card">
                    <h3><?php echo $total_clientes; ?></h3>
                    <p>Clientes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h3><?php echo $total_servicios; ?></h3>
                    <p>Servicios</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h3><?php echo $total_citas_pendientes; ?></h3>
                    <p>Citas Pendientes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h3><?php echo $total_citas_completadas; ?></h3>
                    <p>Citas Completadas</p>
                </div>
            </div>
        </div>

        <!-- Apartado Facturación -->
        <h4 class="mb-3">Facturación</h4>

        <!-- Selector de mes y año -->
        <form method="GET" class="mb-3 d-flex align-items-center gap-2">
            <label for="mes">Mes:</label>
            <select name="mes" id="mes" class="form-select" style="width: auto;">
                <?php
                for ($m=1; $m<=12; $m++) {
                    $selected = ($mes == $m) ? 'selected' : '';
                    echo "<option value='$m' $selected>".date('F', mktime(0,0,0,$m,1))."</option>";
                }
                ?>
            </select>

            <label for="anio">Año:</label>
            <select name="anio" id="anio" class="form-select" style="width: auto;">
                <?php
                $anio_inicio = 2023;
                $anio_actual = date('Y');
                for ($a=$anio_inicio; $a<=$anio_actual; $a++) {
                    $selected = ($anio == $a) ? 'selected' : '';
                    echo "<option value='$a' $selected>$a</option>";
                }
                ?>
            </select>

            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="summary-card">
                    <h3><?php echo number_format($facturacion_mes, 2, ',', '.'); ?>€</h3>
                    <p>Facturación Mes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h3><?php echo number_format($facturacion_anual, 2, ',', '.'); ?>€</h3>
                    <p>Facturación Año</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
