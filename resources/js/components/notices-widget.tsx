import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useLatestNotices, downloadNotice } from '@/hooks/queries/use-notices';
import { FileText, Image, FileSpreadsheet, Download, ChevronRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { Notice } from '@/hooks/queries/use-notices';

interface NoticesWidgetProps {
    limit?: number;
}

export default function NoticesWidget({ limit = 5 }: NoticesWidgetProps) {
    const { data: notices, isLoading, error } = useLatestNotices(limit);

    const getFileIcon = (notice: Notice) => {
        if (notice.is_image) {
            return <Image className="h-4 w-4 text-blue-500" />;
        } else if (notice.is_spreadsheet) {
            return <FileSpreadsheet className="h-4 w-4 text-green-500" />;
        } else {
            return <FileText className="h-4 w-4 text-gray-500" />;
        }
    };

    const handleDownload = (notice: Notice) => {
        downloadNotice(notice.notice_id);
    };

    if (isLoading) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Latest Notices</CardTitle>
                    <CardDescription>Recent company notices and documents</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-6 text-muted-foreground">
                        Loading notices...
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (error) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Latest Notices</CardTitle>
                    <CardDescription>Recent company notices and documents</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-6 text-destructive">
                        Failed to load notices
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>Latest Notices</CardTitle>
                        <CardDescription>Recent company notices and documents</CardDescription>
                    </div>
                    <Link
                        href="/notices"
                        className="inline-flex items-center text-sm font-medium text-primary hover:underline"
                    >
                        View All
                        <ChevronRight className="ml-1 h-4 w-4" />
                    </Link>
                </div>
            </CardHeader>
            <CardContent>
                {!notices || notices.length === 0 ? (
                    <div className="text-center py-6 text-muted-foreground">
                        No notices available
                    </div>
                ) : (
                    <div className="space-y-3">
                        {notices.map((notice) => (
                            <div
                                key={notice.notice_id}
                                className="flex items-center gap-3 p-3 rounded-lg border hover:bg-accent transition-colors"
                            >
                                <div className="flex-shrink-0">{getFileIcon(notice)}</div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-medium text-sm truncate">
                                        {notice.notice_title}
                                    </p>
                                    <div className="flex items-center gap-2 mt-1">
                                        <p className="text-xs text-muted-foreground truncate">
                                            {notice.file_name}
                                        </p>
                                        <Badge variant="outline" className="text-xs">
                                            {notice.file_size_formatted}
                                        </Badge>
                                    </div>
                                    {notice.uploader && (
                                        <p className="text-xs text-muted-foreground mt-1">
                                            By {notice.uploader.firstname} {notice.uploader.surname}{' '}
                                            • {new Date(notice.created_at).toLocaleDateString()}
                                        </p>
                                    )}
                                </div>
                                <div className="flex-shrink-0">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleDownload(notice)}
                                        title="Download"
                                    >
                                        <Download className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
