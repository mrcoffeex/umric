<?php

use App\Mail\ResearchStatusUpdated;
use App\Models\ResearchPaper;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('queues one email per proponent using string ulid ids', function () {
    $lead = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $lead->id]);
    $co = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $co->id]);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $lead->id,
        'proponents' => [
            ['id' => (string) $lead->id, 'name' => $lead->name],
            ['id' => (string) $co->id, 'name' => $co->name],
        ],
    ]);

    ResearchStatusUpdated::dispatch(
        $paper->fresh(),
        'data_gathering',
        'Data Gathering',
        'completed',
        'All set.',
    );

    Mail::assertQueued(ResearchStatusUpdated::class, 2);
});

it('includes paper owner when proponents json is empty', function () {
    $owner = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $owner->id]);

    $paper = ResearchPaper::factory()->create([
        'user_id' => $owner->id,
        'proponents' => null,
    ]);

    ResearchStatusUpdated::dispatch(
        $paper->fresh(),
        'ric_review',
        'RIC/Admin Review',
        'approved',
        null,
    );

    Mail::assertQueued(ResearchStatusUpdated::class, 1);
});
