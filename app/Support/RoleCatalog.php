<?php

namespace App\Support;

final class RoleCatalog
{
    /**
     * @return array<string, array{name: string, description: string, badge_color: string, icon: string}>
     */
    public static function all(): array
    {
        return [
            'admin' => [
                'name' => 'Administrador',
                'description' => 'Acesso total a todas as funcionalidades, configurações, métricas financeiras e gerenciamento de equipe.',
                'badge_color' => 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 border-indigo-500/30',
                'icon' => 'fa-solid fa-shield-halved',
            ],
            'manager' => [
                'name' => 'Gerente',
                'description' => 'Gerencia agendamentos, serviços, horários de expediente, clientes e profissionais do estabelecimento.',
                'badge_color' => 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30',
                'icon' => 'fa-solid fa-user-tie',
            ],
            'professional' => [
                'name' => 'Profissional / Especialista',
                'description' => 'Visualiza e gerencia exclusivamente sua própria agenda de atendimentos e horários.',
                'badge_color' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                'icon' => 'fa-solid fa-user-check',
            ],
            'receptionist' => [
                'name' => 'Recepcionista / Atendente',
                'description' => 'Cria, reagenda e confirma horários de clientes para qualquer profissional do estabelecimento.',
                'badge_color' => 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
                'icon' => 'fa-solid fa-headset',
            ],
        ];
    }

    public static function ids(): array
    {
        return array_keys(self::all());
    }

    public static function titleFor(?string $roleId): string
    {
        $roles = self::all();
        return $roles[$roleId ?? '']['name'] ?? $roles['professional']['name'];
    }

    public static function badgeColorFor(?string $roleId): string
    {
        $roles = self::all();
        return $roles[$roleId ?? '']['badge_color'] ?? $roles['professional']['badge_color'];
    }

    public static function iconFor(?string $roleId): string
    {
        $roles = self::all();
        return $roles[$roleId ?? '']['icon'] ?? $roles['professional']['icon'];
    }
}
