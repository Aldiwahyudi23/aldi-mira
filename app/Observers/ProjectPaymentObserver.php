<?php

namespace App\Observers;

use App\Models\Project\ProjectPayment;
use App\Models\Project\ProjectItem;

class ProjectPaymentObserver
{
    /**
     * Handle the ProjectPayment "created" event.
     */
    public function created(ProjectPayment $payment): void
    {
        $this->updateProjectItemStatus($payment->project_item_id);
    }

    /**
     * Handle the ProjectPayment "updated" event.
     */
    public function updated(ProjectPayment $payment): void
    {
        if ($payment->isDirty('amount')) {
            $this->updateProjectItemStatus($payment->project_item_id);
        }
    }

    /**
     * Handle the ProjectPayment "deleted" event.
     */
    public function deleted(ProjectPayment $payment): void
    {
        $this->updateProjectItemStatus($payment->project_item_id);
    }

    /**
     * Update project item status berdasarkan total savings
     */
    private function updateProjectItemStatus($projectItemId): void
    {
        $projectItem = ProjectItem::find($projectItemId);
        
        if ($projectItem) {
            $totalSavings = $projectItem->payments()
                ->where('amount', '>', 0) // Hanya amount positif (tabungan)
                ->sum('amount');

            $status = 'pending';
            if ($totalSavings > 0) {
                $status = 'in_progress';
            }
            if ($totalSavings >= $projectItem->planned_amount) {
                $status = 'ready';
            }

            $projectItem->update([
                'actual_spent' => $totalSavings,
                'status' => $status
            ]);
        }
    }
}