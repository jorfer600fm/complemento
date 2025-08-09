<?php
/**
 * Muestra el HTML de la página de diagnóstico.
 *
 * @package Avisos_Peru
 */
?>
<div class="wrap ap-diagnostics-wrap">
    <h1><span class="dashicons-dashboard" style="font-size: 1.2em; margin-right: 8px;"></span>Estado y Diagnóstico de "Avisos Perú"</h1>
    <p>Esta página te ofrece una vista general del estado y la salud de los componentes clave del plugin.</p>

    <div id="ap-diagnostics-container">
        <div class="ap-diagnostics-col">
            <div class="ap-card">
                <h2 class="ap-card-title">Configuración General</h2>
                <div class="ap-status-item ap-status-<?php echo esc_attr($data['api_check']['status']); ?>">
                    <strong>API de IA (Gemini):</strong>
                    <span><?php echo esc_html($data['api_check']['message']); ?></span>
                </div>
                <div class="ap-status-item ap-status-ok">
                    <strong>Plantillas de Correo:</strong>
                    <span>El sistema está configurado para leer las plantillas desde la base de datos. Puedes gestionarlas en <a href="edit.php?post_type=aviso&page=ap-email-settings">Avisos > Plantillas de Correo</a>.</span>
                </div>
            </div>

            <div class="ap-card">
                <h2 class="ap-card-title">Diagnóstico de Correos</h2>
                <p>Usa este botón para verificar si tu servidor puede enviar correos correctamente. El correo de prueba se enviará a <strong><?php echo esc_html(get_option('admin_email')); ?></strong>.</p>
                <button id="ap-test-email-btn" class="button button-primary">Enviar Correo de Prueba</button>
                <div id="ap-email-test-feedback" style="margin-top: 15px;"></div>
                <span class="spinner" style="float:none; vertical-align: middle;"></span>
            </div>
            
            <div class="ap-card">
                <h2 class="ap-card-title">Registro de Errores Recientes (Memoria de Fallas)</h2>
                <?php if (empty($data['error_log'])): ?>
                    <p>No se han registrado errores recientemente. ¡Todo parece funcionar bien!</p>
                <?php else: ?>
                    <div class="ap-error-log-container">
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th style="width: 200px;">Fecha y Hora</th>
                                    <th>Mensaje del Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['error_log'] as $error): ?>
                                    <tr>
                                        <td><?php echo esc_html(date_i18n('d-m-Y H:i:s', strtotime($error['timestamp']))); ?></td>
                                        <td><?php echo esc_html($error['message']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                 <button id="ap-clear-log-btn" class="button" style="margin-top: 15px;">Limpiar Registro</button>
                 <span class="spinner" style="float:none; vertical-align: middle;"></span>
            </div>
        </div>

        <div class="ap-diagnostics-col">
            <div class="ap-card">
                <h2 class="ap-card-title">Resumen de Actividad</h2>
                <div class="ap-activity-grid">
                    <div class="ap-activity-item">
                        <span class="ap-activity-number"><?php echo esc_html($data['post_counts']['pending']); ?></span>
                        <span class="ap-activity-label">Pendientes de Revisión</span>
                    </div>
                    <div class="ap-activity-item">
                        <span class="ap-activity-number"><?php echo esc_html($data['post_counts']['unpaid']); ?></span>
                        <span class="ap-activity-label">Esperando Pago</span>
                    </div>
                    <div class="ap-activity-item">
                        <span class="ap-activity-number"><?php echo esc_html($data['post_counts']['publish']); ?></span>
                        <span class="ap-activity-label">Total Publicados</span>
                    </div>
                </div>
            </div>

            <div class="ap-card">
                <h2 class="ap-card-title">Tareas Automáticas (WP-Cron)</h2>
                <div class="ap-status-item ap-status-<?php echo esc_attr($data['cron_check']['status']); ?>">
                    <strong>Mantenimiento Diario:</strong>
                    <span><?php echo esc_html($data['cron_check']['message']); ?></span>
                </div>
                <p class="description">Esta tarea se encarga de mover a la papelera los anuncios vencidos y de eliminar permanentemente los anuncios que llevan mucho tiempo en la papelera.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .ap-diagnostics-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
    #ap-diagnostics-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
    .ap-diagnostics-col { display: flex; flex-direction: column; gap: 20px; }
    .ap-card { background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; }
    .ap-card-title { font-size: 1.2em; margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
    .ap-status-item { margin-bottom: 10px; padding: 10px; border-left: 4px solid; }
    .ap-status-item strong { display: block; font-weight: 600; }
    .ap-status-ok { background-color: #f0f6fc; border-color: #72aee6; }
    .ap-status-warning { background-color: #fff8e5; border-color: #ffb900; }
    .ap-status-error { background-color: #fbeaea; border-color: #d63638; }
    .ap-activity-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; text-align: center; }
    .ap-activity-item .ap-activity-number { font-size: 2.5em; font-weight: 600; color: #076445; display: block; line-height: 1.1; }
    .ap-activity-item .ap-activity-label { font-size: 0.9em; color: #50575e; }
    .ap-error-log-container { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; }
    .ap-diagnostics-wrap .spinner { visibility: hidden; }
    .ap-diagnostics-wrap .spinner.is-active { visibility: visible; }
    #ap-email-test-feedback .notice { margin: 0; }
    @media (max-width: 900px) { #ap-diagnostics-container { grid-template-columns: 1fr; } }
</style>