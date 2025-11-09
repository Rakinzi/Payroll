import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { create } from 'zustand';
import { format } from 'date-fns';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { CalendarIcon, MoreHorizontal, Search, X } from 'lucide-react';
import {
    DischargedEmployee,
    DISCHARGE_REASONS,
    DISCHARGE_REASON_COLORS,
    useReinstateEmployee,
    useDeleteDischargedEmployee,
} from '@/hooks/queries/use-discharged-employees';
import { cn } from '@/lib/utils';

interface DischargedEmployeesPageProps {
    dischargedEmployees: {
        data: DischargedEmployee[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        discharge_reason?: string;
        start_date?: string;
        end_date?: string;
    };
}

interface DialogState {
    reinstateDialogOpen: boolean;
    deleteDialogOpen: boolean;
    selectedEmployee: DischargedEmployee | null;
    setReinstateDialogOpen: (open: boolean) => void;
    setDeleteDialogOpen: (open: boolean) => void;
    setSelectedEmployee: (employee: DischargedEmployee | null) => void;
}

const useDialogStore = create<DialogState>((set) => ({
    reinstateDialogOpen: false,
    deleteDialogOpen: false,
    selectedEmployee: null,
    setReinstateDialogOpen: (open) => set({ reinstateDialogOpen: open }),
    setDeleteDialogOpen: (open) => set({ deleteDialogOpen: open }),
    setSelectedEmployee: (employee) => set({ selectedEmployee: employee }),
}));

function ReinstateEmployeeDialog() {
    const {
        reinstateDialogOpen,
        setReinstateDialogOpen,
        selectedEmployee,
        setSelectedEmployee,
    } = useDialogStore();
    const [employmentStatus, setEmploymentStatus] = useState('active');
    const [reinstatedDate, setReinstatedDate] = useState<Date | undefined>(new Date());
    const [datePickerOpen, setDatePickerOpen] = useState(false);

    const reinstateEmployeeMutation = useReinstateEmployee(selectedEmployee?.id || '');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedEmployee || !reinstatedDate) return;

        try {
            await reinstateEmployeeMutation.mutateAsync({
                employment_status: employmentStatus,
                reinstated_date: format(reinstatedDate, 'yyyy-MM-dd'),
            });
            setReinstateDialogOpen(false);
            setSelectedEmployee(null);
            setEmploymentStatus('active');
            setReinstatedDate(new Date());
        } catch (error) {
            console.error('Failed to reinstate employee:', error);
        }
    };

    return (
        <Dialog open={reinstateDialogOpen} onOpenChange={setReinstateDialogOpen}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reinstate Employee</DialogTitle>
                    <DialogDescription>
                        Reinstate {selectedEmployee?.firstname} {selectedEmployee?.surname} back to
                        active employment.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="employment_status">Employment Status</Label>
                            <Select
                                value={employmentStatus}
                                onValueChange={setEmploymentStatus}
                            >
                                <SelectTrigger id="employment_status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="probation">Probation</SelectItem>
                                    <SelectItem value="contract">Contract</SelectItem>
                                    <SelectItem value="temporary">Temporary</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label>Reinstated Date</Label>
                            <Popover open={datePickerOpen} onOpenChange={setDatePickerOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className={cn(
                                            'w-full justify-start text-left font-normal',
                                            !reinstatedDate && 'text-muted-foreground'
                                        )}
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {reinstatedDate ? (
                                            format(reinstatedDate, 'PPP')
                                        ) : (
                                            <span>Pick a date</span>
                                        )}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0" align="start">
                                    <Calendar
                                        mode="single"
                                        selected={reinstatedDate}
                                        onSelect={(date) => {
                                            setReinstatedDate(date);
                                            setDatePickerOpen(false);
                                        }}
                                        initialFocus
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setReinstateDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={reinstateEmployeeMutation.isPending}>
                            {reinstateEmployeeMutation.isPending
                                ? 'Reinstating...'
                                : 'Reinstate'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteEmployeeDialog() {
    const {
        deleteDialogOpen,
        setDeleteDialogOpen,
        selectedEmployee,
        setSelectedEmployee,
    } = useDialogStore();

    const deleteEmployeeMutation = useDeleteDischargedEmployee();

    const handleDelete = async () => {
        if (!selectedEmployee) return;

        try {
            await deleteEmployeeMutation.mutateAsync(selectedEmployee.id);
            setDeleteDialogOpen(false);
            setSelectedEmployee(null);
        } catch (error) {
            console.error('Failed to delete employee:', error);
        }
    };

    return (
        <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the discharged
                        employee record for {selectedEmployee?.firstname}{' '}
                        {selectedEmployee?.surname}.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleDelete}
                        disabled={deleteEmployeeMutation.isPending}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {deleteEmployeeMutation.isPending ? 'Deleting...' : 'Delete'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

export default function DischargedEmployeesIndex() {
    const { dischargedEmployees, filters } = usePage<DischargedEmployeesPageProps>().props;
    const { setReinstateDialogOpen, setDeleteDialogOpen, setSelectedEmployee } = useDialogStore();

    const [search, setSearch] = useState(filters.search || '');
    const [dischargeReason, setDischargeReason] = useState(filters.discharge_reason || 'all');
    const [startDate, setStartDate] = useState<Date | undefined>(
        filters.start_date ? new Date(filters.start_date) : undefined
    );
    const [endDate, setEndDate] = useState<Date | undefined>(
        filters.end_date ? new Date(filters.end_date) : undefined
    );
    const [startDatePickerOpen, setStartDatePickerOpen] = useState(false);
    const [endDatePickerOpen, setEndDatePickerOpen] = useState(false);

    const handleSearch = () => {
        router.get(
            '/discharged-employees',
            {
                search: search || undefined,
                discharge_reason: dischargeReason !== 'all' ? dischargeReason : undefined,
                start_date: startDate ? format(startDate, 'yyyy-MM-dd') : undefined,
                end_date: endDate ? format(endDate, 'yyyy-MM-dd') : undefined,
            },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        setSearch('');
        setDischargeReason('all');
        setStartDate(undefined);
        setEndDate(undefined);
        router.get('/discharged-employees', {}, { preserveState: true });
    };

    const handleReinstate = (employee: DischargedEmployee) => {
        setSelectedEmployee(employee);
        setReinstateDialogOpen(true);
    };

    const handleDelete = (employee: DischargedEmployee) => {
        setSelectedEmployee(employee);
        setDeleteDialogOpen(true);
    };

    const handleViewDetails = (employee: DischargedEmployee) => {
        router.visit(`/discharged-employees/${employee.id}`);
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Discharged Employees
                    </h2>
                </div>
            }
        >
            <Head title="Discharged Employees" />

            <div className="space-y-4">
                <div className="rounded-lg border bg-card p-4">
                    <div className="mb-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-2">
                            <Label htmlFor="search">Search</Label>
                            <div className="relative">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="search"
                                    placeholder="Search employees..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                    className="pl-8"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="discharge_reason">Discharge Reason</Label>
                            <Select value={dischargeReason} onValueChange={setDischargeReason}>
                                <SelectTrigger id="discharge_reason">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Reasons</SelectItem>
                                    {Object.values(DISCHARGE_REASONS).map((reason) => (
                                        <SelectItem key={reason} value={reason}>
                                            {reason}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label>Start Date</Label>
                            <Popover open={startDatePickerOpen} onOpenChange={setStartDatePickerOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className={cn(
                                            'w-full justify-start text-left font-normal',
                                            !startDate && 'text-muted-foreground'
                                        )}
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {startDate ? format(startDate, 'PPP') : <span>Pick date</span>}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0" align="start">
                                    <Calendar
                                        mode="single"
                                        selected={startDate}
                                        onSelect={(date) => {
                                            setStartDate(date);
                                            setStartDatePickerOpen(false);
                                        }}
                                        initialFocus
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>

                        <div className="space-y-2">
                            <Label>End Date</Label>
                            <Popover open={endDatePickerOpen} onOpenChange={setEndDatePickerOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className={cn(
                                            'w-full justify-start text-left font-normal',
                                            !endDate && 'text-muted-foreground'
                                        )}
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {endDate ? format(endDate, 'PPP') : <span>Pick date</span>}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0" align="start">
                                    <Calendar
                                        mode="single"
                                        selected={endDate}
                                        onSelect={(date) => {
                                            setEndDate(date);
                                            setEndDatePickerOpen(false);
                                        }}
                                        initialFocus
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button onClick={handleSearch}>
                            <Search className="mr-2 h-4 w-4" />
                            Search
                        </Button>
                        <Button variant="outline" onClick={handleClearFilters}>
                            <X className="mr-2 h-4 w-4" />
                            Clear Filters
                        </Button>
                    </div>
                </div>

                <div className="rounded-lg border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Employee ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Cost Center</TableHead>
                                <TableHead>Discharge Date</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead>Days Since</TableHead>
                                <TableHead>Discharged By</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {dischargedEmployees.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center text-muted-foreground">
                                        No discharged employees found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                dischargedEmployees.data.map((employee) => (
                                    <TableRow key={employee.id}>
                                        <TableCell className="font-medium">
                                            {employee.emp_system_id}
                                        </TableCell>
                                        <TableCell>
                                            {employee.firstname} {employee.surname}
                                        </TableCell>
                                        <TableCell>
                                            {employee.cost_center?.center_name || 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            {employee.is_ex_on
                                                ? format(new Date(employee.is_ex_on), 'PPP')
                                                : 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    DISCHARGE_REASON_COLORS[
                                                        employee.employment_status as keyof typeof DISCHARGE_REASON_COLORS
                                                    ] || 'default'
                                                }
                                            >
                                                {employee.employment_status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {employee.days_since_discharge !== null
                                                ? `${employee.days_since_discharge} days`
                                                : 'N/A'}
                                        </TableCell>
                                        <TableCell>{employee.is_ex_by || 'N/A'}</TableCell>
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
                                                    <DropdownMenuItem
                                                        onClick={() => handleViewDetails(employee)}
                                                    >
                                                        View Details
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        onClick={() => handleReinstate(employee)}
                                                    >
                                                        Reinstate Employee
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => handleDelete(employee)}
                                                        className="text-destructive"
                                                    >
                                                        Delete Permanently
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {dischargedEmployees.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <div className="text-sm text-muted-foreground">
                            Showing {dischargedEmployees.data.length} of {dischargedEmployees.total}{' '}
                            discharged employees
                        </div>
                        <div className="flex gap-2">
                            {dischargedEmployees.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.get(
                                            `/discharged-employees?page=${dischargedEmployees.current_page - 1}`
                                        )
                                    }
                                >
                                    Previous
                                </Button>
                            )}
                            {dischargedEmployees.current_page < dischargedEmployees.last_page && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.get(
                                            `/discharged-employees?page=${dischargedEmployees.current_page + 1}`
                                        )
                                    }
                                >
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>

            <ReinstateEmployeeDialog />
            <DeleteEmployeeDialog />
        </AppLayout>
    );
}
