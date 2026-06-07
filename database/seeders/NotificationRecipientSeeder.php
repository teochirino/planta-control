<?php

namespace Database\Seeders;

use App\Models\NotificationRecipient;
use Illuminate\Database\Seeder;

class NotificationRecipientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "📧 Insertando destinatarios de notificaciones...\n";

        $recipients = [
            [
                'name' => 'Compras',
                'email' => 'compras@example.com',
                'is_active' => true,
            ],
            // Puedes agregar más destinatarios de Compras si es necesario
            // [
            //     'name' => 'Compras',
            //     'email' => 'compras2@example.com',
            //     'is_active' => true,
            // ],
        ];

        foreach ($recipients as $recipient) {
            NotificationRecipient::firstOrCreate(
                ['email' => $recipient['email']],
                [
                    'name' => $recipient['name'],
                    'is_active' => $recipient['is_active'],
                ]
            );
            echo "   ✅ Destinatario creado: {$recipient['name']} - {$recipient['email']}\n";
        }

        echo "\n✅ Destinatarios de notificaciones insertados correctamente\n";
    }
}
