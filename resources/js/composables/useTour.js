import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

const tourSteps = [
  {
    element: 'main',
    popover: {
      title: 'Welcome to HiveStack IntelliCash',
      description: 'This short tour shows where to find the main features. Use the sidebar on the left to navigate. You can skip or replay the tour anytime from Help.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-dashboard"]',
    popover: {
      title: 'Dashboard',
      description: 'Your home screen: revenue, outstanding AR/AP, portfolio value, and cash flow charts. Recent invoices and bills are listed here with quick links to reports and Investments.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-customers"]',
    popover: {
      title: 'Customers',
      description: 'Manage customers—names and contact details—so you can create invoices for them.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-invoices"]',
    popover: {
      title: 'Invoices',
      description: 'Create and manage sales invoices: line items, tax, payments. Download PDFs, duplicate, or use Pay with Stripe when configured.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-bills"]',
    popover: {
      title: 'Bills',
      description: 'Track bills from vendors. Create bills, add line items, record payments, and duplicate when needed.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-banking"]',
    popover: {
      title: 'Banking',
      description: 'Manage bank accounts and transactions. Add deposits, withdrawals, and transfers. Use Reconcile to match transactions to your statement.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-investments"]',
    popover: {
      title: 'Investments',
      description: 'Track holdings (stocks, bonds, funds, etc.): cost basis, current value, and gain/loss. Charts show allocation by type and top holdings.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-reports"]',
    popover: {
      title: 'Reports',
      description: 'Profit & Loss, Balance Sheet, Trial Balance, GL Detail, AR/AP Aging, Cash Flow, Tax Summary, and customer/vendor statements. Download any report as PDF.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-settings"]',
    popover: {
      title: 'Settings',
      description: 'Company details, team and roles, period closing, payment reminders, webhooks, and preferences.',
      side: 'right',
      align: 'start',
    },
  },
  {
    element: '[data-tour="nav-help"]',
    popover: {
      title: 'Help',
      description: 'Detailed guides for every feature. Start this tour again anytime from the Help page or from "Take a tour" in the sidebar.',
      side: 'right',
      align: 'start',
    },
  },
];

let driverInstance = null;

export function useTour() {
  function startTour() {
    if (driverInstance) {
      try {
        driverInstance.destroy();
      } catch (_) {}
    }
    driverInstance = driver({
      showProgress: true,
      steps: tourSteps.filter((step) => {
        const el = document.querySelector(step.element);
        return el && el.offsetParent !== null;
      }),
      popoverClass: 'driverjs-theme',
      onDestroyStarted: () => {
        driverInstance.destroy();
      },
    });
    driverInstance.drive();
  }

  return { startTour };
}
