<?php

namespace App\Services;

use App\Models\NotificationRecipient;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Obtener todos los destinatarios activos
     */
    public function getActiveRecipients()
    {
        return NotificationRecipient::active()->get();
    }

    /**
     * Obtener array de emails de destinatarios activos
     */
    public function getActiveEmails()
    {
        return $this->getActiveRecipients()->pluck('email')->toArray();
    }

    /**
     * Enviar notificación de paro de máquina
     */
    public function sendMachineStopNotification($data)
    {
        $recipients = $this->getActiveRecipients();
        
        if ($recipients->isEmpty()) {
            Log::warning('No hay destinatarios activos para notificaciones de paro de máquina');
            return false;
        }

        $subject = '⚠️ Paro de Máquina - ' . ($data['machine_name'] ?? 'Sin nombre');
        
        $message = $this->buildMachineStopMessage($data);

        return $this->sendEmail($recipients->pluck('email')->toArray(), $subject, $message);
    }

    /**
     * Construir mensaje de notificación de paro de máquina
     */
    private function buildMachineStopMessage($data)
    {
        $message = "Se ha registrado un paro de máquina con los siguientes detalles:\n\n";
        
        if (isset($data['machine_name'])) {
            $message .= "🏭 Máquina: {$data['machine_name']}\n";
        }
        
        if (isset($data['work_center'])) {
            $message .= "📍 Centro de Trabajo: {$data['work_center']}\n";
        }
        
        if (isset($data['production_line'])) {
            $message .= "📊 Línea de Producción: {$data['production_line']}\n";
        }
        
        if (isset($data['start_time'])) {
            $message .= "⏰ Hora de Inicio: {$data['start_time']}\n";
        }
        
        if (isset($data['reason'])) {
            $message .= "🔧 Motivo: {$data['reason']}\n";
        }
        
        if (isset($data['operator'])) {
            $message .= "👤 Operador: {$data['operator']}\n";
        }
        
        $message .= "\n---\n";
        $message .= "Esta es una notificación automática del sistema Planta Control.";
        
        return $message;
    }

    /**
     * Enviar notificación personalizada
     */
    public function sendCustomNotification($subject, $message, $recipients = null)
    {
        $emails = $recipients ?? $this->getActiveEmails();
        
        if (empty($emails)) {
            Log::warning('No hay destinatarios para enviar notificación personalizada');
            return false;
        }

        return $this->sendEmail($emails, $subject, $message);
    }

    /**
     * Enviar email usando Laravel Mail
     */
    private function sendEmail($emails, $subject, $message)
    {
        try {
            Mail::raw($message, function ($mail) use ($emails, $subject) {
                $mail->to($emails)
                    ->subject($subject);
            });
            
            Log::info("Notificación enviada exitosamente a: " . implode(', ', $emails));
            return true;
        } catch (\Exception $e) {
            Log::error("Error al enviar notificación: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar notificación a destinatarios específicos
     */
    public function sendToSpecificRecipients($recipientIds, $subject, $message)
    {
        $recipients = NotificationRecipient::whereIn('id', $recipientIds)->active()->get();
        
        if ($recipients->isEmpty()) {
            Log::warning('No se encontraron destinatarios activos con los IDs proporcionados');
            return false;
        }

        return $this->sendEmail($recipients->pluck('email')->toArray(), $subject, $message);
    }
}
