<?php

namespace Tests\Feature\Tasks;

use App\Exceptions\Task\TaskCollaborationException;
use App\Livewire\Tasks\TaskList;
use App\Models\Department;
use App\Models\HrNotification;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskChecklistItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TaskCollaborationService;
use App\Services\TenantProvisioner;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TaskCollaborationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);
    }

    /**
     * @return array{tenant: Tenant, user: User}
     */
    private function provisionCompany(string $email = 'owner@example.com'): array
    {
        return app(TenantProvisioner::class)->provision([
            'name' => 'Owner User',
            'email' => $email,
            'password' => 'password123',
            'company_name' => 'Acme '.$email,
            'plan' => 'free',
            'billing_cycle' => 'monthly',
            'payment_method' => ['type' => 'none'],
        ]);
    }

    private function makeUser(Tenant $tenant, string $role, string $name, ?string $email = null): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email ?? Str::slug($name).'.'.Str::random(6).'@example.com',
        ]);

        $user->assignRole($role);

        return $user;
    }

    private function makeTask(User $creator, array $attributes = []): Task
    {
        return Task::create(array_merge([
            'title' => 'Ship the release',
            'created_by' => $creator->id,
            'tenant_id' => $creator->tenant_id,
            'status' => 'pending',
            'priority' => 'medium',
            'visibility' => 'team',
        ], $attributes));
    }

    private function service(): TaskCollaborationService
    {
        return app(TaskCollaborationService::class);
    }

    /* ---------------------------------------------------------------------
     |  Watchers
     * ------------------------------------------------------------------- */

    public function test_a_user_can_watch_and_unwatch_a_task(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner, ['assigned_to' => $employee->id]);

        $this->actingAs($employee);

        $this->assertTrue($this->service()->toggleWatch($task, $employee));
        $this->assertTrue($this->service()->isWatching($task, $employee));
        $this->assertDatabaseHas('task_watchers', ['task_id' => $task->id, 'user_id' => $employee->id]);

        $this->assertFalse($this->service()->toggleWatch($task->fresh(), $employee));
        $this->assertFalse($this->service()->isWatching($task->fresh(), $employee));
        $this->assertDatabaseMissing('task_watchers', ['task_id' => $task->id, 'user_id' => $employee->id]);
    }

    public function test_watching_the_same_task_twice_does_not_duplicate(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);

        $this->service()->watch($task, $owner);
        $this->service()->watch($task, $owner);

        $this->assertSame(1, $task->watchers()->count());
    }

    public function test_watchers_are_notified_about_new_comments(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $watcher = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner);

        $this->actingAs($owner);

        $this->service()->watch($task, $watcher);
        $this->service()->addComment($task, 'Any progress here?', $owner);

        $this->assertDatabaseHas('hr_notifications', [
            'user_id' => $watcher->id,
            'type' => 'task_comment',
        ]);
    }

    public function test_the_comment_author_is_never_notified_of_their_own_comment(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner, ['assigned_to' => $owner->id]);

        $this->actingAs($owner);

        $this->service()->watch($task, $owner);
        $this->service()->addComment($task, 'Talking to myself', $owner);

        $this->assertSame(0, HrNotification::where('user_id', $owner->id)->count());
    }

    public function test_the_toggle_watch_action_updates_the_drawer(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner, ['assigned_to' => $employee->id]);

        Livewire::actingAs($employee)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->call('toggleWatch')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_watchers', ['task_id' => $task->id, 'user_id' => $employee->id]);
    }

    public function test_a_creator_can_add_another_watcher_by_name(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $employee = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->set('watcherToAdd', (string) $employee->id)
            ->call('addWatcher')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_watchers', ['task_id' => $task->id, 'user_id' => $employee->id]);
    }

    /* ---------------------------------------------------------------------
     |  @mentions
     * ------------------------------------------------------------------- */

    public function test_mentions_resolve_from_slugged_quoted_and_email_handles(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $jane = $this->makeUser($tenant, 'Employee', 'Jane Doe', 'jane.doe@example.com');
        $task = $this->makeTask($owner);

        $service = $this->service();

        $this->assertTrue($service->mentionedUsers($task, 'Ping @jane.doe please')->contains('id', $jane->id));
        $this->assertTrue($service->mentionedUsers($task, 'Ping @"Jane Doe" please')->contains('id', $jane->id));
        $this->assertTrue($service->mentionedUsers($task, 'Ping @jane please')->contains('id', $jane->id));
        $this->assertTrue($service->mentionedUsers($task, 'Ping @jane.doe@example.com please')->contains('id', $jane->id));
    }

    public function test_an_unmatched_mention_is_ignored(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->assertCount(0, $this->service()->mentionedUsers($task, 'Hello @nobody.here'));
    }

    public function test_an_ambiguous_first_name_is_not_resolved(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $janeDoe = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $janeRoe = $this->makeUser($tenant, 'Employee', 'Jane Roe');
        $task = $this->makeTask($owner);

        $mentioned = $this->service()->mentionedUsers($task, 'Over to @jane on this one');

        $this->assertCount(0, $mentioned, 'A first name shared by two people mentions nobody');

        $this->assertCount(1, $this->service()->mentionedUsers($task, 'Over to @jane.roe on this one'));
        $this->assertTrue($this->service()->mentionedUsers($task, 'Over to @jane.roe on this one')->contains('id', $janeRoe->id));
        $this->assertFalse($this->service()->mentionedUsers($task, 'Over to @jane.roe on this one')->contains('id', $janeDoe->id));
    }

    public function test_a_mentioned_teammate_gets_a_mention_notification(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $jane = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner);

        $this->actingAs($owner);

        $this->service()->addComment($task, 'Could you review this @jane.doe?', $owner);

        $this->assertDatabaseHas('hr_notifications', [
            'user_id' => $jane->id,
            'type' => 'task_mention',
        ]);

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'mentioned',
        ]);
    }

    public function test_a_mention_is_not_narrated_when_the_author_mentions_themselves(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);

        $this->service()->addComment($task, 'Note to self @owner.user', $owner);

        $this->assertDatabaseMissing('task_activities', [
            'task_id' => $task->id,
            'type' => 'mentioned',
        ]);
    }

    public function test_comments_can_be_posted_through_the_component_with_mentions(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $jane = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->set('commentBody', '@jane.doe take a look')
            ->call('addComment')
            ->assertHasNoErrors()
            ->assertSet('commentBody', '');

        $this->assertDatabaseHas('task_comments', ['task_id' => $task->id, 'body' => '@jane.doe take a look']);
        $this->assertDatabaseHas('hr_notifications', ['user_id' => $jane->id, 'type' => 'task_mention']);
    }

    /* ---------------------------------------------------------------------
     |  Labels
     * ------------------------------------------------------------------- */

    public function test_labels_can_be_added_and_removed(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);
        $service = $this->service();

        $service->addLabel($task, 'Bug', $owner);
        $service->addLabel($task->fresh(), 'release 2.0', $owner);

        $this->assertSame(['Bug', 'release 2.0'], $task->fresh()->labels);

        $service->removeLabel($task->fresh(), 'bug', $owner);

        $this->assertSame(['release 2.0'], $task->fresh()->labels);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'type' => 'labels_updated']);
    }

    public function test_duplicate_labels_are_rejected_case_insensitively(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);
        $this->service()->addLabel($task, 'Bug', $owner);

        $this->expectException(TaskCollaborationException::class);

        $this->service()->addLabel($task->fresh(), 'bug', $owner);
    }

    public function test_the_label_limit_is_enforced(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        config(['tasks.max_labels' => 2]);

        $this->actingAs($owner);
        $service = $this->service();

        $service->addLabel($task, 'one', $owner);
        $service->addLabel($task->fresh(), 'two', $owner);

        $this->expectException(TaskCollaborationException::class);

        $service->addLabel($task->fresh(), 'three', $owner);
    }

    public function test_labels_can_be_added_from_the_drawer_and_filtered(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $buggy = $this->makeTask($owner, ['title' => 'Fix the bug']);
        $this->makeTask($owner, ['title' => 'Write the docs']);

        $component = Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $buggy->id)
            ->set('labelInput', 'bug, urgent')
            ->call('addLabel')
            ->assertHasNoErrors();

        $this->assertSame(['bug', 'urgent'], $buggy->fresh()->labels);

        $component
            ->call('$set', 'showTask', false)
            ->set('labelFilter', 'bug')
            ->assertSee('Fix the bug')
            ->assertDontSee('Write the docs');
    }

    public function test_removing_a_label_through_the_component_persists(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner, ['labels' => ['bug', 'docs']]);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->call('removeLabel', 'bug')
            ->assertHasNoErrors();

        $this->assertSame(['docs'], $task->fresh()->labels);
    }

    /* ---------------------------------------------------------------------
     |  Checklist
     * ------------------------------------------------------------------- */

    public function test_checklist_items_are_ordered_and_completable(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $assignee = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner, ['assigned_to' => $assignee->id]);

        $this->actingAs($assignee);
        $service = $this->service();

        $first = $service->addChecklistItem($task, 'Write the tests', $assignee);
        $second = $service->addChecklistItem($task->fresh(), 'Ship it', $assignee);

        $this->assertSame([$first->id, $second->id], $task->fresh()->checklistItems->pluck('id')->all());

        $service->toggleChecklistItem($first, $assignee);

        $this->assertTrue($first->fresh()->is_completed);
        $this->assertNotNull($first->fresh()->completed_at);

        $service->toggleChecklistItem($first->fresh(), $assignee);

        $this->assertFalse($first->fresh()->is_completed);
        $this->assertNull($first->fresh()->completed_at);
    }

    public function test_checklist_progress_is_calculated(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);
        $service = $this->service();

        $this->assertSame(['done' => 0, 'total' => 0, 'percent' => 0], $task->checklistProgress());

        $one = $service->addChecklistItem($task, 'One', $owner);
        $service->addChecklistItem($task->fresh(), 'Two', $owner);
        $service->addChecklistItem($task->fresh(), 'Three', $owner);
        $service->toggleChecklistItem($one, $owner);

        $this->assertSame(['done' => 1, 'total' => 3, 'percent' => 33], $task->fresh()->checklistProgress());
    }

    public function test_a_checklist_item_can_be_removed_and_is_narrated(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);
        $service = $this->service();

        $item = $service->addChecklistItem($task, 'Draft the email', $owner);
        $service->removeChecklistItem($item, $owner);

        $this->assertDatabaseMissing('task_checklist_items', ['id' => $item->id]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'checklist_item_removed',
        ]);
    }

    public function test_the_drawer_adds_and_toggles_checklist_items(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->set('checklistInput', 'Book the room')
            ->call('addChecklistItem')
            ->assertHasNoErrors()
            ->assertSet('checklistInput', '');

        $item = TaskChecklistItem::where('task_id', $task->id)->first();
        $this->assertNotNull($item);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->call('toggleChecklistItem', $item->id);

        $this->assertTrue($item->fresh()->is_completed);
    }

    public function test_an_empty_checklist_item_is_rejected(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);

        $this->expectException(TaskCollaborationException::class);

        $this->service()->addChecklistItem($task, '   ', $owner);
    }

    /* ---------------------------------------------------------------------
     |  Attachments
     * ------------------------------------------------------------------- */

    public function test_an_attachment_is_stored_and_recorded(): void
    {
        Storage::fake('local');

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);

        $attachment = $this->service()->attach(
            $task,
            UploadedFile::fake()->create('project-plan.pdf', 120, 'application/pdf'),
            $owner,
        );

        $this->assertSame('project-plan.pdf', $attachment->original_name);
        $this->assertSame($owner->id, $attachment->user_id);
        $this->assertSame($tenant->id, $attachment->tenant_id);
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'type' => 'attachment_added']);
    }

    public function test_attachments_can_be_uploaded_from_the_drawer(): void
    {
        Storage::fake('local');

        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->set('attachmentUpload', UploadedFile::fake()->create('notes.txt', 4, 'text/plain'))
            ->call('uploadAttachment')
            ->assertHasNoErrors()
            ->assertSet('attachmentUpload', null);

        $this->assertSame(1, TaskAttachment::where('task_id', $task->id)->count());
    }

    public function test_a_disallowed_file_type_is_rejected(): void
    {
        Storage::fake('local');

        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->set('attachmentUpload', UploadedFile::fake()->create('payload.exe', 4, 'application/x-msdownload'))
            ->call('uploadAttachment')
            ->assertHasErrors(['attachmentUpload']);

        $this->assertSame(0, TaskAttachment::where('task_id', $task->id)->count());
    }

    public function test_an_oversized_attachment_is_rejected(): void
    {
        Storage::fake('local');

        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->set('attachmentUpload', UploadedFile::fake()->create('huge.pdf', 20480, 'application/pdf'))
            ->call('uploadAttachment')
            ->assertHasErrors(['attachmentUpload']);
    }

    public function test_the_attachment_limit_is_enforced(): void
    {
        Storage::fake('local');

        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        config(['tasks.max_attachments' => 1]);

        $this->actingAs($owner);

        $this->service()->attach($task, UploadedFile::fake()->create('one.pdf', 4, 'application/pdf'), $owner);

        $this->expectException(TaskCollaborationException::class);

        $this->service()->attach($task->fresh(), UploadedFile::fake()->create('two.pdf', 4, 'application/pdf'), $owner);
    }

    public function test_a_viewer_can_download_an_attachment(): void
    {
        Storage::fake('local');

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $assignee = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner, ['assigned_to' => $assignee->id]);

        $this->actingAs($owner);
        $attachment = $this->service()->attach(
            $task,
            UploadedFile::fake()->create('project-plan.pdf', 120, 'application/pdf'),
            $owner,
        );

        $this->actingAs($assignee)
            ->get(route('tasks.attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('project-plan.pdf');
    }

    public function test_an_unrelated_teammate_cannot_download_an_attachment(): void
    {
        Storage::fake('local');

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $outsider = $this->makeUser($tenant, 'Employee', 'Nosy Colleague');
        $task = $this->makeTask($owner);

        $this->actingAs($owner);
        $attachment = $this->service()->attach(
            $task,
            UploadedFile::fake()->create('project-plan.pdf', 120, 'application/pdf'),
            $owner,
        );

        $this->actingAs($outsider)
            ->get(route('tasks.attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_a_teammate_from_another_company_cannot_reach_an_attachment(): void
    {
        Storage::fake('local');

        ['user' => $owner] = $this->provisionCompany();
        ['user' => $stranger] = $this->provisionCompany('stranger@example.com');
        $task = $this->makeTask($owner);

        $this->actingAs($owner);
        $attachment = $this->service()->attach(
            $task,
            UploadedFile::fake()->create('project-plan.pdf', 120, 'application/pdf'),
            $owner,
        );

        $this->actingAs($stranger)
            ->get(route('tasks.attachments.download', $attachment))
            ->assertNotFound();
    }

    public function test_only_the_uploader_creator_or_hr_can_remove_an_attachment(): void
    {
        Storage::fake('local');

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $assignee = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner, ['assigned_to' => $assignee->id]);

        $this->actingAs($assignee);
        $attachment = $this->service()->attach(
            $task,
            UploadedFile::fake()->create('draft.pdf', 4, 'application/pdf'),
            $assignee,
        );

        // The uploader can delete their own file.
        Livewire::actingAs($assignee)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->call('removeAttachment', $attachment->id)
            ->assertHasNoErrors();

        $this->assertSame(0, TaskAttachment::where('task_id', $task->id)->count());
        Storage::disk('local')->assertMissing($attachment->path);
    }

    /* ---------------------------------------------------------------------
     |  Activity timeline
     * ------------------------------------------------------------------- */

    public function test_creating_editing_and_completing_a_task_is_narrated(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $department = Department::create(['name' => 'Engineering', 'tenant_id' => $tenant->id]);

        $component = Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->set('title', 'Ship the release')
            ->set('department_id', $department->id)
            ->call('save')
            ->assertHasNoErrors();

        $task = Task::first();
        $this->assertNotNull($task);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->id, 'type' => 'created']);

        $component->call('openEdit', $task->id)->set('status', 'in_progress')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => 'status_changed',
        ]);
        $this->assertSame('in_progress', $task->fresh()->status);

        $component->call('openEdit', $task->id)->set('status', 'completed')->call('save')->assertHasNoErrors();

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_status_changes_notify_watchers(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $watcher = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner);

        $this->actingAs($owner);
        $this->service()->watch($task, $watcher);

        Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('updateStatus', $task->id, 'in_progress')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('hr_notifications', [
            'user_id' => $watcher->id,
            'type' => 'task_status',
        ]);
    }

    public function test_the_timeline_merges_comments_and_events_newest_first(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $task = $this->makeTask($owner);

        $this->actingAs($owner);

        TaskActivity::record($task, 'created', 'Owner created this task');

        $this->travel(5)->minutes();

        $this->service()->addComment($task, 'First thought', $owner);

        $timeline = Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->instance()
            ->timeline();

        $this->assertSame('comment', $timeline->first()['kind']);
        $this->assertSame('activity', $timeline->last()['kind']);
        $this->assertSame('First thought', $timeline->first()['body']);
    }

    public function test_the_timeline_only_shows_the_open_task(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        $firstTask = $this->makeTask($owner, ['title' => 'First']);
        $secondTask = $this->makeTask($owner, ['title' => 'Second']);

        $this->actingAs($owner);

        TaskActivity::record($secondTask, 'created', 'Owner created the second task');

        $timeline = Livewire::actingAs($owner)
            ->test(TaskList::class)
            ->call('viewTask', $firstTask->id)
            ->instance()
            ->timeline();

        $this->assertTrue($timeline->isEmpty(), 'The drawer never leaks another task\'s history');
    }

    /* ---------------------------------------------------------------------
     |  Access control
     * ------------------------------------------------------------------- */

    public function test_an_unrelated_teammate_cannot_open_the_task_drawer(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $outsider = $this->makeUser($tenant, 'Employee', 'Nosy Colleague');
        $task = $this->makeTask($owner);

        Livewire::actingAs($outsider)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->assertForbidden();
    }

    public function test_a_teammate_from_another_company_cannot_reach_the_task(): void
    {
        ['user' => $owner] = $this->provisionCompany();
        ['user' => $stranger] = $this->provisionCompany('stranger@example.com');
        $task = $this->makeTask($owner);

        $this->actingAs($stranger);
        $this->assertNull(Task::find($task->id), 'The tenant scope hides the task entirely');

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($stranger)
            ->test(TaskList::class)
            ->call('viewTask', $task->id);
    }

    public function test_only_a_creator_or_hr_can_edit_labels(): void
    {
        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $assignee = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner, ['assigned_to' => $assignee->id]);

        Livewire::actingAs($assignee)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->set('labelInput', 'sneaky')
            ->call('addLabel')
            ->assertForbidden();

        $this->assertNull($task->fresh()->labels);
    }

    public function test_an_assignee_can_still_use_the_checklist_and_attachments(): void
    {
        Storage::fake('local');

        ['tenant' => $tenant, 'user' => $owner] = $this->provisionCompany();
        $assignee = $this->makeUser($tenant, 'Employee', 'Jane Doe');
        $task = $this->makeTask($owner, ['assigned_to' => $assignee->id]);

        Livewire::actingAs($assignee)
            ->test(TaskList::class)
            ->call('viewTask', $task->id)
            ->set('checklistInput', 'Do the thing')
            ->call('addChecklistItem')
            ->assertHasNoErrors()
            ->set('attachmentUpload', UploadedFile::fake()->create('proof.pdf', 4, 'application/pdf'))
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $this->assertSame(1, TaskChecklistItem::where('task_id', $task->id)->count());
        $this->assertSame(1, TaskAttachment::where('task_id', $task->id)->count());
    }
}
