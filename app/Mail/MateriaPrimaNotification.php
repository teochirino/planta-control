<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MateriaPrimaNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $workCenter;
    public $user;
    public $reason;
    public $newColor;

    /**
     * Create a new message instance.
     */
    public function __construct($workCenter, $user, $reason, $newColor)
    {
        $this->workCenter = $workCenter;
        $this->user = $user;
        $this->reason = $reason;
        $this->newColor = $newColor;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'MateriaPrima',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.materia-prima-notification',
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
