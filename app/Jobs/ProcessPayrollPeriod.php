<?php

namespace App\Jobs;

use App\Models\AccountingPeriod;
use App\Models\User;
use App\Services\PayrollProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPayrollPeriod implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * The accounting period to process.
     *
     * @var AccountingPeriod
     */
    protected AccountingPeriod $period;

    /**
     * The center ID to process for.
     *
     * @var string
     */
    protected string $centerId;

    /**
     * The currency to use for processing.
     *
     * @var string
     */
    protected string $currency;

    /**
     * The action to perform (run, refresh).
     *
     * @var string
     */
    protected string $action;

    /**
     * The user who initiated the job.
     *
     * @var string|null
     */
    protected ?string $userId;

    /**
     * Create a new job instance.
     *
     * @param AccountingPeriod $period
     * @param string $centerId
     * @param string $currency
     * @param string $action
     * @param string|null $userId
     */
    public function __construct(
        AccountingPeriod $period,
        string $centerId,
        string $currency,
        string $action,
        ?string $userId = null
    ) {
        $this->period = $period;
        $this->centerId = $centerId;
        $this->currency = $currency;
        $this->action = $action;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @param PayrollProcessor $processor
     * @return void
     */
    public function handle(PayrollProcessor $processor): void
    {
        $startTime = now();

        Log::info("Starting payroll period processing", [
            'period_id' => $this->period->period_id,
            'center_id' => $this->centerId,
            'currency' => $this->currency,
            'action' => $this->action,
            'user_id' => $this->userId,
        ]);

        try {
            $result = match ($this->action) {
                'run' => $processor->runPeriod($this->period, $this->centerId, $this->currency),
                'refresh' => $processor->refreshPeriod($this->period, $this->centerId, $this->currency),
                default => throw new \InvalidArgumentException("Invalid action: {$this->action}"),
            };

            $duration = now()->diffInSeconds($startTime);

            if ($result) {
                Log::info("Successfully completed payroll period processing", [
                    'period_id' => $this->period->period_id,
                    'center_id' => $this->centerId,
                    'action' => $this->action,
                    'duration_seconds' => $duration,
                ]);

                // TODO: Send success notification to user
                // if ($this->userId) {
                //     $user = User::find($this->userId);
                //     $user->notify(new PayrollPeriodProcessed($this->period, $this->action, 'success'));
                // }
            }

        } catch (\Exception $e) {
            $duration = now()->diffInSeconds($startTime);

            Log::error("Failed to process payroll period", [
                'period_id' => $this->period->period_id,
                'center_id' => $this->centerId,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'duration_seconds' => $duration,
            ]);

            // TODO: Send failure notification to user
            // if ($this->userId) {
            //     $user = User::find($this->userId);
            //     $user->notify(new PayrollPeriodProcessed($this->period, $this->action, 'failed', $e->getMessage()));
            // }

            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("Payroll period job permanently failed", [
            'period_id' => $this->period->period_id,
            'center_id' => $this->centerId,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send critical failure notification
        // if ($this->userId) {
        //     $user = User::find($this->userId);
        //     $user->notify(new PayrollPeriodFailed($this->period, $this->action, $exception->getMessage()));
        // }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'payroll',
            'period:' . $this->period->period_id,
            'center:' . $this->centerId,
            'action:' . $this->action,
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }
}
