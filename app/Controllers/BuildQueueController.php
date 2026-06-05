<?php

namespace App\Controllers;

use App\ActivityLog;
use App\Permission;
use App\ReadFoundation\SprintMergeQa;

class BuildQueueController extends Controller
{
    public function index()
    {
        $this->authorize('build_queue.view');
        ActivityLog::record('build_queue_access', 'Build Queue and Semi-Automation planning foundation page viewed');

        $this->render('build-queue.index', [
            'pageTitle' => 'Build Queue',
            'breadcrumbs' => [
                ['label' => 'System', 'active' => false],
                ['label' => 'Build Queue', 'active' => true],
            ],
            'accessMode' => Permission::accessMode(),
            'workflow' => $this->workflow(),
            'safetyGates' => $this->safetyGates(),
            'automationLevels' => $this->automationLevels(),
            'blockedActions' => $this->blockedActions(),
            'planningSections' => $this->planningSections(),
            'buildQueueFields' => $this->buildQueueFields(),
            'buildRunFields' => $this->buildRunFields(),
            'redIssueFields' => $this->redIssueFields(),
            'postActivationBuilds' => SprintMergeQa::postActivationNextBuilds(),
            'sprintMergeNote' => 'v0.4.2.2 adds /dev-db-activation table verification. Manual dev DB apply and write-form testing first. v0.4.3 Return Receive Submit only after dev DB write testing passes.',
        ]);
    }

    private function workflow()
    {
        return [
            'Read the next build task from the build queue.',
            'Apply one build or one small safe batch only.',
            'Run tools/check-local.ps1 before considering the build complete.',
            'If [OK] ALL GREEN, show version, changed files, route count, Red Issues: none, and recommended next build.',
            'If [FAIL] RED ISSUES SUMMARY appears, stop immediately and do not continue to another task.',
            'Wait for owner approval before any commit or push.',
            'Start the next build only after Git is synced with origin/main.',
        ];
    }

    private function safetyGates()
    {
        return [
            'Checkpoint-first rule: every build or foundation change must run the local checkpoint.',
            'Git sync rule: the next build starts only after Git is synced with origin/main.',
            'Owner approval rule: commit and push remain manual owner-approved actions.',
            'Red Issues Summary stop rule: failure blocks the next task until fixed and rechecked.',
            'Small batch rule: Level 3 batches must stay limited to 2-3 related planning pages.',
            'No blind run rule: the agent must not auto-run 10-15 tasks without review.',
        ];
    }

    private function automationLevels()
    {
        return [
            [
                'title' => 'Level 1',
                'summary' => 'Manual task prompt plus manual checkpoint plus manual commit/push.',
            ],
            [
                'title' => 'Level 2',
                'summary' => 'Build queue suggests the next task, checkpoint footer is shown, commit/push stay manual.',
            ],
            [
                'title' => 'Level 3',
                'summary' => 'Small safe batch of 2-3 related planning pages, checkpoint, then manual owner review.',
            ],
        ];
    }

    private function blockedActions()
    {
        return [
            'Automatic commit',
            'Automatic push',
            'Automatic database migration apply',
            'Automatic OpenCart/WooCommerce sync',
            'Automatic order import',
            'Automatic payable mutation',
            'Automatic stock deduction',
            'Automatic invoice generation',
        ];
    }

    private function planningSections()
    {
        return [
            [
                'title' => 'Build Queue Purpose',
                'points' => [
                    'Speed up development by keeping the next safe build task visible.',
                    'Make the agent stop after one task or one approved small batch.',
                    'Keep checkpoint output and owner approval as the control point.',
                ],
            ],
            [
                'title' => 'Future Build Queue File Plan',
                'points' => [
                    'A future queue file can list version, title, module area, dependencies, expected routes, and expected permissions.',
                    'The queue file should be reviewed by the owner before the agent starts the next build.',
                    'This release does not create queue files, queue records, or build-run records automatically.',
                ],
            ],
            [
                'title' => 'Future Cursor Agent Instruction Plan',
                'points' => [
                    'Future instructions should tell the agent to pick one queued task, implement it, run the checkpoint, then stop.',
                    'Failure instructions should require immediate stop with the Red Issues Summary and no next-task continuation.',
                    'Commit and push instructions must stay manual and owner-approved.',
                ],
            ],
        ];
    }

    private function buildQueueFields()
    {
        return [
            'build_queue_id',
            'build_version',
            'build_title',
            'build_type',
            'module_area',
            'priority',
            'status',
            'depends_on_version',
            'expected_routes',
            'expected_permissions',
            'checkpoint_required',
            'browser_check_required',
            'owner_approval_required',
            'created_by',
            'created_at',
            'completed_at',
        ];
    }

    private function buildRunFields()
    {
        return [
            'build_run_id',
            'build_queue_id',
            'started_by',
            'started_at',
            'finished_at',
            'checkpoint_status',
            'route_smoke_status',
            'red_issues_count',
            'git_status',
            'result_summary',
            'next_recommended_build',
        ];
    }

    private function redIssueFields()
    {
        return [
            'red_issue_id',
            'build_run_id',
            'severity',
            'area',
            'file_path',
            'route',
            'issue_title',
            'issue_detail',
            'suggested_fix',
            'status',
            'created_at',
        ];
    }
}
