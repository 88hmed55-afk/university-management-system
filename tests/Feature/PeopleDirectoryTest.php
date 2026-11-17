<?php

namespace Tests\Feature;

use App\Actions\RevealPersonIdentifier;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\PersonIdentifier;
use App\Models\PersonIdentifierType;
use App\Models\Role;
use App\Models\User;
use App\RecordStatus;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class PeopleDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_people_directory_schema_is_created(): void
    {
        foreach (['people', 'person_identifier_types', 'person_identifiers'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('people', [
            'given_name', 'family_name', 'status', 'archived_at',
        ]));
        $this->assertTrue(Schema::hasColumns('person_identifiers', [
            'person_id', 'identifier_type_id', 'value', 'value_hash',
            'issuing_authority', 'effective_from', 'effective_to', 'status', 'archived_at',
        ]));
        $this->assertTrue(Schema::hasColumn('users', 'person_id'));
    }

    public function test_person_has_an_optional_single_login_link(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id]);
        $serviceAccount = User::factory()->create();

        $this->assertTrue($user->person->is($person));
        $this->assertTrue($person->user->is($user));
        $this->assertSame('اسم-', mb_substr($person->fullName, 0, 4));
        $this->assertNull($serviceAccount->person_id);
    }

    public function test_identifier_types_are_not_seeded(): void
    {
        $this->assertSame(0, PersonIdentifierType::count());
    }

    public function test_identifier_value_is_encrypted_at_rest_and_hidden_from_arrays(): void
    {
        $identifier = PersonIdentifier::factory()->create(['value' => '1234567890']);

        $rawValue = DB::table('person_identifiers')->where('id', $identifier->id)->value('value');
        $rawHash = DB::table('person_identifiers')->where('id', $identifier->id)->value('value_hash');

        $this->assertNotSame('1234567890', $rawValue);
        $this->assertSame('1234567890', $identifier->fresh()->value);
        $this->assertSame(hash_hmac('sha256', '1234567890', (string) config('app.key')), $rawHash);
        $this->assertArrayNotHasKey('value', $identifier->toArray());
        $this->assertArrayNotHasKey('value_hash', $identifier->toArray());
    }

    public function test_same_official_identifier_cannot_be_reused_for_another_person(): void
    {
        $type = PersonIdentifierType::factory()->create();
        PersonIdentifier::factory()->create([
            'identifier_type_id' => $type->id,
            'value' => 'SHARED-VALUE',
        ]);

        $this->expectException(QueryException::class);

        PersonIdentifier::factory()->create([
            'identifier_type_id' => $type->id,
            'value' => 'SHARED-VALUE',
        ]);
    }

    public function test_identifier_validity_window_excludes_expired_and_archived_rows(): void
    {
        $person = Person::factory()->create();
        $current = PersonIdentifier::factory()->create([
            'person_id' => $person->id,
            'effective_from' => '2026-01-01',
        ]);
        PersonIdentifier::factory()->create([
            'person_id' => $person->id,
            'effective_from' => '2020-01-01',
            'effective_to' => '2020-12-31',
        ]);
        PersonIdentifier::factory()->create([
            'person_id' => $person->id,
            'status' => RecordStatus::Archived,
            'archived_at' => now(),
        ]);

        $ids = PersonIdentifier::query()->currentlyValid()->pluck('id')->all();

        $this->assertSame([$current->id], $ids);
    }

    public function test_people_identifiers_and_types_cannot_be_deleted(): void
    {
        $rows = [
            Person::factory()->create(),
            PersonIdentifier::factory()->create(),
            PersonIdentifierType::factory()->create(),
        ];

        foreach ($rows as $row) {
            try {
                $row->delete();
                $this->fail('حذف سجل الهوية يجب أن يُرفض.');
            } catch (LogicException) {
                // متوقع
            }
        }

        foreach ($rows as $row) {
            $this->assertNotNull($row->fresh());
        }
    }

    public function test_access_is_fail_closed_even_for_privileged_role_titles(): void
    {
        $identifier = PersonIdentifier::factory()->create();

        foreach ([RoleCode::SystemAdmin, RoleCode::IdentityAdmin, RoleCode::HrDirector] as $roleCode) {
            $user = $this->userWithRole($roleCode);

            $this->assertTrue(Gate::forUser($user)->denies('view', $identifier));
            $this->assertTrue(Gate::forUser($user)->denies('viewAny', PersonIdentifier::class));
        }
    }

    public function test_reveal_is_denied_and_not_audited_without_explicit_approval(): void
    {
        $identifier = PersonIdentifier::factory()->create(['value' => 'SECRET-VALUE']);
        $user = $this->userWithRole(RoleCode::IdentityAdmin);

        try {
            (new RevealPersonIdentifier)($identifier, $user, 'تحقق');
            $this->fail('الاطلاع يجب أن يُرفض دون اعتماد صريح.');
        } catch (AuthorizationException) {
            // متوقع
        }

        $this->assertSame(0, AuditLog::count());
    }

    public function test_approved_viewer_can_reveal_and_the_access_is_audited_without_the_value(): void
    {
        config()->set('privacy.identifier_viewer_user_ids', []);

        $identifier = PersonIdentifier::factory()->create(['value' => 'SECRET-VALUE']);
        $user = $this->userWithRole(RoleCode::HrDirector);
        config()->set('privacy.identifier_viewer_user_ids', [$user->id]);

        $revealed = (new RevealPersonIdentifier)($identifier, $user, 'تحقق من الهوية');

        $this->assertSame('SECRET-VALUE', $revealed);
        $this->assertTrue(Gate::forUser($user)->allows('view', $identifier));
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'person_identifier.revealed',
            'outcome' => 'success',
            'subject_type' => PersonIdentifier::class,
        ]);

        $log = AuditLog::firstOrFail();
        $this->assertStringNotContainsString('SECRET-VALUE', (string) json_encode($log->after));
        $this->assertNull($log->before);
    }

    public function test_reveal_requires_a_reason(): void
    {
        $identifier = PersonIdentifier::factory()->create();
        $user = $this->userWithRole(RoleCode::HrDirector);
        config()->set('privacy.identifier_viewer_user_ids', [$user->id]);

        $this->expectException(InvalidArgumentException::class);

        (new RevealPersonIdentifier)($identifier, $user, '   ');
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
