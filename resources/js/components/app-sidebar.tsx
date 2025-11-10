import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import employees from '@/routes/employees';
import dischargedEmployees from '@/routes/discharged-employees';
import payrolls from '@/routes/payrolls';
import payslips from '@/routes/payslips';
import accountingPeriods from '@/routes/accounting-periods';
import defaultTransactions from '@/routes/default-transactions';
import customTransactions from '@/routes/custom-transactions';
import transactionCodes from '@/routes/transaction-codes';
import leave from '@/routes/leave';
import reports from '@/routes/reports';
import companies from '@/routes/companies';
import taxBands from '@/routes/tax-bands';
import taxCredits from '@/routes/tax-credits';
import currencySetup from '@/routes/currency-setup';
import vehicleBenefits from '@/routes/vehicle-benefits';
import companyBankDetails from '@/routes/company-bank-details';
import admins from '@/routes/admins';
import activityLogs from '@/routes/activity-logs';
import securityLogs from '@/routes/security-logs';
import appearance from '@/routes/appearance';
import profile from '@/routes/profile';
import { organizationalData } from '@/routes';
import spreadsheetImport from '@/routes/spreadsheet-import';
import notices from '@/routes/notices';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    Users,
    Wallet,
    Calendar,
    BarChart3,
    Building2,
    Settings as SettingsIcon,
    ShieldCheck,
    Database,
    Upload,
    Bell,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Employees',
        href: employees.index(),
        icon: Users,
        items: [
            {
                title: 'All Employees',
                href: employees.index(),
            },
            {
                title: 'Discharged Employees',
                href: dischargedEmployees.index(),
            },
        ],
    },
    {
        title: 'Payroll',
        href: payrolls.index(),
        icon: Wallet,
        items: [
            {
                title: 'Payroll Processing',
                href: payrolls.index(),
            },
            {
                title: 'Payslips',
                href: payslips.index(),
            },
            {
                title: 'Accounting Periods',
                href: accountingPeriods.index(),
            },
            {
                title: 'Default Transactions',
                href: defaultTransactions.index(),
            },
            {
                title: 'Custom Transactions',
                href: customTransactions.index(),
            },
            {
                title: 'Transaction Codes',
                href: transactionCodes.index(),
            },
        ],
    },
    {
        title: 'Leave Management',
        href: leave.applications.index(),
        icon: Calendar,
        items: [
            {
                title: 'Applications',
                href: leave.applications.index(),
            },
            {
                title: 'Balances',
                href: leave.balances.index(),
            },
            {
                title: 'Reports',
                href: leave.reports.index(),
            },
        ],
    },
    {
        title: 'Reports',
        href: reports.index(),
        icon: BarChart3,
    },
    {
        title: 'Companies',
        href: companies.show(),
        icon: Building2,
    },
    {
        title: 'Configuration',
        href: taxBands.index(),
        icon: SettingsIcon,
        items: [
            {
                title: 'Tax Bands',
                href: taxBands.index(),
            },
            {
                title: 'Tax Credits',
                href: taxCredits.index(),
            },
            {
                title: 'Currency Setup',
                href: currencySetup.index(),
            },
            {
                title: 'Vehicle Benefits',
                href: vehicleBenefits.index(),
            },
            {
                title: 'Company Bank Details',
                href: companyBankDetails.index(),
            },
        ],
    },
    {
        title: 'Administration',
        href: admins.index(),
        icon: ShieldCheck,
        items: [
            {
                title: 'Admins',
                href: admins.index(),
            },
            {
                title: 'Activity Logs',
                href: activityLogs.index(),
            },
            {
                title: 'Security Logs',
                href: securityLogs.index(),
            },
        ],
    },
    {
        title: 'Organizational Data',
        href: organizationalData(),
        icon: Database,
    },
    {
        title: 'Import Data',
        href: spreadsheetImport.index(),
        icon: Upload,
    },
    {
        title: 'Notices',
        href: notices.index(),
        icon: Bell,
    },
    {
        title: 'Settings',
        href: profile.edit(),
        icon: SettingsIcon,
        items: [
            {
                title: 'Profile',
                href: profile.edit(),
            },
            {
                title: 'Appearance',
                href: appearance.edit(),
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
