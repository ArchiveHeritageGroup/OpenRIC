<?php

declare(strict_types=1);

namespace OpenRiC\Workflow\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use OpenRiC\Workflow\Contracts\WorkflowServiceInterface;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowServiceInterface $service,
    ) {}

    /**
     * Workflow dashboard with stats, recent tasks, and pool summary.
     */
    public function dashboard(): View
    {
        $userId = auth()->id();
        $stats = $this->service->getStats($userId);
        $myTasks = $this->service->getMyTasks($userId);
        $poolTasks = $this->service->getPoolTasks($userId);
        $recentHistory = $this->service->getHistory(20);

        return view('openric-workflow::dashboard', [
            'stats' => $stats,
            'myTasks' => array_slice($myTasks, 0, 10),
            'poolTasks' => array_slice($poolTasks, 0, 10),
            'recentHistory' => $recentHistory,
        ]);
    }

    /**
     * Full list of tasks assigned to the current user, with optional status filter.
     */
    public function myTasks(Request $request): View
    {
        $userId = auth()->id();
        $status = $request->get('status');
        $tasks = $this->service->getMyTasks($userId, $status);

        return view('openric-workflow::my-tasks', [
            'tasks' => $tasks,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Pool tasks available for claiming.
     */
    public function poolTasks(): View
    {
        $userId = auth()->id();
        $tasks = $this->service->getPoolTasks($userId);

        return view('openric-workflow::pool', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * Show a single task with full detail and history timeline.
     */
    public function showTask(int $id): View
    {
        $task = $this->service->getTask($id);

        if ($task === null) {
            abort(404, 'Task not found.');
        }

        return view('openric-workflow::task-show', [
            'task' => $task,
        ]);
    }

    /**
     * Claim a pool task for the current user.
     */
    public function claimTask(int $id): RedirectResponse
    {
        $userId = auth()->id();
        $result = $this->service->claimTask($id, $userId);

        if ($result) {
            return redirect()->route('workflow.task', $id)
                ->with('success', 'Task claimed successfully.');
        }

        return redirect()->route('workflow.pool')
            ->with('error', 'Unable to claim task. It may have already been claimed.');
    }

    /**
     * Release a claimed task back to the pool.
     */
    public function releaseTask(Request $request, int $id): RedirectResponse
    {
        $userId = auth()->id();
        $comment = $request->input('comment');
        $result = $this->service->releaseTask($id, $userId, $comment);

        if ($result) {
            return redirect()->route('workflow.pool')
                ->with('success', 'Task released back to pool.');
        }

        return redirect()->route('workflow.task', $id)
            ->with('error', 'Unable to release task.');
    }

    /**
     * Approve a task and advance the workflow.
     */
    public function approveTask(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'comment' => 'nullable|string|max:5000',
        ]);

        $userId = auth()->id();
        $result = $this->service->approveTask($id, $userId, $request->input('comment'));

        if ($result) {
            return redirect()->route('workflow.my-tasks')
                ->with('success', 'Task approved successfully.');
        }

        return redirect()->route('workflow.task', $id)
            ->with('error', 'Unable to approve task.');
    }

    /**
     * Reject a task with a mandatory comment.
     */
    public function rejectTask(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        $userId = auth()->id();
        $result = $this->service->rejectTask($id, $userId, $request->input('comment'));

        if ($result) {
            return redirect()->route('workflow.my-tasks')
                ->with('success', 'Task rejected.');
        }

        return redirect()->route('workflow.task', $id)
            ->with('error', 'Unable to reject task.');
    }

    /**
     * Admin: list all workflows.
     */
    public function workflows(): View
    {
        $workflows = $this->service->getWorkflows();

        return view('openric-workflow::workflows', [
            'workflows' => $workflows,
        ]);
    }

    /**
     * Admin: show create workflow form.
     */
    public function createWorkflow(): View
    {
        return view('openric-workflow::create-workflow');
    }

    /**
     * Admin: persist a new workflow.
     */
    public function storeWorkflow(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope_type' => 'required|string|max:50',
            'trigger_event' => 'required|string|max:50',
            'applies_to' => 'required|string|max:50',
        ]);

        $data = $request->only([
            'name', 'description', 'scope_type', 'scope_id', 'trigger_event',
            'applies_to', 'auto_archive_days',
        ]);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['is_default'] = $request->has('is_default') ? 1 : 0;
        $data['require_all_steps'] = $request->has('require_all_steps') ? 1 : 0;
        $data['allow_parallel'] = $request->has('allow_parallel') ? 1 : 0;
        $data['notification_enabled'] = $request->has('notification_enabled') ? 1 : 0;
        $data['created_by'] = auth()->id();

        $id = $this->service->createWorkflow($data);

        return redirect()->route('workflow.admin.edit', $id)
            ->with('success', 'Workflow created. Now add steps.');
    }

    /**
     * Admin: show edit workflow form with steps.
     */
    public function editWorkflow(int $id): View
    {
        $workflow = $this->service->getWorkflow($id);

        if ($workflow === null) {
            abort(404, 'Workflow not found.');
        }

        return view('openric-workflow::edit-workflow', [
            'workflow' => $workflow,
        ]);
    }

    /**
     * Admin: persist workflow updates.
     */
    public function updateWorkflow(Request $request, int $id): RedirectResponse
    {
        $workflow = $this->service->getWorkflow($id);

        if ($workflow === null) {
            abort(404, 'Workflow not found.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope_type' => 'required|string|max:50',
            'trigger_event' => 'required|string|max:50',
            'applies_to' => 'required|string|max:50',
        ]);

        $data = $request->only([
            'name', 'description', 'scope_type', 'scope_id', 'trigger_event',
            'applies_to', 'auto_archive_days',
        ]);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['is_default'] = $request->has('is_default') ? 1 : 0;
        $data['require_all_steps'] = $request->has('require_all_steps') ? 1 : 0;
        $data['allow_parallel'] = $request->has('allow_parallel') ? 1 : 0;
        $data['notification_enabled'] = $request->has('notification_enabled') ? 1 : 0;

        $this->service->updateWorkflow($id, $data);

        return redirect()->route('workflow.admin.edit', $id)
            ->with('success', 'Workflow updated.');
    }

    /**
     * Admin: add step to a workflow.
     */
    public function addStep(Request $request, int $workflowId): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'step_type' => 'required|string|max:50',
            'action_required' => 'required|string|max:50',
        ]);

        $data = $request->only([
            'name', 'description', 'step_order', 'step_type', 'action_required',
            'required_role_id', 'required_clearance_level', 'allowed_group_ids',
            'allowed_user_ids', 'auto_assign_user_id', 'escalation_days',
            'escalation_user_id', 'notification_template', 'instructions',
            'checklist',
        ]);
        $data['pool_enabled'] = $request->has('pool_enabled') ? 1 : 0;
        $data['is_optional'] = $request->has('is_optional') ? 1 : 0;
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $this->service->addStep($workflowId, $data);

        return redirect()->route('workflow.admin.edit', $workflowId)
            ->with('success', 'Step added.');
    }

    /**
     * Admin: delete a step.
     */
    public function deleteStep(int $id): RedirectResponse
    {
        $step = DB::table('workflow_steps')->where('id', $id)->first();

        if ($step === null) {
            abort(404, 'Step not found.');
        }

        $this->service->deleteStep($id);

        return redirect()->route('workflow.admin.edit', $step->workflow_id)
            ->with('success', 'Step deleted.');
    }

    /**
     * Admin: delete a workflow entirely.
     */
    public function deleteWorkflow(int $id): RedirectResponse
    {
        $this->service->deleteWorkflow($id);

        return redirect()->route('workflow.admin')
            ->with('success', 'Workflow deleted.');
    }

    /**
     * Overdue tasks list with optional user/queue filters.
     */
    public function overdue(Request $request): View
    {
        $userId = $request->get('user_id');
        $queueId = $request->get('queue_id');
        $tasks = $this->service->getOverdueTasks(
            $userId ? (int) $userId : null,
            $queueId ? (int) $queueId : null,
        );

        return view('openric-workflow::overdue', [
            'tasks' => $tasks,
            'filterUserId' => $userId,
            'filterQueueId' => $queueId,
        ]);
    }

    /**
     * Evaluate publish readiness for an entity identified by IRI.
     */
    public function publishReadiness(Request $request): View
    {
        $iri = $request->get('iri', '');

        if ($iri === '') {
            return view('openric-workflow::publish-readiness', [
                'evaluation' => null,
                'objectIri' => '',
            ]);
        }

        $evaluation = $this->service->evaluateGates($iri);

        return view('openric-workflow::publish-readiness', [
            'evaluation' => $evaluation,
            'objectIri' => $iri,
        ]);
    }
}
