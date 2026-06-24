<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MachineBreakdownNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $machineName;
    public $workCenter;
    public $productionLine;
    public $user;
    public $reason;
    public $startTime;

    /**
     * Create a new message instance.
     */
    public function __construct($machineName, $workCenter, $productionLine, $user, $reason, $startTime)
    {
        $this->machineName = $machineName;
        $this->workCenter = $workCenter;
        $this->productionLine = $productionLine;
        $this->user = $user;
        $this->reason = $reason;
        $this->startTime = $startTime;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Paro de Máquina - Requiere Atención de Mantenimiento',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.machine-breakdown-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
