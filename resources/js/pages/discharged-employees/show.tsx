import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { create } from 'zustand';
import { format } from 'date-fns';
import AppLayout from '@/components/layouts/app-layout';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ArrowLeft, CalendarIcon, RefreshCcw, Trash2 } from 'lucide-react';
import {
    DischargedEmployee,
    DISCHARGE_REASON_COLORS,
    useReinstateEmployee,
    useDeleteDischargedEmployee,
} from '@/hooks/queries/use-discharged-employees';
import { cn } from '@/lib/utils';

interface DischargedEmployeeShowPageProps {
    dischargedEmployee: DischargedEmployee;
}

interface DialogState {
    reinstateDialogOpen: boolean;
    deleteDialogOpen: boolean;
    setReinstateDialogOpen: (open: boolean) => void;
    setDeleteDialogOpen: (open: boolean) => void;
}

const useDialogStore = create<DialogState>((set) => ({
    reinstateDialogOpen: false,
    deleteDialogOpen: false,
    setReinstateDialogOpen: (open) => set({ reinstateDialogOpen: open }),
    setDeleteDialogOpen: (open) => set({ deleteDialogOpen: open }),
}));

function ReinstateEmployeeDialog({ employee }: { employee: DischargedEmployee }) {
    const { reinstateDialogOpen, setReinstateDialogOpen } = useDialogStore();
    const [employmentStatus, setEmploymentStatus] = useState('active');
    const [reinstatedDate, setReinstatedDate] = useState<Date | undefined>(new Date());
    const [datePickerOpen, setDatePickerOpen] = useState(false);

    const reinstateEmployeeMutation = useReinstateEmployee(employee.id);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!reinstatedDate) return;

        try {
            await reinstateEmployeeMutation.mutateAsync({
                employment_status: employmentStatus,
                reinstated_date: format(reinstatedDate, 'yyyy-MM-dd'),
            });
            setReinstateDialogOpen(false);
            router.visit('/discharged-employees');
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
                        Reinstate {employee.firstname} {employee.surname} back to active employment.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="employment_status">Employment Status</Label>
                            <Select value={employmentStatus} onValueChange={setEmploymentStatus}>
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
                            {reinstateEmployeeMutation.isPending ? 'Reinstating...' : 'Reinstate'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteEmployeeDialog({ employee }: { employee: DischargedEmployee }) {
    const { deleteDialogOpen, setDeleteDialogOpen } = useDialogStore();
    const deleteEmployeeMutation = useDeleteDischargedEmployee();

    const handleDelete = async () => {
        try {
            await deleteEmployeeMutation.mutateAsync(employee.id);
            setDeleteDialogOpen(false);
            router.visit('/discharged-employees');
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
                        employee record for {employee.firstname} {employee.surname}.
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

export default function DischargedEmployeeShow() {
    const { dischargedEmployee } = usePage<DischargedEmployeeShowPageProps>().props;
    const { setReinstateDialogOpen, setDeleteDialogOpen } = useDialogStore();

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => router.visit('/discharged-employees')}
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back
                        </Button>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Discharged Employee Details
                        </h2>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setReinstateDialogOpen(true)}>
                            <RefreshCcw className="mr-2 h-4 w-4" />
                            Reinstate
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setDeleteDialogOpen(true)}
                        >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`${dischargedEmployee.firstname} ${dischargedEmployee.surname}`} />

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Employee Information</CardTitle>
                        <CardDescription>
                            Personal and employment details of the discharged employee
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Employee ID
                                </Label>
                                <p className="mt-1 text-sm">{dischargedEmployee.emp_system_id}</p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Full Name
                                </Label>
                                <p className="mt-1 text-sm">
                                    {dischargedEmployee.firstname}{' '}
                                    {dischargedEmployee.middlename && `${dischargedEmployee.middlename} `}
                                    {dischargedEmployee.surname}
                                </p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Cost Center
                                </Label>
                                <p className="mt-1 text-sm">
                                    {dischargedEmployee.cost_center?.center_name || 'N/A'}
                                    {dischargedEmployee.cost_center?.center_code &&
                                        ` (${dischargedEmployee.cost_center.center_code})`}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Discharge Information</CardTitle>
                        <CardDescription>Details about the employee's discharge</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Discharge Date
                                </Label>
                                <p className="mt-1 text-sm">
                                    {dischargedEmployee.is_ex_on
                                        ? format(new Date(dischargedEmployee.is_ex_on), 'PPP')
                                        : 'N/A'}
                                </p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Discharge Reason
                                </Label>
                                <div className="mt-1">
                                    <Badge
                                        variant={
                                            DISCHARGE_REASON_COLORS[
                                                dischargedEmployee.employment_status as keyof typeof DISCHARGE_REASON_COLORS
                                            ] || 'default'
                                        }
                                    >
                                        {dischargedEmployee.employment_status}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Discharged By
                                </Label>
                                <p className="mt-1 text-sm">{dischargedEmployee.is_ex_by || 'N/A'}</p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Days Since Discharge
                                </Label>
                                <p className="mt-1 text-sm">
                                    {dischargedEmployee.days_since_discharge !== null
                                        ? `${dischargedEmployee.days_since_discharge} days`
                                        : 'N/A'}
                                </p>
                            </div>
                            {dischargedEmployee.discharge_notes && (
                                <div className="md:col-span-2">
                                    <Label className="text-sm font-medium text-muted-foreground">
                                        Discharge Notes
                                    </Label>
                                    <p className="mt-1 text-sm">{dischargedEmployee.discharge_notes}</p>
                                </div>
                            )}
                            {dischargedEmployee.reinstated_date && (
                                <div>
                                    <Label className="text-sm font-medium text-muted-foreground">
                                        Reinstated Date
                                    </Label>
                                    <p className="mt-1 text-sm">
                                        {format(new Date(dischargedEmployee.reinstated_date), 'PPP')}
                                    </p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Record Details</CardTitle>
                        <CardDescription>System metadata for this record</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Created At
                                </Label>
                                <p className="mt-1 text-sm">
                                    {format(new Date(dischargedEmployee.created_at), 'PPP p')}
                                </p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium text-muted-foreground">
                                    Last Updated
                                </Label>
                                <p className="mt-1 text-sm">
                                    {format(new Date(dischargedEmployee.updated_at), 'PPP p')}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <ReinstateEmployeeDialog employee={dischargedEmployee} />
            <DeleteEmployeeDialog employee={dischargedEmployee} />
        </AppLayout>
    );
}
