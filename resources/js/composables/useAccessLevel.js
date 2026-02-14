import { computed } from 'vue';
import { usePermissions } from './usePermissions';

/**
 * Hierarchical access levels (highest first):
 * - admin: full access (Admin/IT)
 * - ceo: management dashboard + all permitted modules (CEO/Director)
 * - finance: financial dashboard + finance-related pages only (Finance Manager)
 * - operations: operational dashboard + shifts/employee/communications only (Operations Manager)
 * - secretary: secretary dashboard + communications/schedule (Secretary)
 * - employee: employee portal only (Employee)
 */
export function useAccessLevel() {
  const { can, permissions, loaded } = usePermissions();

  const accessLevel = computed(() => {
    if (!loaded.value || !permissions.value?.length) return null;
    if (can('admin.full')) return 'admin';
    if (can('dashboard.view')) return 'ceo';
    if (can('reports.view')) return 'finance';
    if (can('shifts.manage')) return 'operations';
    if (can('secretary.dashboard')) return 'secretary';
    if (can('employee.dashboard')) return 'employee';
    return null;
  });

  const homePath = computed(() => {
    switch (accessLevel.value) {
      case 'admin':
      case 'ceo':
        return '/';
      case 'finance':
        return '/dashboard/financial';
      case 'operations':
        return '/dashboard/operational';
      case 'secretary':
        return '/dashboard/secretary';
      case 'employee':
        return '/employee';
      default:
        return '/';
    }
  });

  const isAdmin = computed(() => accessLevel.value === 'admin');
  const isCeo = computed(() => accessLevel.value === 'ceo');
  const isFinance = computed(() => accessLevel.value === 'finance');
  const isOperations = computed(() => accessLevel.value === 'operations');
  const isSecretary = computed(() => accessLevel.value === 'secretary');
  const isEmployee = computed(() => accessLevel.value === 'employee');

  /** Routes allowed for current access level (path prefixes or exact paths). */
  const allowedPaths = computed(() => {
    const level = accessLevel.value;
    if (level === 'admin') {
      return null; // all paths
    }
    if (level === 'ceo') {
      return null; // all paths they have permission for (nav already filters by can())
    }
    if (level === 'finance') {
      return [
        '/dashboard/financial',
        '/invoices', '/bills', '/recurring-invoices', '/recurring-bills', '/credit-notes',
        '/customers', '/vendors', '/accounts', '/journal-entries', '/banking',
        '/items', '/tax-rates', '/reports', '/investments', '/communications',
        '/settings/finance',
        '/help', '/about',
      ];
    }
    if (level === 'operations') {
      return [
        '/dashboard/operational',
        '/employee',
        '/communications',
        '/settings/operations',
        '/help', '/about',
      ];
    }
    if (level === 'secretary') {
      return [
        '/dashboard/secretary',
        '/meetings',
        '/communications',
        '/employee',
        '/settings/secretary',
        '/help', '/about',
      ];
    }
    if (level === 'employee') {
      return ['/employee', '/help', '/about'];
    }
    return ['/help', '/about'];
  });

  const adminOnlyPaths = ['/audit-log', '/apps'];
  const adminOnlySettingsExact = true; // /settings (full) is admin-only; /settings/finance, /settings/operations are not

  function canAccessPath(path) {
    const normalized = path.replace(/\/$/, '') || '/';
    if (adminOnlyPaths.some(p => normalized === p || normalized.startsWith(p + '/'))) {
      return accessLevel.value === 'admin';
    }
    if (adminOnlySettingsExact && normalized === '/settings') {
      return accessLevel.value === 'admin';
    }
    if (allowedPaths.value === null) return true;
    return allowedPaths.value.some(allowed => normalized === allowed || normalized.startsWith(allowed + '/'));
  }

  return {
    accessLevel,
    homePath,
    isAdmin,
    isCeo,
    isFinance,
    isOperations,
    isSecretary,
    isEmployee,
    allowedPaths,
    canAccessPath,
  };
}
