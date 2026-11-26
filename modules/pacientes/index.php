<?php
require_once __DIR__ . '/../../conexion.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// 2) Término de búsqueda (si viene por GET)
$busqueda = trim($_GET['q'] ?? '');

// 3) Consulta de pacientes
if ($busqueda !== '') {
    $termino = '%' . $busqueda . '%';

    $sql = "SELECT id_paciente, nombres, apellidos, telefono, correo, diagnostico_medico, fecha_registro
            FROM pacientes
            WHERE nombres LIKE ?
               OR apellidos LIKE ?
               OR telefono LIKE ?
               OR diagnostico_medico LIKE ?
            ORDER BY apellidos, nombres
            LIMIT 100";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $termino, $termino, $termino, $termino);
} else {
    $sql = "SELECT id_paciente, nombres, apellidos, telefono, correo, diagnostico_medico, fecha_registro
            FROM pacientes
            ORDER BY apellidos, nombres
            LIMIT 100";

    $stmt = mysqli_prepare($con, $sql);
}

mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$total_resultados = mysqli_num_rows($resultado);

// 4) Total general (sin filtro) para mostrar en la cabecera
$res_total = mysqli_query($con, "SELECT COUNT(*) AS total FROM pacientes");
$total_pacientes = (int) ($res_total ? mysqli_fetch_assoc($res_total)['total'] : 0);

// 5) Mensaje flash (una sola vez)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="container-fluid">

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <!-- Título de la página -->
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title">Pacientes</h1>
            <p class="page-subtitle mb-0">
                Gestión de pacientes registrados en el sistema.
            </p>
        </div>

        <a href="<?= BASE_URL ?>/modules/pacientes/registrar.php" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i>
            Registrar nuevo paciente
        </a>
    </div>

    <!-- Card de búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            name="q"
                            placeholder="Buscar por nombre, apellido, teléfono o diagnóstico"
                            value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>

                    <?php if ($busqueda !== ''): ?>
                        <a href="index.php" class="btn btn-light">
                            <i class="bi bi-x-lg"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Card con la tabla -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>
                <?php if ($busqueda !== ''): ?>
                    Resultados de búsqueda
                <?php else: ?>
                    Lista de pacientes
                <?php endif; ?>
            </strong>

            <small class="text-muted">
                <?php if ($busqueda !== ''): ?>
                    <?= $total_resultados ?> resultado<?= $total_resultados === 1 ? '' : 's' ?>
                    de <?= $total_pacientes ?> en total
                <?php else: ?>
                    <?= $total_pacientes ?> paciente<?= $total_pacientes === 1 ? '' : 's' ?> registrado<?= $total_pacientes === 1 ? '' : 's' ?>
                <?php endif; ?>
            </small>
        </div>

        <?php if ($total_resultados === 0): ?>

            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 40px; color: var(--color-muted);"></i>

                <p class="mt-3 mb-0" style="color: var(--color-muted);">
                    <?php if ($busqueda !== ''): ?>
                        No se encontraron pacientes que coincidan con
                        <strong>"<?= htmlspecialchars($busqueda) ?>"</strong>.
                    <?php else: ?>
                        Aún no hay pacientes registrados en el sistema.
                    <?php endif; ?>
                </p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nombre completo</th>
                            <th>Teléfono</th>
                            <th>Diagnóstico médico</th>
                            <th>Fecha de registro</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = mysqli_fetch_assoc($resultado)): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']) ?>
                                    </strong>
                                    <?php if (!empty($p['correo'])): ?>
                                        <br>
                                        <small style="color: var(--color-muted);">
                                            <?= htmlspecialchars($p['correo']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($p['telefono']) ?></td>

                                <td>
                                    <?php if (!empty($p['diagnostico_medico'])): ?>
                                        <?= htmlspecialchars($p['diagnostico_medico']) ?>
                                    <?php else: ?>
                                        <span style="color: var(--color-muted);">—</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha_registro']))) ?>
                                </td>

                                <td class="text-end">
                                    <a
                                        href="<?= BASE_URL ?>/modules/pacientes/ver.php?id=<?= (int) $p['id_paciente'] ?>"
                                        class="btn btn-sm btn-light"
                                        title="Ver expediente">

                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
                                        href="<?= BASE_URL ?>/modules/pacientes/editar.php?id=<?= (int) $p['id_paciente'] ?>"
                                        class="btn btn-sm btn-light"
                                        title="Editar">

                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>