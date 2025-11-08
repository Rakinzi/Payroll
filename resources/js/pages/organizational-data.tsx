import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useOrganizationalData } from '@/hooks/queries/use-organizational-data';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import {
    Building,
    Briefcase,
    DollarSign,
    GraduationCap,
    MapPin,
    FileText,
    Users,
    TrendingUp,
    CreditCard,
    Car,
    Landmark,
} from 'lucide-react';
import { CompanyTab } from './organizational-data/company-tab';
import { TaxCreditsTab } from './organizational-data/tax-credits-tab';
import { VehicleBenefitBandsTab } from './organizational-data/vehicle-benefit-bands-tab';
import { CompanyBankDetailsTab } from './organizational-data/company-bank-details-tab';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Organizational Data', href: '/organizational-data' },
];

export default function OrganizationalDataPage() {
    const { data, isLoading, error } = useOrganizationalData();

    if (isLoading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Organizational Data" />
                <div className="flex h-full items-center justify-center p-6">
                    <Spinner className="h-8 w-8" />
                </div>
            </AppLayout>
        );
    }

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Organizational Data" />
                <div className="p-6">
                    <Card className="border-destructive">
                        <CardContent className="pt-6">
                            <p className="text-destructive">
                                Error: {error instanceof Error ? error.message : 'Failed to load data'}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organizational Data" />
            <div className="flex flex-col gap-6 p-6">
                <div className="space-y-2">
                    <h1 className="text-3xl font-bold tracking-tight">Organizational Data Management</h1>
                    <p className="text-muted-foreground">
                        Manage company structure, payroll codes, tax bands, and other organizational configurations
                    </p>
                </div>

                <Tabs defaultValue="company" className="w-full">
                    <TabsList>
                        <TabsTrigger value="company">
                            <Building className="mr-2 h-4 w-4" />
                            Company
                        </TabsTrigger>
                        <TabsTrigger value="tax-credits">
                            <CreditCard className="mr-2 h-4 w-4" />
                            Tax Credits
                        </TabsTrigger>
                        <TabsTrigger value="vehicle-benefits">
                            <Car className="mr-2 h-4 w-4" />
                            Vehicle Benefits
                        </TabsTrigger>
                        <TabsTrigger value="bank-details">
                            <Landmark className="mr-2 h-4 w-4" />
                            Bank Details
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="company">
                        <CompanyTab />
                    </TabsContent>

                    <TabsContent value="tax-credits">
                        <TaxCreditsTab />
                    </TabsContent>

                    <TabsContent value="vehicle-benefits">
                        <VehicleBenefitBandsTab />
                    </TabsContent>

                    <TabsContent value="bank-details">
                        <CompanyBankDetailsTab />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
