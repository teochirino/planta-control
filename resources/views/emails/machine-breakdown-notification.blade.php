<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Paro de Máquina</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #dc2626; padding: 20px; border-radius: 8px 8px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 24px;">⚠️ Alerta de Paro de Máquina</h1>
        </div>
        
        <div style="background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
            <p style="font-size: 16px; margin-bottom: 20px;">
                Se ha registrado un <strong>paro de máquina</strong> que requiere atención inmediata del equipo de <strong>Mantenimiento</strong>.
            </p>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold; width: 40%;">Máquina:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $machineName }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Centro de Trabajo:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $workCenter }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Línea de Producción:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $productionLine }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Usuario que registró:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $user }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Hora de inicio:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $startTime }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Motivo del paro:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $reason ?: 'No especificado' }}</td>
                </tr>
            </table>

            <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; font-size: 14px; color: #991b1b;">
                    <strong>Acción requerida:</strong> Por favor revisar y atender la máquina a la brevedad posible para minimizar el tiempo de inactividad.
                </p>
            </div>

            <p style="font-size: 14px; color: #666; margin-top: 30px;">
                Este es un mensaje automático del sistema de control de planta. Por favor no responda a este correo.
            </p>
            
            <p style="font-size: 14px; color: #666;">
                Fecha y hora de notificación: {{ now()->format('d/m/Y H:i:s') }}
            </p>
        </div>
    </div>
</body>
</html>
