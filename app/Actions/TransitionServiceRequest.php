<?php

namespace App\Actions;

use App\DataScopeCode;
use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkflowStep;
use App\RoleCode;
use App\Support\RequestAudit;
use App\Support\ServicesAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class TransitionServiceRequest
{
    /** Each transition is a documented decision; reject/return require a reason. */
    public function __invoke(
        ServiceRequest $request,
        User $actor,
        string $transition,
        string $decisionReference,
        ?string $note = null,
        ?string $correlationId = null,
    ): ServiceRequest {
        if (! ServicesAccess::canManageRequests($actor)) {
            throw new AuthorizationException('معالجة الطلبات مقصور على خدمات الطلاب المخولين.');
        }

        if (! in_array($transition, ['advance', 'reject', 'return'], true)) {
            throw new InvalidArgumentException('نوع الانتقال غير معروف.');
        }

        if (trim($decisionReference) === '') {
            throw new InvalidArgumentException('مرجع قرار الانتقال مطلوب.');
        }

        if (! in_array($request->status, ['submitted', 'in_review'], true)) {
            throw new InvalidArgumentException('الطلب ليس في حالة تسمح بالمعالجة.');
        }

        if (in_array($transition, ['reject', 'return'], true) && trim((string) $note) === '') {
            throw new InvalidArgumentException('سبب الرفض أو الإرجاع مطلوب.');
        }

        $step = null;
        $nextStep = null;
        $newStatus = null;

        if ($transition === 'advance') {
            if ($request->workflow_version_no === null) {
                throw new InvalidArgumentException('لا يوجد مسار منشور مرتبط بهذا الطلب.');
            }

            $currentStepNo = (int) ($request->current_step_no ?? 1);
            $steps = WorkflowStep::query()
                ->where('service_request_type_id', $request->service_request_type_id)
                ->where('version_no', $request->workflow_version_no)
                ->where('status', 'published')
                ->orderBy('step_no')
                ->get();

            $step = $steps->firstWhere('step_no', $currentStepNo);

            if ($step === null) {
                throw new InvalidArgumentException('خطوة المسار الحالية غير صالحة.');
            }

            $role = RoleCode::tryFrom($step->role_code);

            if ($role === null || ! $actor->hasActiveRole($role)) {
                throw new AuthorizationException('دورك لا يطابق الدور المخول لخطوة المسار الحالية.');
            }

            $scope = DataScopeCode::tryFrom($step->scope_code);

            if ($scope === DataScopeCode::Institution && ! $actor->hasInstitutionWideAccess()) {
                throw new AuthorizationException('هذه الخطوة تتطلب نطاقاً مؤسسياً.');
            }

            if ($scope === DataScopeCode::Campus && $actor->visibleCampusIds() === []) {
                throw new AuthorizationException('لا يوجد حرم مرئي لك لتنفيذ هذه الخطوة.');
            }

            $nextStep = $steps->firstWhere('step_no', $currentStepNo + 1);
            $newStatus = $nextStep === null ? 'completed' : 'in_review';
        } else {
            $newStatus = $transition === 'reject' ? 'rejected' : 'returned';
        }

        $correlationId ??= Str::uuid()->toString();

        DB::transaction(function () use (
            $request, $actor, $transition, $decisionReference, $note,
            $correlationId, $nextStep, $newStatus,
        ): void {
            $request->status = $newStatus;
            $request->assigned_to = $actor->getKey();
            $request->decision_reference = $decisionReference;

            if ($newStatus === 'completed' || $newStatus === 'rejected') {
                $request->closed_at = now();
            }

            if ($transition === 'advance' && $nextStep !== null) {
                $request->current_step_no = $nextStep->step_no;
            }

            $request->save();

            $eventType = $transition === 'advance'
                ? ($newStatus === 'completed' ? 'completed' : 'advanced')
                : $transition;

            RequestAudit::event($request, $actor, $eventType, $note, $correlationId);

            AuditLog::create([
                'actor_id' => $actor->getKey(),
                'action' => 'services.request_transitioned',
                'subject_type' => ServiceRequest::class,
                'subject_id' => (string) $request->getKey(),
                'outcome' => 'success',
                'correlation_id' => $correlationId,
                'reason' => $note,
                'after' => [
                    'request_no' => $request->request_no,
                    'transition' => $transition,
                    'status' => $newStatus,
                    'decision_reference' => $decisionReference,
                ],
            ]);
        });

        return $request->refresh();
    }
}
