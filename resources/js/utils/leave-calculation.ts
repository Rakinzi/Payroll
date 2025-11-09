import moment from 'moment';

export type WorkingDaysPolicy = '5_day' | '6_day' | '7_day';

export interface CompanyWorkingPolicy {
    working_days_policy: WorkingDaysPolicy;
    standard_working_days_per_month: number;
    exclude_saturdays: boolean;
    exclude_sundays: boolean;
    exclude_public_holidays: boolean;
    custom_holidays: string[]; // Array of dates in 'YYYY-MM-DD' format
}

export interface LeaveBreakdown {
    total_days: number;
    working_days: number;
    weekend_days: number;
    public_holidays: number;
    custom_holidays: number;
    excluded_dates: Array<{
        date: string;
        type: 'weekend' | 'public_holiday' | 'custom_holiday';
        name: string;
    }>;
}

interface ZimbabweHoliday {
    name: string;
    date: string;
    type: 'public' | 'observance';
}

/**
 * Official Zimbabwe Public Holidays (excluding observances)
 * These are gazetted public holidays only
 */
const OFFICIAL_ZW_HOLIDAY_NAMES = [
    "New Year's Day",
    'New Year Holiday',
    'Robert Gabriel Mugabe National Youth Day',
    'Good Friday',
    'Easter Saturday',
    'Easter Monday',
    'Independence Day',
    "Workers' Day",
    'Africa Day',
    "Heroes' Day",
    'Defence Forces Day',
    'Unity Day',
    'Christmas Day',
    'Boxing Day',
    'Family Day',
];

/**
 * Calculate Easter Sunday using Computus algorithm
 */
function getEasterDate(year: number): moment.Moment {
    const a = year % 19;
    const b = Math.floor(year / 100);
    const c = year % 100;
    const d = Math.floor(b / 4);
    const e = b % 4;
    const f = Math.floor((b + 8) / 25);
    const g = Math.floor((b - f + 1) / 3);
    const h = (19 * a + b - d - g + 15) % 30;
    const i = Math.floor(c / 4);
    const k = c % 4;
    const l = (32 + 2 * e + 2 * i - h - k) % 7;
    const m = Math.floor((a + 11 * h + 22 * l) / 451);
    const month = Math.floor((h + l - 7 * m + 114) / 31);
    const day = ((h + l - 7 * m + 114) % 31) + 1;

    return moment({ year, month: month - 1, day });
}

/**
 * Get Zimbabwe official public holidays for a given year
 * Filters out observances (Father's Day, Mother's Day, etc.)
 */
function getZimbabwePublicHolidays(year: number): ZimbabweHoliday[] {
    const holidays: ZimbabweHoliday[] = [];

    // Fixed date holidays
    const fixedHolidays = [
        { name: "New Year's Day", date: `${year}-01-01` },
        { name: 'Robert Gabriel Mugabe National Youth Day', date: `${year}-02-21` },
        { name: 'Independence Day', date: `${year}-04-18` },
        { name: "Workers' Day", date: `${year}-05-01` },
        { name: 'Africa Day', date: `${year}-05-25` },
        { name: "Heroes' Day", date: `${year}-08-11` }, // 2nd Monday (approximately)
        { name: 'Defence Forces Day', date: `${year}-08-12` }, // Day after Heroes' Day
        { name: 'Unity Day', date: `${year}-12-22` },
        { name: 'Christmas Day', date: `${year}-12-25` },
        { name: 'Boxing Day', date: `${year}-12-26` },
    ];

    fixedHolidays.forEach((holiday) => {
        holidays.push({
            name: holiday.name,
            date: holiday.date,
            type: 'public',
        });
    });

    // Easter-based holidays (moveable)
    const easter = getEasterDate(year);
    holidays.push(
        {
            name: 'Good Friday',
            date: easter.clone().subtract(2, 'days').format('YYYY-MM-DD'),
            type: 'public',
        },
        {
            name: 'Easter Saturday',
            date: easter.clone().subtract(1, 'day').format('YYYY-MM-DD'),
            type: 'public',
        },
        {
            name: 'Easter Monday',
            date: easter.clone().add(1, 'day').format('YYYY-MM-DD'),
            type: 'public',
        }
    );

    return holidays;
}

/**
 * Get public holidays within a date range, handling rollover for weekends
 */
function getPublicHolidaysInRange(
    startDate: moment.Moment,
    endDate: moment.Moment,
    excludeSaturdays: boolean,
    excludeSundays: boolean
): string[] {
    const holidays: string[] = [];
    const years = [];
    for (let year = startDate.year(); year <= endDate.year(); year++) {
        years.push(year);
    }

    years.forEach((year) => {
        const yearHolidays = getZimbabwePublicHolidays(year);

        yearHolidays.forEach((holiday) => {
            const holidayDate = moment(holiday.date);

            // Only include holidays within the date range
            if (holidayDate.isBetween(startDate, endDate, 'day', '[]')) {
                const dayOfWeek = holidayDate.day();

                // Handle holiday rollover
                if (dayOfWeek === 0 && excludeSundays) {
                    // Sunday -> Monday
                    const observedDate = holidayDate.clone().add(1, 'day');
                    if (observedDate.isSameOrBefore(endDate)) {
                        holidays.push(observedDate.format('YYYY-MM-DD'));
                    }
                } else if (dayOfWeek === 6 && excludeSaturdays) {
                    // Saturday -> Monday
                    const observedDate = holidayDate.clone().add(2, 'days');
                    if (observedDate.isSameOrBefore(endDate)) {
                        holidays.push(observedDate.format('YYYY-MM-DD'));
                    }
                } else {
                    // Holiday falls on working day (or weekend not excluded)
                    const isExcludedWeekend =
                        (dayOfWeek === 6 && excludeSaturdays) || (dayOfWeek === 0 && excludeSundays);

                    if (!isExcludedWeekend) {
                        holidays.push(holidayDate.format('YYYY-MM-DD'));
                    }
                }
            }
        });
    });

    // Remove duplicates
    return [...new Set(holidays)];
}

/**
 * Count weekend days in the date range
 */
function countWeekendDays(
    startDate: moment.Moment,
    endDate: moment.Moment,
    excludeSaturdays: boolean,
    excludeSundays: boolean
): number {
    let weekendDays = 0;
    const current = startDate.clone();

    while (current.isSameOrBefore(endDate, 'day')) {
        const dayOfWeek = current.day();

        if (excludeSaturdays && dayOfWeek === 6) {
            weekendDays++;
        }

        if (excludeSundays && dayOfWeek === 0) {
            weekendDays++;
        }

        current.add(1, 'day');
    }

    return weekendDays;
}

/**
 * Count custom holidays in the date range (excluding those on already-excluded weekends)
 */
function countCustomHolidays(
    startDate: moment.Moment,
    endDate: moment.Moment,
    customHolidays: string[],
    excludeSaturdays: boolean,
    excludeSundays: boolean
): number {
    let count = 0;

    customHolidays.forEach((holidayDate) => {
        const date = moment(holidayDate);

        if (date.isBetween(startDate, endDate, 'day', '[]')) {
            const dayOfWeek = date.day();

            // Only count if not already an excluded weekend
            const isExcludedWeekend = (dayOfWeek === 6 && excludeSaturdays) || (dayOfWeek === 0 && excludeSundays);

            if (!isExcludedWeekend) {
                count++;
            }
        }
    });

    return count;
}

/**
 * Calculate working days between two dates based on company policy
 */
export function calculateWorkingDays(
    startDate: string | Date | moment.Moment,
    endDate: string | Date | moment.Moment,
    policy: CompanyWorkingPolicy
): number {
    const start = moment(startDate).startOf('day');
    const end = moment(endDate).startOf('day');

    // Total days including start and end
    const totalDays = end.diff(start, 'days') + 1;

    // For 7-day work week, return total days
    if (policy.working_days_policy === '7_day') {
        return totalDays;
    }

    let excludedDays = 0;

    // Exclude weekends
    if (policy.exclude_saturdays || policy.exclude_sundays) {
        excludedDays += countWeekendDays(start, end, policy.exclude_saturdays, policy.exclude_sundays);
    }

    // Exclude public holidays
    if (policy.exclude_public_holidays) {
        const publicHolidays = getPublicHolidaysInRange(
            start,
            end,
            policy.exclude_saturdays,
            policy.exclude_sundays
        );
        excludedDays += publicHolidays.length;
    }

    // Exclude custom company holidays
    if (policy.custom_holidays && policy.custom_holidays.length > 0) {
        excludedDays += countCustomHolidays(
            start,
            end,
            policy.custom_holidays,
            policy.exclude_saturdays,
            policy.exclude_sundays
        );
    }

    return Math.max(0, totalDays - excludedDays);
}

/**
 * Get detailed breakdown of leave calculation
 */
export function getLeaveBreakdown(
    startDate: string | Date | moment.Moment,
    endDate: string | Date | moment.Moment,
    policy: CompanyWorkingPolicy
): LeaveBreakdown {
    const start = moment(startDate).startOf('day');
    const end = moment(endDate).startOf('day');

    const totalDays = end.diff(start, 'days') + 1;
    let weekendDays = 0;
    let publicHolidayDays = 0;
    let customHolidayDays = 0;
    const excludedDates: LeaveBreakdown['excluded_dates'] = [];

    if (policy.working_days_policy !== '7_day') {
        // Count weekends
        if (policy.exclude_saturdays || policy.exclude_sundays) {
            weekendDays = countWeekendDays(start, end, policy.exclude_saturdays, policy.exclude_sundays);

            // Get weekend dates
            const current = start.clone();
            while (current.isSameOrBefore(end, 'day')) {
                const dayOfWeek = current.day();
                if (
                    (policy.exclude_saturdays && dayOfWeek === 6) ||
                    (policy.exclude_sundays && dayOfWeek === 0)
                ) {
                    excludedDates.push({
                        date: current.format('YYYY-MM-DD'),
                        type: 'weekend',
                        name: current.format('dddd'),
                    });
                }
                current.add(1, 'day');
            }
        }

        // Count public holidays
        if (policy.exclude_public_holidays) {
            const publicHolidays = getPublicHolidaysInRange(
                start,
                end,
                policy.exclude_saturdays,
                policy.exclude_sundays
            );
            publicHolidayDays = publicHolidays.length;

            publicHolidays.forEach((holiday) => {
                excludedDates.push({
                    date: holiday,
                    type: 'public_holiday',
                    name: 'Public Holiday',
                });
            });
        }

        // Count custom holidays
        if (policy.custom_holidays && policy.custom_holidays.length > 0) {
            customHolidayDays = countCustomHolidays(
                start,
                end,
                policy.custom_holidays,
                policy.exclude_saturdays,
                policy.exclude_sundays
            );

            policy.custom_holidays.forEach((holiday) => {
                const date = moment(holiday);
                if (date.isBetween(start, end, 'day', '[]')) {
                    const dayOfWeek = date.day();
                    const isExcludedWeekend =
                        (dayOfWeek === 6 && policy.exclude_saturdays) || (dayOfWeek === 0 && policy.exclude_sundays);

                    if (!isExcludedWeekend) {
                        excludedDates.push({
                            date: holiday,
                            type: 'custom_holiday',
                            name: 'Company Holiday',
                        });
                    }
                }
            });
        }
    }

    const workingDays = totalDays - weekendDays - publicHolidayDays - customHolidayDays;

    return {
        total_days: totalDays,
        working_days: Math.max(0, workingDays),
        weekend_days: weekendDays,
        public_holidays: publicHolidayDays,
        custom_holidays: customHolidayDays,
        excluded_dates: excludedDates.sort((a, b) => a.date.localeCompare(b.date)),
    };
}

/**
 * Default company policy for 5-day work week
 */
export const DEFAULT_WORKING_POLICY: CompanyWorkingPolicy = {
    working_days_policy: '5_day',
    standard_working_days_per_month: 22,
    exclude_saturdays: true,
    exclude_sundays: true,
    exclude_public_holidays: true,
    custom_holidays: [],
};
