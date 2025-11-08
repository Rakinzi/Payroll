import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    type Company,
    useCompany,
    useCreateOrganizationalItem,
    useUpdateOrganizationalItem,
} from '@/hooks/queries/use-organizational-data';
import { Pencil, Plus } from 'lucide-react';
import { type FormEvent } from 'react';
import { create } from 'zustand';

interface CompanyDialogStore {
    isOpen: boolean;
    mode: 'create' | 'edit';
    companyData: Partial<Company> | null;
    openCreate: () => void;
    openEdit: (company: Company) => void;
    close: () => void;
}

const useCompanyDialog = create<CompanyDialogStore>((set) => ({
    isOpen: false,
    mode: 'create',
    companyData: null,
    openCreate: () => set({ isOpen: true, mode: 'create', companyData: null }),
    openEdit: (company) => set({ isOpen: true, mode: 'edit', companyData: company }),
    close: () => set({ isOpen: false, companyData: null }),
}));

export function CompanyTab() {
    const company = useCompany();
    const { openCreate, openEdit } = useCompanyDialog();

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-2xl font-bold tracking-tight">Company Details</h2>
                    <p className="text-muted-foreground">Manage your company information</p>
                </div>
                {!company && (
                    <Button onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Company
                    </Button>
                )}
            </div>

            {company ? (
                <Card>
                    <CardHeader>
                        <div className="flex justify-between items-center">
                            <div>
                                <CardTitle>{company.company_name}</CardTitle>
                                {company.description && (
                                    <CardDescription>{company.description}</CardDescription>
                                )}
                            </div>
                            <Button variant="outline" size="sm" onClick={() => openEdit(company)}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Edit
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            {company.company_email_address && (
                                <div>
                                    <Label className="text-muted-foreground">Email</Label>
                                    <p className="font-medium">{company.company_email_address}</p>
                                </div>
                            )}
                            {company.phone_number && (
                                <div>
                                    <Label className="text-muted-foreground">Phone</Label>
                                    <p className="font-medium">{company.phone_number}</p>
                                </div>
                            )}
                            {company.telephone_number && (
                                <div>
                                    <Label className="text-muted-foreground">Telephone</Label>
                                    <p className="font-medium">{company.telephone_number}</p>
                                </div>
                            )}
                            {company.physical_address && (
                                <div>
                                    <Label className="text-muted-foreground">Physical Address</Label>
                                    <p className="font-medium">{company.physical_address}</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            ) : (
                <Card>
                    <CardContent className="flex items-center justify-center py-12">
                        <div className="text-center">
                            <p className="text-muted-foreground mb-4">No company details found</p>
                            <Button onClick={openCreate}>
                                <Plus className="mr-2 h-4 w-4" />
                                Add Company
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            <CompanyDialog />
        </div>
    );
}

function CompanyDialog() {
    const { isOpen, mode, companyData, close } = useCompanyDialog();
    const createMutation = useCreateOrganizationalItem('company', '/api/companies');
    const updateMutation = useUpdateOrganizationalItem('company', '/api/companies', companyData?.id ?? '');

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);

        const data = {
            company_name: formData.get('company_name') as string,
            company_email_address: formData.get('company_email_address') as string || undefined,
            phone_number: formData.get('phone_number') as string || undefined,
            telephone_number: formData.get('telephone_number') as string || undefined,
            physical_address: formData.get('physical_address') as string || undefined,
            description: formData.get('description') as string || undefined,
            is_active: true,
        };

        if (mode === 'create') {
            createMutation.mutate(data, {
                onSuccess: () => close(),
            });
        } else {
            updateMutation.mutate(data, {
                onSuccess: () => close(),
            });
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && close()}>
            <DialogContent className="sm:max-w-[600px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {mode === 'create' ? 'Add Company' : 'Edit Company'}
                        </DialogTitle>
                        <DialogDescription>
                            {mode === 'create'
                                ? 'Enter your company details below'
                                : 'Update your company information'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="company_name">Company Name *</Label>
                            <Input
                                id="company_name"
                                name="company_name"
                                defaultValue={companyData?.company_name ?? ''}
                                required
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="company_email_address">Email</Label>
                            <Input
                                id="company_email_address"
                                name="company_email_address"
                                type="email"
                                defaultValue={companyData?.company_email_address ?? ''}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="phone_number">Phone</Label>
                                <Input
                                    id="phone_number"
                                    name="phone_number"
                                    defaultValue={companyData?.phone_number ?? ''}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="telephone_number">Telephone</Label>
                                <Input
                                    id="telephone_number"
                                    name="telephone_number"
                                    defaultValue={companyData?.telephone_number ?? ''}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="physical_address">Physical Address</Label>
                            <Input
                                id="physical_address"
                                name="physical_address"
                                defaultValue={companyData?.physical_address ?? ''}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Input
                                id="description"
                                name="description"
                                defaultValue={companyData?.description ?? ''}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={createMutation.isPending || updateMutation.isPending}
                        >
                            {mode === 'create' ? 'Create' : 'Update'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
