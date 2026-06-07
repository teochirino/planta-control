<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationRecipientController extends Controller
{
    /**
     * Obtener categorías disponibles para destinatarios
     */
    private function getCategories(): array
    {
        return [
            'Gerencia',
            'Compras',
            'Mantenimiento',
            'Ingeniería de procesos',
        ];
    }

    /**
     * Mostrar listado de destinatarios de notificaciones
     */
    public function index()
    {
        $recipients = NotificationRecipient::orderBy('name')->paginate(15);

        return Inertia::render('Admin/NotificationRecipients/Index', [
            'recipients' => $recipients,
        ]);
    }

    /**
     * Mostrar formulario para crear destinatario
     */
    public function create()
    {
        return Inertia::render('Admin/NotificationRecipients/Create', [
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Guardar nuevo destinatario
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|in:' . implode(',', $this->getCategories()),
            'email' => 'required|email|unique:notification_recipients,email',
            'is_active' => 'boolean',
        ]);

        NotificationRecipient::create([
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.notification-recipients.index')
            ->with('success', 'Destinatario creado correctamente.');
    }

    /**
     * Mostrar formulario para editar destinatario
     */
    public function edit(NotificationRecipient $notificationRecipient)
    {
        return Inertia::render('Admin/NotificationRecipients/Edit', [
            'recipient' => $notificationRecipient,
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Actualizar destinatario
     */
    public function update(Request $request, NotificationRecipient $notificationRecipient)
    {
        $request->validate([
            'name' => 'required|string|in:' . implode(',', $this->getCategories()),
            'email' => 'required|email|unique:notification_recipients,email,' . $notificationRecipient->id,
            'is_active' => 'boolean',
        ]);

        $notificationRecipient->update([
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.notification-recipients.index')
            ->with('success', 'Destinatario actualizado correctamente.');
    }

    /**
     * Eliminar destinatario
     */
    public function destroy(NotificationRecipient $notificationRecipient)
    {
        $notificationRecipient->delete();

        return redirect()->route('admin.notification-recipients.index')
            ->with('success', 'Destinatario eliminado correctamente.');
    }
}
