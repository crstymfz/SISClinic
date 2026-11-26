<?php

require_once 'conexion.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

// --- Conteos generales ---

// Pacientes
$res = mysqli_query($con, "SELECT COUNT(*) AS total FROM pacientes");
$total_pacientes = (int) ($res ? mysqli_fetch_assoc($res)['total'] : 0);

// Evaluaciones
$res = mysqli_query($con, "SELECT COUNT(*) AS total FROM evaluaciones");
$total_evaluaciones = (int) ($res ? mysqli_fetch_assoc($res)['total'] : 0);

// Sesiones
$res = mysqli_query($con, "SELECT COUNT(*) AS total FROM sesiones");
$total_sesiones = (int) ($res ? mysqli_fetch_assoc($res)['total'] : 0);

// Profesionales activos (fisioterapeutas)
$res = mysqli_query($con, "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'Fisioterapeuta' AND estado = 1");
$total_fisios = (int) ($res ? mysqli_fetch_assoc($res)['total'] : 0);

// --- Consultas para alertas clínicas ---

// 1) Pacientes sin sesión en los últimos 30 días
$res = mysqli_query($con, "
    SELECT p.id_paciente, p.nombres, p.apellidos,
           MAX(s.fecha) AS ultima_sesion
    FROM pacientes p
    LEFT JOIN sesiones s ON s.id_paciente = p.id_paciente
    GROUP BY p.id_paciente, p.nombres, p.apellidos
    HAVING ultima_sesion IS NULL OR ultima_sesion < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY ultima_sesion ASC
    LIMIT 5
");
$alertas_sin_sesion = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

// 2) Pacientes con dolor alto en su última sesión (dolor_inicial >= 7)
$res = mysqli_query($con, "
    SELECT p.id_paciente, p.nombres, p.apellidos,
           s.fecha, s.dolor_inicial, s.dolor_final
    FROM pacientes p
    INNER JOIN sesiones s ON s.id_sesion = (
        SELECT id_sesion
        FROM sesiones
        WHERE id_paciente = p.id_paciente
        ORDER BY fecha DESC, id_sesion DESC
        LIMIT 1
    )
    WHERE s.dolor_inicial >= 7
    ORDER BY s.dolor_inicial DESC, s.fecha DESC
    LIMIT 5
");
$alertas_dolor_alto = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

// 3) Pacientes sin evaluación
$res = mysqli_query($con, "
    SELECT p.id_paciente, p.nombres, p.apellidos, p.fecha_registro
    FROM pacientes p
    LEFT JOIN evaluaciones e ON e.id_paciente = p.id_paciente
    WHERE e.id_evaluacion IS NULL
    ORDER BY p.fecha_registro DESC
    LIMIT 5
");
$alertas_sin_evaluacion = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

// --- Datos para gráficos ---

// Pacientes por sexo
$res = mysqli_query($con, "
    SELECT sexo, COUNT(*) AS total
    FROM pacientes
    GROUP BY sexo
");
$pacientes_sexo = ['Femenino' => 0, 'Masculino' => 0];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $pacientes_sexo[$row['sexo']] = (int) $row['total'];
    }
}

// Sesiones por mes (últimos 6 meses)
$res = mysqli_query($con, "
    SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(*) AS total
    FROM sesiones
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes
");
$sesiones_mes = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $sesiones_mes[$row['mes']] = (int) $row['total'];
    }
}

// Rellenar meses vacíos
$labels_sesiones = [];
$valores_sesiones = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $labels_sesiones[] = date('M Y', strtotime("-$i months"));
    $valores_sesiones[] = $sesiones_mes[$mes] ?? 0;
}

// Diagnósticos más frecuentes (Top 5)
$res = mysqli_query($con, "
    SELECT diagnostico_medico, COUNT(*) AS total
    FROM pacientes
    WHERE diagnostico_medico IS NOT NULL 
      AND diagnostico_medico != '' 
      AND diagnostico_medico != '—'
    GROUP BY diagnostico_medico
    ORDER BY total DESC
    LIMIT 5
");
$top_diagnosticos = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $top_diagnosticos[] = $row;
    }
}

// Preparar datos para el gráfico (cortamos textos muy largos)
$labels_diag = [];
$valores_diag = [];
foreach ($top_diagnosticos as $d) {
    $labels_diag[] = mb_strimwidth($d['diagnostico_medico'], 0, 30, '...');
    $valores_diag[] = (int) $d['total'];
}

// Pasar datos a JS en formato JSON
$chart_pacientes_sexo = json_encode([
    'labels' => ['Femenino', 'Masculino'],
    'valores' => [$pacientes_sexo['Femenino'], $pacientes_sexo['Masculino']],
]);
$chart_sesiones_mes = json_encode([
    'labels' => $labels_sesiones,
    'valores' => $valores_sesiones,
]);
$chart_diagnosticos = json_encode([
    'labels' => $labels_diag,
    'valores' => $valores_diag,
]);

// --- Últimos 5 pacientes ---
$ultimos_pacientes = mysqli_query($con, "
    SELECT id_paciente, nombres, apellidos, telefono, fecha_registro
    FROM pacientes
    ORDER BY fecha_registro DESC
    LIMIT 5
");

// --- Últimas 5 sesiones ---
$ultimas_sesiones = mysqli_query($con, "
    SELECT s.id_sesion, s.fecha, s.dolor_inicial, s.dolor_final,
           p.id_paciente, p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos,
           u.nombre_completo AS fisioterapeuta
    FROM sesiones s
    INNER JOIN pacientes p ON p.id_paciente = s.id_paciente
    INNER JOIN usuarios  u ON u.id_usuario  = s.id_usuario
    ORDER BY s.fecha DESC, s.id_sesion DESC
    LIMIT 5
");

// Helper de color de dolor
function color_dolor(int $dolor): string {
    if ($dolor <= 3) return 'success';
    if ($dolor <= 6) return 'warning';
    return 'danger';
}

// Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="container-fluid">

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <!-- Bienvenida -->
    <div class="mb-4">
        <h1 class="page-title">
            Bienvenido, <?= htmlspecialchars($nombre_usuario) ?>
        </h1>
        <p class="page-subtitle mb-0">
            Panel principal del sistema de gestión clínica de fisioterapia.
        </p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-3">
            <a href="<?= BASE_URL ?>/modules/pacientes/index.php" class="text-decoration-none">
                <div class="card stat-card stat-card--teal">
                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-info">
                        <small>Pacientes</small>
                        <strong><?= $total_pacientes ?></strong>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="<?= BASE_URL ?>/modules/evaluaciones/index.php" class="text-decoration-none">
                <div class="card stat-card stat-card--emerald">
                    <div class="stat-icon"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                    <div class="stat-info">
                        <small>Evaluaciones</small>
                        <strong><?= $total_evaluaciones ?></strong>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <a href="<?= BASE_URL ?>/modules/sesiones/index.php" class="text-decoration-none">
                <div class="card stat-card stat-card--violet">
                    <div class="stat-icon"><i class="bi bi-calendar3"></i></div>
                    <div class="stat-info">
                        <small>Sesiones</small>
                        <strong><?= $total_sesiones ?></strong>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card stat-card stat-card--blue">
                <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
                <div class="stat-info">
                    <small>Profesionales activos</small>
                    <strong><?= $total_fisios ?></strong>
                </div>
            </div>
        </div>

    </div>

    <!-- Fila: últimos pacientes + últimas sesiones -->
    <div class="row g-3 mb-4">

        <!-- Últimos pacientes -->
        <div class="col-lg-6">
            <div class="card card-section card-section--teal h-100">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-person-plus-fill"></i></span>
                    <strong>Últimos pacientes registrados</strong>
                    <a href="<?= BASE_URL ?>/modules/pacientes/index.php"
                       class="ms-auto small text-decoration-none"
                       style="color: var(--color-primary);">
                        Ver todos <i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>

                <?php if (!$ultimos_pacientes || mysqli_num_rows($ultimos_pacientes) === 0): ?>

                    <div class="card-body empty-state">
                        <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                        <p>Aún no hay pacientes registrados.</p>
                        <small>
                            <a href="<?= BASE_URL ?>/modules/pacientes/registrar.php"
                               style="color: var(--color-primary);">
                                Registrar el primero
                            </a>
                        </small>
                    </div>

                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                <?php while ($p = mysqli_fetch_assoc($ultimos_pacientes)): ?>
                                    <tr>
                                        <td style="width: 42px;">
                                            <div class="mini-avatar">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $p['id_paciente'] ?>"
                                               style="color: var(--color-dark); font-weight: 600;">
                                                <?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?>
                                            </a>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($p['telefono']) ?>
                                            </small>
                                        </td>
                                        <td class="text-end" style="width: 90px;">
                                            <small class="text-muted">
                                                <?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha_registro']))) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>
            </div>
        </div>

        <!-- Últimas sesiones -->
        <div class="col-lg-6">
            <div class="card card-section card-section--violet h-100">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-calendar-check-fill"></i></span>
                    <strong>Últimas sesiones registradas</strong>
                    <a href="<?= BASE_URL ?>/modules/sesiones/index.php"
                       class="ms-auto small text-decoration-none"
                       style="color: var(--color-primary);">
                        Ver todas <i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>

                <?php if (!$ultimas_sesiones || mysqli_num_rows($ultimas_sesiones) === 0): ?>

                    <div class="card-body empty-state">
                        <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                        <p>Aún no hay sesiones registradas.</p>
                        <small>
                            <a href="<?= BASE_URL ?>/modules/sesiones/registrar.php"
                               style="color: var(--color-primary);">
                                Registrar la primera
                            </a>
                        </small>
                    </div>

                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                <?php while ($s = mysqli_fetch_assoc($ultimas_sesiones)): ?>
                                    <?php $mejora = (int) $s['dolor_inicial'] - (int) $s['dolor_final']; ?>
                                    <tr>
                                        <td>
                                            <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $s['id_paciente'] ?>"
                                               style="color: var(--color-dark); font-weight: 600;">
                                                <?= htmlspecialchars($s['paciente_apellidos'] . ', ' . $s['paciente_nombres']) ?>
                                            </a>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($s['fisioterapeuta']) ?>
                                            </small>
                                        </td>
                                        <td style="width: 100px;">
                                            <span class="badge bg-<?= color_dolor((int) $s['dolor_inicial']) ?>">
                                                <?= (int) $s['dolor_inicial'] ?>
                                            </span>
                                            <i class="bi bi-arrow-right mx-1" style="font-size: 12px; color: var(--color-muted);"></i>
                                            <span class="badge bg-<?= color_dolor((int) $s['dolor_final']) ?>">
                                                <?= (int) $s['dolor_final'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end" style="width: 90px;">
                                            <small class="text-muted">
                                                <?= htmlspecialchars(date('d/m/Y', strtotime($s['fecha']))) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Fila: gráficos -->
    <div class="row g-3 mb-4">

        <div class="col-lg-5">
            <div class="card card-section card-section--coral h-100">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-pie-chart-fill"></i></span>
                    <strong>Pacientes por sexo</strong>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="graficoSexo"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-section card-section--emerald h-100">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-bar-chart-fill"></i></span>
                    <strong>Sesiones por mes (últimos 6 meses)</strong>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="graficoSesiones"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Fila: Gráfico de diagnósticos -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card card-section card-section--teal">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-clipboard2-pulse-fill"></i></span>
                    <strong>Diagnósticos médicos más frecuentes</strong>
                    <span class="ms-auto small text-muted">Top 5 del sistema</span>
                </div>
                <div class="card-body">
                    <?php if (empty($top_diagnosticos)): ?>
                        <div class="empty-state" style="padding: 30px;">
                            <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 20px;">
                                <i class="bi bi-clipboard2-x"></i>
                            </div>
                            <p style="font-size: 13px;">Aún no hay diagnósticos registrados.</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-container" style="height: 280px;">
                            <canvas id="graficoDiagnosticos"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila: alertas clínicas -->
    <div class="row g-3 mb-4">

        <!-- Alerta 1: Sin sesión en 30 días -->
        <div class="col-lg-4">
            <div class="card card-section card-section--amber h-100">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-clock-history"></i></span>
                    <strong>Sin sesión reciente</strong>
                    <span class="badge bg-warning text-dark ms-auto">
                        <?= count($alertas_sin_sesion) ?>
                    </span>
                </div>

                <?php if (empty($alertas_sin_sesion)): ?>
                    <div class="card-body empty-state" style="padding: 24px 20px;">
                        <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 22px;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <p style="font-size: 13px;">Todos los pacientes tienen sesiones recientes.</p>
                    </div>
                <?php else: ?>
                    <div class="card-body p-0">
                        <ul class="alert-list">
                            <?php foreach ($alertas_sin_sesion as $a): ?>
                                <li>
                                    <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $a['id_paciente'] ?>"
                                       class="alert-list-link">
                                        <i class="bi bi-person-circle"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($a['nombres'] . ' ' . $a['apellidos']) ?></strong>
                                            <small>
                                                <?php if ($a['ultima_sesion']): ?>
                                                    Última: <?= htmlspecialchars(date('d/m/Y', strtotime($a['ultima_sesion']))) ?>
                                                <?php else: ?>
                                                    Sin sesiones registradas
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alerta 2: Dolor alto -->
        <div class="col-lg-4">
            <div class="card card-section card-section--amber h-100">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-thermometer-high"></i></span>
                    <strong>Dolor elevado reciente</strong>
                    <span class="badge bg-warning text-dark ms-auto">
                        <?= count($alertas_dolor_alto) ?>
                    </span>
                </div>

                <?php if (empty($alertas_dolor_alto)): ?>
                    <div class="card-body empty-state" style="padding: 24px 20px;">
                        <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 22px;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <p style="font-size: 13px;">Ningún paciente con dolor elevado.</p>
                    </div>
                <?php else: ?>
                    <div class="card-body p-0">
                        <ul class="alert-list">
                            <?php foreach ($alertas_dolor_alto as $a): ?>
                                <li>
                                    <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $a['id_paciente'] ?>"
                                       class="alert-list-link">
                                        <i class="bi bi-person-circle"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($a['nombres'] . ' ' . $a['apellidos']) ?></strong>
                                            <small>
                                                <?= htmlspecialchars(date('d/m/Y', strtotime($a['fecha']))) ?> ·
                                                Dolor: <strong><?= (int) $a['dolor_inicial'] ?>/10</strong>
                                            </small>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alerta 3: Sin evaluación -->
        <div class="col-lg-4">
            <div class="card card-section card-section--amber h-100">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-clipboard2-x"></i></span>
                    <strong>Sin evaluación inicial</strong>
                    <span class="badge bg-warning text-dark ms-auto">
                        <?= count($alertas_sin_evaluacion) ?>
                    </span>
                </div>

                <?php if (empty($alertas_sin_evaluacion)): ?>
                    <div class="card-body empty-state" style="padding: 24px 20px;">
                        <div class="empty-state-icon" style="width: 48px; height: 48px; font-size: 22px;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <p style="font-size: 13px;">Todos los pacientes tienen evaluación.</p>
                    </div>
                <?php else: ?>
                    <div class="card-body p-0">
                        <ul class="alert-list">
                            <?php foreach ($alertas_sin_evaluacion as $a): ?>
                                <li>
                                    <a href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $a['id_paciente'] ?>"
                                       class="alert-list-link">
                                        <i class="bi bi-person-circle"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($a['nombres'] . ' ' . $a['apellidos']) ?></strong>
                                            <small>
                                                Registrado: <?= htmlspecialchars(date('d/m/Y', strtotime($a['fecha_registro']))) ?>
                                            </small>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Accesos rápidos -->
    <div class="row g-3">

        <div class="col-12">
            <div class="card card-section card-section--blue">
                <div class="card-header">
                    <span class="section-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                    <strong>Accesos rápidos</strong>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-3 col-6">
                            <a href="<?= BASE_URL ?>/modules/pacientes/registrar.php"
                               class="quick-action">
                                <i class="bi bi-person-plus-fill"></i>
                                <span>Nuevo paciente</span>
                            </a>
                        </div>

                        <div class="col-md-3 col-6">
                            <a href="<?= BASE_URL ?>/modules/evaluaciones/registrar.php"
                               class="quick-action">
                                <i class="bi bi-clipboard2-plus-fill"></i>
                                <span>Nueva evaluación</span>
                            </a>
                        </div>

                        <div class="col-md-3 col-6">
                            <a href="<?= BASE_URL ?>/modules/sesiones/registrar.php"
                               class="quick-action">
                                <i class="bi bi-calendar-plus-fill"></i>
                                <span>Nueva sesión</span>
                            </a>
                        </div>

                        <?php if ($rol_usuario === 'Administrador'): ?>
                            <div class="col-md-3 col-6">
                                <a href="<?= BASE_URL ?>/modules/usuarios/registrar.php"
                                   class="quick-action">
                                    <i class="bi bi-person-badge-fill"></i>
                                    <span>Nuevo usuario</span>
                                </a>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
// Datos que vienen desde PHP (JSON)
const dataSexo         = <?= $chart_pacientes_sexo ?>;
const dataSesiones     = <?= $chart_sesiones_mes ?>;
const dataDiagnosticos = <?= $chart_diagnosticos ?>;

document.addEventListener('DOMContentLoaded', function () {

    // Paleta base del sistema
    const colorTeal    = '#38a3a5';
    const colorViolet  = '#7c6fbc';
    const colorCoral   = '#e76f51';
    const colorEmerald = '#57cc99';
    const colorMuted   = '#5a7d92';

    // --- Gráfico de dona: pacientes por sexo ---
    const ctxSexo = document.getElementById('graficoSexo');
    if (ctxSexo) {
        const totalSexo = dataSexo.valores.reduce((a, b) => a + b, 0);

        new Chart(ctxSexo, {
            type: 'doughnut',
            data: {
                labels: dataSexo.labels,
                datasets: [{
                    data: dataSexo.valores,
                    backgroundColor: [colorCoral, colorTeal],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            font: { size: 12, family: "'Inter', sans-serif" },
                            color: colorMuted,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const valor = context.parsed;
                                const pct = totalSexo > 0 ? Math.round((valor / totalSexo) * 100) : 0;
                                return ' ' + valor + ' paciente' + (valor === 1 ? '' : 's') + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // --- Gráfico de barras: sesiones por mes ---
    const ctxSesiones = document.getElementById('graficoSesiones');
    if (ctxSesiones) {
        new Chart(ctxSesiones, {
            type: 'bar',
            data: {
                labels: dataSesiones.labels,
                datasets: [{
                    label: 'Sesiones',
                    data: dataSesiones.valores,
                    backgroundColor: colorEmerald,
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.parsed.y + ' sesión' + (context.parsed.y === 1 ? '' : 'es');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: colorMuted,
                            font: { size: 11, family: "'Inter', sans-serif" }
                        },
                        grid: { color: 'rgba(90, 125, 146, 0.10)' }
                    },
                    x: {
                        ticks: {
                            color: colorMuted,
                            font: { size: 11, family: "'Inter', sans-serif" }
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // --- Gráfico de barras horizontales: Diagnósticos ---
    const ctxDiag = document.getElementById('graficoDiagnosticos');
    if (ctxDiag) {
        new Chart(ctxDiag, {
            type: 'bar',
            data: {
                labels: dataDiagnosticos.labels,
                datasets: [{
                    label: 'Pacientes',
                    data: dataDiagnosticos.valores,
                    backgroundColor: colorTeal,
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 28
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.parsed.x + ' paciente' + (context.parsed.x === 1 ? '' : 's');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: colorMuted,
                            font: { size: 11, family: "'Inter', sans-serif" }
                        },
                        grid: { color: 'rgba(90, 125, 146, 0.10)' }
                    },
                    y: {
                        ticks: {
                            color: '#1a4459',
                            font: { size: 12, family: "'Inter', sans-serif", weight: '500' }
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

});
</script>

<?php require_once 'includes/footer.php'; ?>