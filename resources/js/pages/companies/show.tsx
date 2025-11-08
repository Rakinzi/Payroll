import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/components/layouts/app-layout';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Building2,
    CheckCircle2,
    Edit,
    Globe,
    Info,
    Mail,
    MapPin,
    Phone,
    Save,
    Upload,
    X,
} from 'lucide-react';
import { Company, CompanyData, useUpdateCompany, useUploadCompanyLogo, useDeleteCompanyLogo } from '@/hooks/queries/use-companies';

interface CompanyShowPageProps {
    company: Company;
    canEdit: boolean;
}

export default function CompanyShow() {
    const { company, canEdit } = usePage<CompanyShowPageProps>().props;
    const [isEditing, setIsEditing] = useState(false);
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(company.logo_url);

    const [formData, setFormData] = useState<CompanyData>({
        company_name: company.company_name,
        company_email_address: company.company_email_address,
        phone_number: company.phone_number,
        telephone_number: company.telephone_number,
        physical_address: company.physical_address,
        registration_number: company.registration_number,
        tax_number: company.tax_number,
        industry: company.industry,
        website: company.website,
        description: company.description,
        is_active: company.is_active,
    });

    const updateMutation = useUpdateCompany(company.id);
    const uploadLogoMutation = useUploadCompanyLogo(company.id);
    const deleteLogoMutation = useDeleteCompanyLogo(company.id);

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setLogoFile(file);
            const reader = new FileReader();
            reader.onload = (e) => setLogoPreview(e.target?.result as string);
            reader.readAsDataURL(file);
        }
    };

    const handleUploadLogo = async () => {
        if (!logoFile) return;

        try {
            const result = await uploadLogoMutation.mutateAsync(logoFile);
            setLogoPreview(result.logo_url);
            setLogoFile(null);
        } catch (error) {
            console.error('Failed to upload logo:', error);
        }
    };

    const handleDeleteLogo = async () => {
        if (!confirm('Are you sure you want to delete the company logo?')) return;

        try {
            await deleteLogoMutation.mutateAsync();
            setLogoPreview(null);
        } catch (error) {
            console.error('Failed to delete logo:', error);
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        try {
            await updateMutation.mutateAsync(formData);
            setIsEditing(false);
        } catch (error) {
            console.error('Failed to update company:', error);
        }
    };

    const handleCancel = () => {
        setFormData({
            company_name: company.company_name,
            company_email_address: company.company_email_address,
            phone_number: company.phone_number,
            telephone_number: company.telephone_number,
            physical_address: company.physical_address,
            registration_number: company.registration_number,
            tax_number: company.tax_number,
            industry: company.industry,
            website: company.website,
            description: company.description,
            is_active: company.is_active,
        });
        setIsEditing(false);
        setLogoFile(null);
        setLogoPreview(company.logo_url);
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Company Details
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Manage your company profile and information
                        </p>
                    </div>
                    {canEdit && !isEditing && (
                        <Button onClick={() => setIsEditing(true)}>
                            <Edit className="mr-2 h-4 w-4" />
                            Edit Details
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Company Details" />

            <div className="space-y-6">
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Company Information:</strong> This information is used across the
                        system for reports, communications, and legal documentation. Keep it
                        up-to-date.
                    </AlertDescription>
                </Alert>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Logo Section */}
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="h-5 w-5" />
                                Company Logo
                            </CardTitle>
                            <CardDescription>
                                Upload your company logo for reports and documents
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-col items-center justify-center">
                                {logoPreview ? (
                                    <div className="relative mb-4">
                                        <img
                                            src={logoPreview}
                                            alt="Company Logo"
                                            className="max-h-48 rounded-lg border object-contain"
                                        />
                                        {canEdit && (
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                className="absolute -right-2 -top-2"
                                                onClick={handleDeleteLogo}
                                                disabled={deleteLogoMutation.isPending}
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>
                                ) : (
                                    <div className="mb-4 flex h-48 w-48 items-center justify-center rounded-lg border-2 border-dashed bg-muted">
                                        <Building2 className="h-16 w-16 text-muted-foreground" />
                                    </div>
                                )}

                                {canEdit && (
                                    <div className="w-full space-y-2">
                                        <Label htmlFor="logo">Upload Logo</Label>
                                        <Input
                                            id="logo"
                                            type="file"
                                            accept="image/*"
                                            onChange={handleLogoChange}
                                        />
                                        {logoFile && (
                                            <Button
                                                className="w-full"
                                                onClick={handleUploadLogo}
                                                disabled={uploadLogoMutation.isPending}
                                            >
                                                <Upload className="mr-2 h-4 w-4" />
                                                {uploadLogoMutation.isPending
                                                    ? 'Uploading...'
                                                    : 'Upload Logo'}
                                            </Button>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            Max 2MB, JPEG/PNG/GIF
                                        </p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Company Information Section */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="flex items-center gap-2">
                                        <Building2 className="h-5 w-5" />
                                        Company Information
                                    </CardTitle>
                                    <CardDescription>
                                        {isEditing
                                            ? 'Update your company details'
                                            : 'View your company details'}
                                    </CardDescription>
                                </div>
                                {company.is_active && (
                                    <Badge variant="default" className="gap-1">
                                        <CheckCircle2 className="h-3 w-3" />
                                        Active
                                    </Badge>
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            {isEditing ? (
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2 md:col-span-2">
                                            <Label htmlFor="company_name">Company Name *</Label>
                                            <Input
                                                id="company_name"
                                                value={formData.company_name}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        company_name: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="company_email_address">
                                                Email Address *
                                            </Label>
                                            <Input
                                                id="company_email_address"
                                                type="email"
                                                value={formData.company_email_address}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        company_email_address: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="phone_number">Phone Number *</Label>
                                            <Input
                                                id="phone_number"
                                                type="tel"
                                                value={formData.phone_number}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        phone_number: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="telephone_number">
                                                Telephone Number
                                            </Label>
                                            <Input
                                                id="telephone_number"
                                                type="tel"
                                                value={formData.telephone_number || ''}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        telephone_number: e.target.value,
                                                    })
                                                }
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="registration_number">
                                                Registration Number
                                            </Label>
                                            <Input
                                                id="registration_number"
                                                value={formData.registration_number || ''}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        registration_number: e.target.value,
                                                    })
                                                }
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="tax_number">Tax Number</Label>
                                            <Input
                                                id="tax_number"
                                                value={formData.tax_number || ''}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        tax_number: e.target.value,
                                                    })
                                                }
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="industry">Industry</Label>
                                            <Input
                                                id="industry"
                                                value={formData.industry || ''}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        industry: e.target.value,
                                                    })
                                                }
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="website">Website</Label>
                                            <Input
                                                id="website"
                                                type="url"
                                                value={formData.website || ''}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        website: e.target.value,
                                                    })
                                                }
                                                placeholder="https://example.com"
                                            />
                                        </div>

                                        <div className="space-y-2 md:col-span-2">
                                            <Label htmlFor="physical_address">
                                                Physical Address *
                                            </Label>
                                            <Textarea
                                                id="physical_address"
                                                rows={4}
                                                value={formData.physical_address}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        physical_address: e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2 md:col-span-2">
                                            <Label htmlFor="description">Description</Label>
                                            <Textarea
                                                id="description"
                                                rows={3}
                                                value={formData.description || ''}
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        description: e.target.value,
                                                    })
                                                }
                                                placeholder="Optional company description"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex justify-end gap-2">
                                        <Button type="button" variant="outline" onClick={handleCancel}>
                                            <X className="mr-2 h-4 w-4" />
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={updateMutation.isPending}>
                                            <Save className="mr-2 h-4 w-4" />
                                            {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
                                        </Button>
                                    </div>
                                </form>
                            ) : (
                                <div className="space-y-6">
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div className="space-y-4">
                                            <div>
                                                <h4 className="mb-1 text-sm font-medium text-muted-foreground">
                                                    Company Name
                                                </h4>
                                                <p className="text-base font-semibold">
                                                    {company.company_name}
                                                </p>
                                            </div>

                                            <div>
                                                <div className="mb-1 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                                    <Mail className="h-4 w-4" />
                                                    Email Address
                                                </div>
                                                <p className="text-base">
                                                    {company.company_email_address}
                                                </p>
                                            </div>

                                            <div>
                                                <div className="mb-1 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                                    <Phone className="h-4 w-4" />
                                                    Phone Number
                                                </div>
                                                <p className="text-base">{company.phone_number}</p>
                                            </div>

                                            {company.telephone_number && (
                                                <div>
                                                    <div className="mb-1 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                                        <Phone className="h-4 w-4" />
                                                        Telephone Number
                                                    </div>
                                                    <p className="text-base">
                                                        {company.telephone_number}
                                                    </p>
                                                </div>
                                            )}
                                        </div>

                                        <div className="space-y-4">
                                            {company.registration_number && (
                                                <div>
                                                    <h4 className="mb-1 text-sm font-medium text-muted-foreground">
                                                        Registration Number
                                                    </h4>
                                                    <p className="text-base">
                                                        {company.registration_number}
                                                    </p>
                                                </div>
                                            )}

                                            {company.tax_number && (
                                                <div>
                                                    <h4 className="mb-1 text-sm font-medium text-muted-foreground">
                                                        Tax Number
                                                    </h4>
                                                    <p className="text-base">{company.tax_number}</p>
                                                </div>
                                            )}

                                            {company.industry && (
                                                <div>
                                                    <h4 className="mb-1 text-sm font-medium text-muted-foreground">
                                                        Industry
                                                    </h4>
                                                    <p className="text-base">{company.industry}</p>
                                                </div>
                                            )}

                                            {company.website && (
                                                <div>
                                                    <div className="mb-1 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                                        <Globe className="h-4 w-4" />
                                                        Website
                                                    </div>
                                                    <a
                                                        href={company.website}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-base text-blue-600 hover:underline"
                                                    >
                                                        {company.website}
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div>
                                        <div className="mb-2 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                            <MapPin className="h-4 w-4" />
                                            Physical Address
                                        </div>
                                        <p className="text-base whitespace-pre-wrap">
                                            {company.physical_address}
                                        </p>
                                    </div>

                                    {company.description && (
                                        <div>
                                            <h4 className="mb-2 text-sm font-medium text-muted-foreground">
                                                Description
                                            </h4>
                                            <p className="text-base whitespace-pre-wrap">
                                                {company.description}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
