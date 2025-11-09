import { Head, Link, usePage } from '@inertiajs/react';
import { useDialog } from '@/hooks/use-dialog';
import { useState } from 'react';
import { create } from 'zustand';
import AppLayout from '@/components/layouts/app-layout';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { AlertCircle, Download, FileUp, Info, MoreHorizontal, Upload } from 'lucide-react';
import {
    ImportSession,
    useUploadFile,
    useDeleteSession,
    useExportData,
} from '@/hooks/queries/use-spreadsheet-import';

interface SpreadsheetImportPageProps {
    sessions: {
        data: ImportSession[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    supportedTypes: Record<string, string>;
}

interface DialogState {
    uploadDialogOpen: boolean;
    deleteDialogOpen: boolean;
    exportDialogOpen: boolean;
    selectedSession: ImportSession | null;
    setUploadDialogOpen: (open: boolean) => void;
    setDeleteDialogOpen: (open: boolean) => void;
    setExportDialogOpen: (open: boolean) => void;
    setSelectedSession: (session: ImportSession | null) => void;
}

const useDialogStore = create<DialogState>((set) => ({
    uploadDialogOpen: false,
    deleteDialogOpen: false,
    exportDialogOpen: false,
    selectedSession: null,
    setUploadDialogOpen: (open) => set({ uploadDialogOpen: open }),
    setDeleteDialogOpen: (open) => set({ deleteDialogOpen: open }),
    setExportDialogOpen: (open) => set({ exportDialogOpen: open }),
    setSelectedSession: (session) => set({ selectedSession: session }),
}));

function UploadFileDialog() {
    const { uploadDialogOpen, setUploadDialogOpen } = useDialogStore();
    const { supportedTypes } = usePage<SpreadsheetImportPageProps>().props;

    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [importType, setImportType] = useState<string>('employees');

    const uploadMutation = useUploadFile();
    const dialog = useDialog();

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file && (file.type === 'text/csv' || file.name.endsWith('.csv'))) {
            setSelectedFile(file);
        } else {
            dialog.alert('Please select a valid CSV file', 'Invalid File Type');
        }
    };

    const handleUpload = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!selectedFile) return;

        try {
            await uploadMutation.mutateAsync({
                file: selectedFile,
                import_type: importType as any,
            });
            handleClose();
        } catch (error) {
            console.error('Upload failed:', error);
        }
    };

    const handleClose = () => {
        setUploadDialogOpen(false);
        setSelectedFile(null);
        setImportType('employees');
    };

    return (
        <Dialog open={uploadDialogOpen} onOpenChange={setUploadDialogOpen}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Upload CSV File</DialogTitle>
                    <DialogDescription>
                        Upload a CSV file to import data in bulk. Maximum file size: 50MB.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleUpload}>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="import_type">Import Type *</Label>
                            <Select value={importType} onValueChange={setImportType}>
                                <SelectTrigger id="import_type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(supportedTypes).map(([key, label]) => (
                                        <SelectItem key={key} value={key}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="file">Select CSV File *</Label>
                            <Input
                                id="file"
                                type="file"
                                accept=".csv"
                                onChange={handleFileSelect}
                                required
                            />
                            <p className="text-xs text-muted-foreground">
                                Maximum file size: 50MB. Only CSV files are supported.
                            </p>
                        </div>

                        {selectedFile && (
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    <strong>Selected File:</strong> {selectedFile.name}
                                    <br />
                                    <strong>Size:</strong>{' '}
                                    {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
                                </AlertDescription>
                            </Alert>
                        )}

                        <Alert>
                            <Info className="h-4 w-4" />
                            <AlertDescription>
                                <strong>CSV Format Requirements:</strong>
                                <ul className="mt-2 ml-4 text-sm list-disc">
                                    <li>First row must contain column headers</li>
                                    <li>Use comma (,) as delimiter</li>
                                    <li>Enclose text fields in double quotes if they contain commas</li>
                                    <li>Date fields should be in YYYY-MM-DD format</li>
                                    <li>Empty cells are allowed for optional fields</li>
                                </ul>
                            </AlertDescription>
                        </Alert>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={!selectedFile || uploadMutation.isPending}>
                            {uploadMutation.isPending ? (
                                <>
                                    <Upload className="mr-2 h-4 w-4 animate-spin" />
                                    Uploading...
                                </>
                            ) : (
                                <>
                                    <Upload className="mr-2 h-4 w-4" />
                                    Upload & Process
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteSessionDialog() {
    const { deleteDialogOpen, setDeleteDialogOpen, selectedSession, setSelectedSession } =
        useDialogStore();
    const deleteMutation = useDeleteSession();

    const handleDelete = async () => {
        if (!selectedSession) return;

        try {
            await deleteMutation.mutateAsync(selectedSession.id);
            setDeleteDialogOpen(false);
            setSelectedSession(null);
        } catch (error) {
            console.error('Failed to delete session:', error);
        }
    };

    return (
        <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the import session
                        for <strong>{selectedSession?.file_name}</strong>.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleDelete}
                        disabled={deleteMutation.isPending}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {deleteMutation.isPending ? 'Deleting...' : 'Delete'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

function ExportDataDialog() {
    const { exportDialogOpen, setExportDialogOpen } = useDialogStore();
    const { supportedTypes } = usePage<SpreadsheetImportPageProps>().props;

    const [exportType, setExportType] = useState<string>('employees');

    const exportMutation = useExportData();

    const handleExport = async (e: React.FormEvent) => {
        e.preventDefault();

        try {
            await exportMutation.mutateAsync({
                export_type: exportType as any,
                filters: {},
            });
            setExportDialogOpen(false);
        } catch (error) {
            console.error('Export failed:', error);
        }
    };

    return (
        <Dialog open={exportDialogOpen} onOpenChange={setExportDialogOpen}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Export Data to CSV</DialogTitle>
                    <DialogDescription>
                        Export data to CSV format for backup or external processing.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleExport}>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="export_type">Data Type *</Label>
                            <Select value={exportType} onValueChange={setExportType}>
                                <SelectTrigger id="export_type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="employees">Employees</SelectItem>
                                    <SelectItem value="payroll">Payroll</SelectItem>
                                    <SelectItem value="transactions">Transactions</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setExportDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={exportMutation.isPending}>
                            {exportMutation.isPending ? (
                                <>
                                    <Download className="mr-2 h-4 w-4 animate-spin" />
                                    Exporting...
                                </>
                            ) : (
                                <>
                                    <Download className="mr-2 h-4 w-4" />
                                    Export
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function SpreadsheetImportIndex() {
    const { sessions, supportedTypes } = usePage<SpreadsheetImportPageProps>().props;
    const {
        setUploadDialogOpen,
        setDeleteDialogOpen,
        setExportDialogOpen,
        setSelectedSession,
    } = useDialogStore();

    const handleUpload = () => {
        setUploadDialogOpen(true);
    };

    const handleExport = () => {
        setExportDialogOpen(true);
    };

    const handleDelete = (session: ImportSession) => {
        setSelectedSession(session);
        setDeleteDialogOpen(true);
    };

    const getStatusBadge = (status: string) => {
        const variants: Record<string, any> = {
            uploaded: 'secondary',
            processing: 'default',
            preview: 'outline',
            completed: 'default',
            failed: 'destructive',
        };

        return (
            <Badge variant={variants[status] || 'secondary'}>
                {status.charAt(0).toUpperCase() + status.slice(1)}
            </Badge>
        );
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Spreadsheet Import & Export
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Bulk data import and export via CSV files
                    </p>
                </div>
            }
        >
            <Head title="Spreadsheet Import" />

            <div className="space-y-6">
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Bulk Data Operations:</strong> Import large datasets via CSV files or
                        export data for backup and external processing.
                    </AlertDescription>
                </Alert>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <FileUp className="h-5 w-5" />
                                    Import Sessions
                                </CardTitle>
                                <CardDescription>
                                    Upload and manage your import sessions
                                </CardDescription>
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" onClick={handleExport}>
                                    <Download className="mr-2 h-4 w-4" />
                                    Export Data
                                </Button>
                                <Button onClick={handleUpload}>
                                    <Upload className="mr-2 h-4 w-4" />
                                    Upload CSV
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>File Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Total Rows</TableHead>
                                    <TableHead>Errors</TableHead>
                                    <TableHead>Progress</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sessions.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="text-center text-muted-foreground"
                                        >
                                            No import sessions yet. Upload your first CSV file to get
                                            started.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    sessions.data.map((session) => (
                                        <TableRow key={session.id}>
                                            <TableCell>
                                                <div className="font-medium">{session.file_name}</div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {supportedTypes[session.import_type]}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{getStatusBadge(session.status)}</TableCell>
                                            <TableCell>{session.total_rows}</TableCell>
                                            <TableCell>
                                                {session.error_rows > 0 ? (
                                                    <div className="flex items-center gap-1 text-destructive">
                                                        <AlertCircle className="h-4 w-4" />
                                                        {session.error_rows}
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">0</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <div className="text-sm">
                                                        {session.progress_percentage.toFixed(0)}%
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm">
                                                    {new Date(session.created_at).toLocaleDateString()}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                            <span className="sr-only">Actions</span>
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                        {session.status === 'preview' && (
                                                            <DropdownMenuItem asChild>
                                                                <Link
                                                                    href={`/spreadsheet-import/${session.id}/preview`}
                                                                >
                                                                    View Preview
                                                                </Link>
                                                            </DropdownMenuItem>
                                                        )}
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            onClick={() => handleDelete(session)}
                                                            className="text-destructive"
                                                        >
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <UploadFileDialog />
            <DeleteSessionDialog />
            <ExportDataDialog />
        </AppLayout>
    );
}
