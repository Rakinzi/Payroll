import { Head, usePage } from '@inertiajs/react';
import { useDialog } from '@/hooks/use-dialog';
import AppLayout from '@/components/layouts/app-layout';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { AlertCircle, CheckCircle, FileText, Info, Play } from 'lucide-react';
import {
    ImportError,
    ImportSessionDetails,
    useProcessImport,
    useImportStatus,
} from '@/hooks/queries/use-spreadsheet-import';
import { useEffect } from 'react';

interface ImportPreviewPageProps {
    session: ImportSessionDetails;
    previewData: Record<string, any>[];
    errorsByRow: Record<number, ImportError[]>;
}

export default function ImportPreviewIndex() {
    const { session, previewData, errorsByRow } = usePage<ImportPreviewPageProps>().props;
    const processMutation = useProcessImport(session.id);
    const dialog = useDialog();

    // Poll status if processing
    const { data: statusData } = useImportStatus(
        session.id,
        session.status === 'processing'
    );

    // Reload page when status changes
    useEffect(() => {
        if (statusData && (statusData.status === 'completed' || statusData.status === 'failed')) {
            window.location.reload();
        }
    }, [statusData]);

    const handleProcess = async () => {
        if (!session.can_be_processed) {
            dialog.alert('Session cannot be processed due to errors or invalid status', 'Cannot Process');
            return;
        }

        const confirmed = await dialog.confirm(
            'Are you sure you want to process all valid rows? This action cannot be undone.',
            {
                title: 'Confirm Processing',
                confirmText: 'Process',
                variant: 'destructive',
            }
        );

        if (!confirmed) {
            return;
        }

        try {
            await processMutation.mutateAsync();
        } catch (error) {
            console.error('Processing failed:', error);
        }
    };

    const getRowStatus = (rowNumber: number): 'valid' | 'error' => {
        return errorsByRow[rowNumber] ? 'error' : 'valid';
    };

    const getRowErrors = (rowNumber: number): ImportError[] => {
        return errorsByRow[rowNumber] || [];
    };

    const validRows = previewData.length - session.error_rows;

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Import Preview
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Review data before final import - {session.file_name}
                    </p>
                </div>
            }
        >
            <Head title="Import Preview" />

            <div className="space-y-6">
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Data Preview:</strong> Review the imported data below. Rows with
                        errors are highlighted in red and will be skipped during import.
                    </AlertDescription>
                </Alert>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Total Rows</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{session.total_rows}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-green-600">
                                Valid Rows
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-2">
                                <CheckCircle className="h-5 w-5 text-green-600" />
                                <div className="text-2xl font-bold text-green-600">{validRows}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-red-600">
                                Error Rows
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-2">
                                <AlertCircle className="h-5 w-5 text-red-600" />
                                <div className="text-2xl font-bold text-red-600">
                                    {session.error_rows}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium text-blue-600">
                                Success Rate
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">
                                {session.success_percentage.toFixed(1)}%
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="h-5 w-5" />
                                    Data Preview
                                </CardTitle>
                                <CardDescription>
                                    Review all rows before processing. Rows with errors will be
                                    skipped.
                                </CardDescription>
                            </div>
                            {session.can_be_processed && (
                                <Button
                                    onClick={handleProcess}
                                    disabled={processMutation.isPending || session.status !== 'preview'}
                                    size="lg"
                                >
                                    {processMutation.isPending ? (
                                        <>
                                            <Play className="mr-2 h-4 w-4 animate-spin" />
                                            Processing...
                                        </>
                                    ) : (
                                        <>
                                            <Play className="mr-2 h-4 w-4" />
                                            Process {validRows} Valid Row{validRows !== 1 ? 's' : ''}
                                        </>
                                    )}
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {!session.can_be_processed && session.error_rows === session.total_rows && (
                            <Alert variant="destructive" className="mb-4">
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    <strong>All rows have errors.</strong> Please fix the errors in
                                    your CSV file and upload again.
                                </AlertDescription>
                            </Alert>
                        )}

                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-16">Row</TableHead>
                                        <TableHead className="w-24">Status</TableHead>
                                        {previewData.length > 0 &&
                                            Object.keys(previewData[0]).map((key) => (
                                                <TableHead key={key}>{key}</TableHead>
                                            ))}
                                        <TableHead>Errors</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {previewData.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={100}
                                                className="text-center text-muted-foreground"
                                            >
                                                No data to preview
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        previewData.map((row, index) => {
                                            const rowNumber = index + 1;
                                            const status = getRowStatus(rowNumber);
                                            const errors = getRowErrors(rowNumber);

                                            return (
                                                <TableRow
                                                    key={index}
                                                    className={
                                                        status === 'error'
                                                            ? 'bg-red-50 hover:bg-red-100'
                                                            : ''
                                                    }
                                                >
                                                    <TableCell className="font-medium">
                                                        {rowNumber}
                                                    </TableCell>
                                                    <TableCell>
                                                        {status === 'error' ? (
                                                            <Badge variant="destructive">Error</Badge>
                                                        ) : (
                                                            <Badge
                                                                variant="default"
                                                                className="bg-green-600"
                                                            >
                                                                Valid
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    {Object.values(row).map((value, i) => (
                                                        <TableCell key={i}>
                                                            <div className="max-w-[200px] truncate">
                                                                {value?.toString() || '-'}
                                                            </div>
                                                        </TableCell>
                                                    ))}
                                                    <TableCell>
                                                        {errors.length > 0 ? (
                                                            <div className="space-y-1">
                                                                {errors.map((error, i) => (
                                                                    <div
                                                                        key={i}
                                                                        className="text-xs text-red-600"
                                                                    >
                                                                        {error.column_name && (
                                                                            <strong>
                                                                                {error.column_name}:
                                                                            </strong>
                                                                        )}{' '}
                                                                        {error.error_message}
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        ) : (
                                                            <span className="text-muted-foreground text-xs">
                                                                No errors
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {previewData.length > 0 && (
                            <div className="mt-4 text-sm text-muted-foreground">
                                Showing {previewData.length} row{previewData.length !== 1 ? 's' : ''}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {session.error_rows > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-red-600">
                                <AlertCircle className="h-5 w-5" />
                                Error Summary
                            </CardTitle>
                            <CardDescription>
                                {session.error_rows} row{session.error_rows !== 1 ? 's' : ''} with
                                validation errors
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {Object.entries(errorsByRow).map(([rowNumber, errors]) => (
                                    <div key={rowNumber} className="border-l-4 border-red-500 pl-4 py-2">
                                        <div className="font-medium text-sm">
                                            Row {rowNumber}
                                        </div>
                                        <ul className="mt-1 space-y-1">
                                            {errors.map((error, i) => (
                                                <li key={i} className="text-sm text-red-600">
                                                    {error.column_name && (
                                                        <strong>{error.column_name}:</strong>
                                                    )}{' '}
                                                    {error.error_message}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
