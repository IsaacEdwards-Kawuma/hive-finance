<?php

namespace App\Services;

class Permissions
{
    /** Full access: all modules, settings, audit, roles, companies (Admin/IT). */
    public const ADMIN_FULL = 'admin.full';

    public const ALL = [
        self::ADMIN_FULL,
        'dashboard.view',
        'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
        'bills.view', 'bills.create', 'bills.edit', 'bills.delete',
        'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
        'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.delete',
        'accounts.view', 'accounts.create', 'accounts.edit', 'accounts.delete',
        'journal-entries.view', 'journal-entries.create', 'journal-entries.edit', 'journal-entries.delete', 'journal-entries.post',
        'banking.view', 'banking.create', 'banking.edit', 'banking.delete',
        'reports.view',
        'items.view', 'items.create', 'items.edit', 'items.delete',
        'tax-rates.view', 'tax-rates.create', 'tax-rates.edit', 'tax-rates.delete',
        'settings.view', 'settings.roles', 'settings.companies',
        'investments.view', 'investments.create', 'investments.edit', 'investments.delete',
        'communications.view', 'communications.create', 'communications.edit', 'communications.delete',
        'employee.dashboard',
        'shifts.manage',
        'shifts.view',
        'secretary.dashboard',
        'meetings.view', 'meetings.create', 'meetings.edit', 'meetings.delete',
    ];

    public static function forRole(?array $permissions): array
    {
        if ($permissions === null || $permissions === []) {
            return self::ALL;
        }
        return $permissions;
    }

    public static function definitions(): array
    {
        return [
            'admin' => ['full' => 'Full access (Admin/IT): all modules, settings, audit log, roles, companies'],
            'dashboard' => ['view' => 'View management dashboard (CEO/Director)'],
            'invoices' => ['view' => 'View invoices', 'create' => 'Create invoices', 'edit' => 'Edit invoices', 'delete' => 'Delete invoices'],
            'bills' => ['view' => 'View bills', 'create' => 'Create bills', 'edit' => 'Edit bills', 'delete' => 'Delete bills'],
            'customers' => ['view' => 'View customers', 'create' => 'Create customers', 'edit' => 'Edit customers', 'delete' => 'Delete customers'],
            'vendors' => ['view' => 'View vendors', 'create' => 'Create vendors', 'edit' => 'Edit vendors', 'delete' => 'Delete vendors'],
            'accounts' => ['view' => 'View chart of accounts', 'create' => 'Create accounts', 'edit' => 'Edit accounts', 'delete' => 'Delete accounts'],
            'journal-entries' => ['view' => 'View journal entries', 'create' => 'Create entries', 'edit' => 'Edit entries', 'delete' => 'Delete entries', 'post' => 'Post entries'],
            'banking' => ['view' => 'View banking', 'create' => 'Create accounts/transactions', 'edit' => 'Edit', 'delete' => 'Delete'],
            'reports' => ['view' => 'View reports'],
            'items' => ['view' => 'View items', 'create' => 'Create items', 'edit' => 'Edit items', 'delete' => 'Delete items'],
            'tax-rates' => ['view' => 'View tax rates', 'create' => 'Create tax rates', 'edit' => 'Edit tax rates', 'delete' => 'Delete tax rates'],
            'settings' => ['view' => 'View settings', 'roles' => 'Manage roles', 'companies' => 'Manage companies'],
            'investments' => ['view' => 'View investments', 'create' => 'Create investments', 'edit' => 'Edit investments', 'delete' => 'Delete investments'],
            'communications' => ['view' => 'View communications', 'create' => 'Post announcements', 'edit' => 'Edit communications', 'delete' => 'Delete communications'],
            'employee' => ['dashboard' => 'Access employee dashboard (availability, shifts, chat)'],
            'shifts' => ['manage' => 'Create and manage shifts for employees', 'view' => 'View shifts and schedule (read-only)'],
            'secretary' => ['dashboard' => 'Access secretary dashboard (communications, schedule, announcements)'],
            'meetings' => ['view' => 'View meetings', 'create' => 'Create meetings', 'edit' => 'Edit meetings', 'delete' => 'Delete meetings'],
        ];
    }
}
