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
import Heading from '@/components/heading';
import type { Notice } from '@/hooks/queries/use-notices';
import {
    useCreateNotice,
    useUpdateNotice,
    useDeleteNotice,
    downloadNotice,
} from '@/hooks/queries/use-notices';
import { useNoticeStore } from '@/stores/notice-store';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Plus, Trash2, Download, Edit, FileText, Image, FileSpreadsheet, Search } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
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
import { useState, useRef, ChangeEvent } from 'react';
import { useToast } from '@/hooks/use-toast';

interface Props {
    notices: PaginatedData<Notice>;
    };
    filters: {
        search?: string;
    };
    maxFileSize: number;
    allowedExtensions: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notices', href: '/notices' },
];

export default function NoticesIndex({ notices, filters, maxFileSize, allowedExtensions }: Props) {
    const {
        form,
        setForm,
        resetForm,
        showCreateDialog,
        setShowCreateDialog,
        showEditDialog,
        setShowEditDialog,
        selectedNotice,
        setSelectedNotice,
        filePreview,
        setFilePreview,
    } = useNoticeStore();

    const createNotice = useCreateNotice();
    const updateNotice = useUpdateNotice(selectedNotice?.notice_id || 0);
    const deleteNotice = useDeleteNotice();
    const { toast } = useToast();

    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [noticeToDelete, setNoticeToDelete] = useState<Notice | null>(null);
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const fileInputRef = useRef<HTMLInputElement>(null);
    const editFileInputRef = useRef<HTMLInputElement>(null);

    const handleSearch = () => {
        router.get('/notices', { search: searchTerm }, { preserveState: true });
    };

    const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validate file size
            if (file.size > maxFileSize) {
                toast({
                    title: 'Error',
                    description: 'File size exceeds 10MB limit',
                    variant: 'destructive',
                });
                return;
            }

            // Validate file type
            const extension = file.name.split('.').pop()?.toLowerCase();
            if (extension && !allowedExtensions.includes(extension)) {
                toast({
                    title: 'Error',
                    description: `File type not allowed. Allowed types: ${allowedExtensions.join(', ')}`,
                    variant: 'destructive',
                });
                return;
            }

            setForm({ attach_file: file });

            // Create file preview for images
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onloadend = () => {
                    setFilePreview(reader.result as string);
                };
                reader.readAsDataURL(file);
            } else {
                setFilePreview(null);
            }
        }
    };

    const handleCreate = () => {
        if (!form.notice_title || !form.attach_file) {
            toast({
                title: 'Error',
                description: 'Please fill in all required fields',
                variant: 'destructive',
            });
            return;
        }

        createNotice.mutate(
            {
                notice_title: form.notice_title,
                attach_file: form.attach_file,
            },
            {
                onSuccess: () => {
                    setShowCreateDialog(false);
                    resetForm();
                    toast({
                        title: 'Success',
                        description: 'Notice created successfully',
                    });
                },
                onError: (error: Error) => {
                    toast({
                        title: 'Error',
                        description: error?.message || 'Failed to create notice',
                        variant: 'destructive',
                    });
                },
            }
        );
    };

    const handleEdit = (notice: Notice) => {
        setSelectedNotice(notice);
        setShowEditDialog(true);
    };

    const handleUpdate = () => {
        if (!form.notice_title) {
            toast({
                title: 'Error',
                description: 'Please enter a notice title',
                variant: 'destructive',
            });
            return;
        }

        updateNotice.mutate(
            {
                notice_title: form.notice_title,
                attach_file: form.attach_file || undefined,
            },
            {
                onSuccess: () => {
                    setShowEditDialog(false);
                    resetForm();
                    setSelectedNotice(null);
                    toast({
                        title: 'Success',
                        description: 'Notice updated successfully',
                    });
                },
                onError: (error: Error) => {
                    toast({
                        title: 'Error',
                        description: error?.message || 'Failed to update notice',
                        variant: 'destructive',
                    });
                },
            }
        );
    };

    const handleDeleteClick = (notice: Notice) => {
        setNoticeToDelete(notice);
        setDeleteDialogOpen(true);
    };

    const handleDelete = () => {
        if (noticeToDelete) {
            deleteNotice.mutate(noticeToDelete.notice_id, {
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setNoticeToDelete(null);
                    toast({
                        title: 'Success',
                        description: 'Notice deleted successfully',
                    });
                },
                onError: (error: Error) => {
                    toast({
                        title: 'Error',
                        description: error?.message || 'Failed to delete notice',
                        variant: 'destructive',
                    });
                },
            });
        }
    };

    const handleDownload = (notice: Notice) => {
        downloadNotice(notice.notice_id);
    };

    const getFileIcon = (notice: Notice) => {
        if (notice.is_image) {
            return <Image className="h-4 w-4" />;
        } else if (notice.is_spreadsheet) {
            return <FileSpreadsheet className="h-4 w-4" />;
        } else {
            return <FileText className="h-4 w-4" />;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notices" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <Heading>Notices Management</Heading>
                        <p className="text-muted-foreground mt-1">
                            Upload and manage company notices and documents
                        </p>
                    </div>
                    <Button onClick={() => setShowCreateDialog(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Notice
                    </Button>
                </div>

                {/* Search */}
                <Card>
                    <CardHeader>
                        <CardTitle>Search Notices</CardTitle>
                        <CardDescription>Find notices by title or filename</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-2">
                            <div className="flex-1">
                                <Input
                                    placeholder="Search notices..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onKeyPress={(e) => {
                                        if (e.key === 'Enter') {
                                            handleSearch();
                                        }
                                    }}
                                />
                            </div>
                            <Button onClick={handleSearch}>
                                <Search className="mr-2 h-4 w-4" />
                                Search
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Notices Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>All Notices</CardTitle>
                        <CardDescription>
                            Company-wide notices and documents available for all employees
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {notices.data.length === 0 ? (
                            <div className="text-center py-12 text-muted-foreground">
                                No notices found
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <th className="w-12">#</th>
                                            <TableHead>Title</TableHead>
                                            <TableHead>File</TableHead>
                                            <TableHead>Size</TableHead>
                                            <TableHead>Uploaded By</TableHead>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {notices.data.map((notice, index) => (
                                            <TableRow key={notice.notice_id}>
                                                <TableCell>{index + 1}</TableCell>
                                                <TableCell>
                                                    <div className="max-w-xs">
                                                        <p className="font-medium truncate">
                                                            {notice.notice_title}
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        {getFileIcon(notice)}
                                                        <span className="text-sm truncate max-w-xs">
                                                            {notice.file_name}
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {notice.file_size_formatted}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {notice.uploader
                                                        ? `${notice.uploader.firstname} ${notice.uploader.surname}`
                                                        : 'N/A'}
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(notice.created_at).toLocaleDateString()}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDownload(notice)}
                                                            title="Download"
                                                        >
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                        {notice.can_modify && (
                                                            <>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleEdit(notice)}
                                                                    title="Edit"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleDeleteClick(notice)}
                                                                    title="Delete"
                                                                >
                                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                                </Button>
                                                            </>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        {/* Pagination */}
                        {notices.meta && notices.meta.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Showing {notices.meta.from} to {notices.meta.to} of{' '}
                                    {notices.meta.total} notices
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Create Dialog */}
            <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Add New Notice</DialogTitle>
                        <DialogDescription>
                            Upload a document or notice to share with all employees
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="space-y-2">
                            <Label>Notice Title *</Label>
                            <Input
                                placeholder="Enter notice title or description"
                                value={form.notice_title}
                                onChange={(e) => setForm({ notice_title: e.target.value })}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>Attachment File *</Label>
                            <Input
                                ref={fileInputRef}
                                type="file"
                                accept={allowedExtensions.map((ext) => `.${ext}`).join(',')}
                                onChange={handleFileChange}
                            />
                            <p className="text-sm text-muted-foreground">
                                Max file size: 10MB. Allowed types: {allowedExtensions.join(', ')}
                            </p>
                        </div>

                        {filePreview && (
                            <div className="space-y-2">
                                <Label>Preview</Label>
                                <div className="border rounded-md p-4">
                                    <img
                                        src={filePreview}
                                        alt="Preview"
                                        className="max-w-full h-auto max-h-64 mx-auto"
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowCreateDialog(false);
                                resetForm();
                            }}
                        >
                            Cancel
                        </Button>
                        <Button onClick={handleCreate} disabled={createNotice.isPending}>
                            {createNotice.isPending ? 'Uploading...' : 'Upload Notice'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Edit Dialog */}
            <Dialog open={showEditDialog} onOpenChange={setShowEditDialog}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Edit Notice</DialogTitle>
                        <DialogDescription>
                            Update notice information or replace the file
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="space-y-2">
                            <Label>Notice Title *</Label>
                            <Input
                                placeholder="Enter notice title or description"
                                value={form.notice_title}
                                onChange={(e) => setForm({ notice_title: e.target.value })}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>Replace File (Optional)</Label>
                            <Input
                                ref={editFileInputRef}
                                type="file"
                                accept={allowedExtensions.map((ext) => `.${ext}`).join(',')}
                                onChange={handleFileChange}
                            />
                            <p className="text-sm text-muted-foreground">
                                Leave empty to keep the existing file. Max file size: 10MB
                            </p>
                        </div>

                        {selectedNotice && !form.attach_file && (
                            <div className="space-y-2">
                                <Label>Current File</Label>
                                <div className="flex items-center gap-2 p-2 border rounded-md">
                                    {getFileIcon(selectedNotice)}
                                    <span className="text-sm">{selectedNotice.file_name}</span>
                                    <Badge variant="outline" className="ml-auto">
                                        {selectedNotice.file_size_formatted}
                                    </Badge>
                                </div>
                            </div>
                        )}

                        {filePreview && (
                            <div className="space-y-2">
                                <Label>New File Preview</Label>
                                <div className="border rounded-md p-4">
                                    <img
                                        src={filePreview}
                                        alt="Preview"
                                        className="max-w-full h-auto max-h-64 mx-auto"
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowEditDialog(false);
                                resetForm();
                                setSelectedNotice(null);
                            }}
                        >
                            Cancel
                        </Button>
                        <Button onClick={handleUpdate} disabled={updateNotice.isPending}>
                            {updateNotice.isPending ? 'Updating...' : 'Update Notice'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Notice</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete "{noticeToDelete?.notice_title}"? This
                            action cannot be undone and the file will be permanently removed.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete} className="bg-destructive">
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
