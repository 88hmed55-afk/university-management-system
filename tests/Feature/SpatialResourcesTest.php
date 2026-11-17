<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Campus;
use App\Models\Floor;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomFeature;
use App\Models\User;
use App\RecordStatus;
use App\RoleCode;
use Database\Seeders\IdentityReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SpatialResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentityReferenceSeeder::class);
    }

    public function test_spatial_schema_is_created(): void
    {
        foreach ([
            'buildings', 'floors', 'rooms', 'room_capacity_periods',
            'room_features', 'room_feature_assignments',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('buildings', [
            'campus_id', 'code', 'name', 'status', 'archived_at',
        ]));
        $this->assertTrue(Schema::hasColumns('room_capacity_periods', [
            'room_id', 'capacity', 'effective_from', 'effective_to', 'decision_reference',
        ]));
        $this->assertTrue(Schema::hasColumns('room_feature_assignments', [
            'room_id', 'room_feature_id', 'effective_from', 'effective_to', 'decision_reference',
        ]));
    }

    public function test_campus_building_floor_room_chain_can_be_extended_without_code_changes(): void
    {
        $campus = Campus::factory()->create();
        $building = Building::factory()->create(['campus_id' => $campus->id]);
        $floor = Floor::factory()->create(['building_id' => $building->id]);
        $room = Room::factory()->create(['floor_id' => $floor->id]);

        $this->assertTrue($room->floor->is($floor));
        $this->assertTrue($room->floor->building->is($building));
        $this->assertTrue($room->floor->building->campus->is($campus));
        $this->assertSame(1, Room::query()->count());
    }

    public function test_codes_are_unique_within_their_parent_only(): void
    {
        $institution = Institution::factory()->create();
        $firstCampus = Campus::factory()->create(['institution_id' => $institution->id]);
        $secondCampus = Campus::factory()->create(['institution_id' => $institution->id]);

        Building::factory()->create(['campus_id' => $firstCampus->id, 'code' => 'MAIN']);
        Building::factory()->create(['campus_id' => $secondCampus->id, 'code' => 'MAIN']);

        $this->assertSame(2, Building::query()->where('code', 'MAIN')->count());

        $this->expectException(QueryException::class);

        Building::factory()->create(['campus_id' => $firstCampus->id, 'code' => 'MAIN']);
    }

    public function test_archiving_a_room_keeps_history_and_removes_it_from_active_scope(): void
    {
        $room = Room::factory()->create();

        $room->status = RecordStatus::Archived;
        $room->archived_at = now();
        $room->save();

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'code' => $room->code]);
        $this->assertNotNull($room->fresh()->archived_at);
        $this->assertSame(0, Room::query()->active()->count());
    }

    public function test_capacity_is_effective_dated_without_overwriting_the_old_value(): void
    {
        $room = Room::factory()->create();

        $room->capacityPeriods()->create([
            'capacity' => 30,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-05-31',
        ]);
        $room->capacityPeriods()->create([
            'capacity' => 40,
            'effective_from' => '2026-06-01',
        ]);

        $this->assertSame(40, $room->currentCapacity());
        $this->assertSame(2, $room->capacityPeriods()->count());
        $this->assertTrue(
            $room->capacityPeriods()
                ->where('capacity', 30)
                ->whereDate('effective_to', '2026-05-31')
                ->exists()
        );
    }

    public function test_features_are_not_seeded_and_are_linked_only_after_approval(): void
    {
        $this->assertSame(0, RoomFeature::count());

        $feature = RoomFeature::factory()->create(['code' => 'PROJECTOR']);
        $room = Room::factory()->create();
        $room->features()->attach($feature, ['effective_from' => '2026-01-01']);

        $this->assertTrue($room->features()->whereKey($feature->getKey())->exists());
    }

    public function test_campus_manager_sees_only_resources_in_the_assigned_campus(): void
    {
        $institution = Institution::factory()->create();
        $ownedCampus = Campus::factory()->create(['institution_id' => $institution->id]);
        $otherCampus = Campus::factory()->create(['institution_id' => $institution->id]);
        $ownedBuilding = Building::factory()->create(['campus_id' => $ownedCampus->id]);
        $otherBuilding = Building::factory()->create(['campus_id' => $otherCampus->id]);
        $ownedFloor = Floor::factory()->create(['building_id' => $ownedBuilding->id]);
        $otherFloor = Floor::factory()->create(['building_id' => $otherBuilding->id]);
        $ownedRoom = Room::factory()->create(['floor_id' => $ownedFloor->id]);
        $otherRoom = Room::factory()->create(['floor_id' => $otherFloor->id]);

        $manager = $this->userWithRole(RoleCode::CampusManager);
        $manager->campuses()->attach($ownedCampus);

        $this->assertSame([$ownedBuilding->id], Building::query()->visibleTo($manager)->pluck('id')->all());
        $this->assertSame([$ownedFloor->id], Floor::query()->visibleTo($manager)->pluck('id')->all());
        $this->assertSame([$ownedRoom->id], Room::query()->visibleTo($manager)->pluck('id')->all());
        $this->assertTrue(Room::query()->whereKey($otherRoom->getKey())->visibleTo($manager)->doesntExist());
    }

    public function test_institution_wide_role_sees_all_resources(): void
    {
        $buildings = Building::factory()->count(2)->create();

        foreach ($buildings as $building) {
            $floor = Floor::factory()->create(['building_id' => $building->id]);
            Room::factory()->create(['floor_id' => $floor->id]);
        }

        $president = $this->userWithRole(RoleCode::UniversityPresident);

        $this->assertSame(2, Building::query()->visibleTo($president)->count());
        $this->assertSame(2, Room::query()->visibleTo($president)->count());
    }

    public function test_user_without_an_active_role_sees_no_resources(): void
    {
        Building::factory()->create();
        Room::factory()->create();
        $user = User::factory()->create();

        $this->assertSame(0, Building::query()->visibleTo($user)->count());
        $this->assertSame(0, Room::query()->visibleTo($user)->count());
    }

    private function userWithRole(RoleCode $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code->value)->firstOrFail();
        $user->roles()->attach($role);

        return $user->refresh();
    }
}
