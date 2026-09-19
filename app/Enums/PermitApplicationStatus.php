<?php

namespace App\Enums;

enum PermitApplicationStatus: string
{
    case Draft = 'draft';
    case HseReview = 'hse_review';
    case HseRevisionRequired = 'hse_revision_required';
    case WaitingExamSetup = 'waiting_exam_setup';
    case ExamScheduled = 'exam_scheduled';
    case ExamInProgress = 'exam_in_progress';
    case ExamRetryRequired = 'exam_retry_required';
    case ExamFailedFinal = 'exam_failed_final';
    case ExamPassed = 'exam_passed';
    case KttReview = 'ktt_review';
    case KttRevisionRequired = 'ktt_revision_required';
    case Approved = 'approved';
    case Issued = 'issued';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Cancelled = 'cancelled';

    public function isFinal(): bool
    {
        return in_array($this, [
            self::ExamFailedFinal,
            self::Expired,
            self::Revoked,
            self::Cancelled,
        ], true);
    }
}
