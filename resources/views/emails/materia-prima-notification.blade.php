<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Materia Prima</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: #0b2a40; padding: 20px; border-radius: 8px 8px 0 0;">
            <h1 style="color: white; margin: 0; font-size: 24px;">⚠️ Alerta de Materia Prima</h1>
        </div>
        
        <div style="background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
            <p style="font-size: 16px; margin-bottom: 20px;">
                Se ha registrado un cambio de estado en el semáforo de <strong>Materia Prima</strong>.
            </p>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold; width: 40%;">Centro de Trabajo:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $workCenter }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Usuario:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $user }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Nuevo Estado:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                        <span style="background: {{ $newColor === 'rojo' ? '#fce9e8' : '#fff6da' }}; color: {{ $newColor === 'rojo' ? '#ba2418' : '#a87000' }}; padding: 5px 10px; border-radius: 4px; font-weight: bold; text-transform: capitalize;">
                            {{ $newColor }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Motivo del Cambio:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{{ $reason ?: 'No especificado' }}</td>
                </tr>
            </table>

            <p style="font-size: 14px; color: #666; margin-top: 30px;">
                Este es un mensaje automático del sistema de control de planta. Por favor no responda a este correo.
            </p>
            
            <p style="font-size: 14px; color: #666;">
                Fecha y hora: {{ now()->format('d/m/Y H:i:s') }}
            </p>
        </div>
    </div>
</body>
</html>
