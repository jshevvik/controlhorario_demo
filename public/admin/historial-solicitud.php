<?php
require_once __DIR__ . '/../../includes/init.php';
requireAdminOrSupervisor();

$solicitudId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$solicitudId) {
    echo json_encode(['error' => 'ID de solicitud inválido']);
    exit;
}

// Obtener historial
$historial = getHistorialSolicitud($solicitudId);

// Devolver JSON si se solicita
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($historial);
    exit;
}
?>

<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history me-2"></i>
                    Historial de Cambios
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($historial)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        No hay cambios registrados para esta solicitud.
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($historial as $cambio): ?>
                            <div class="timeline-item mb-4">
                                <div class="timeline-marker">
                                    <?php
                                    $iconClass = match($cambio['accion']) {
                                        'crear' => 'bi-plus-circle text-success',
                                        'editar' => 'bi-pencil text-warning',
                                        'aprobar' => 'bi-check-circle text-success',
                                        'rechazar' => 'bi-x-circle text-danger',
                                        'eliminar' => 'bi-trash text-danger',
                                        default => 'bi-circle text-secondary'
                                    };
                                    ?>
                                    <i class="bi <?= $iconClass ?> fs-4"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?= htmlspecialchars($cambio['nombre'] . ' ' . $cambio['apellidos']) ?></strong>
                                            <span class="badge bg-secondary ms-2"><?= htmlspecialchars($cambio['rol']) ?></span>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($cambio['fecha'])) ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <span class="badge bg-<?= 
                                            $cambio['accion'] === 'aprobar' ? 'success' : 
                                            ($cambio['accion'] === 'rechazar' ? 'danger' : 
                                            ($cambio['accion'] === 'editar' ? 'warning' : 'info'))
                                        ?>">
                                            <?= ucfirst($cambio['accion']) ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($cambio['campo_modificado']): ?>
                                        <div class="bg-light p-2 rounded">
                                            <strong>Campo:</strong> <?= htmlspecialchars($cambio['campo_modificado']) ?>
                                            <?php if ($cambio['valor_anterior']): ?>
                                                <br><strong>Anterior:</strong> <code><?= htmlspecialchars($cambio['valor_anterior']) ?></code>
                                            <?php endif; ?>
                                            <?php if ($cambio['valor_nuevo']): ?>
                                                <br><strong>Nuevo:</strong> <code><?= htmlspecialchars($cambio['valor_nuevo']) ?></code>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($cambio['comentario']): ?>
                                        <div class="mt-2">
                                            <i class="bi bi-chat-quote me-1"></i>
                                            <em><?= htmlspecialchars($cambio['comentario']) ?></em>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-globe me-1"></i> IP: <?= htmlspecialchars($cambio['ip_address']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -40px;
    top: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -25px;
    top: 30px;
    bottom: -20px;
    width: 2px;
    background: #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #0d6efd;
}
</style>
