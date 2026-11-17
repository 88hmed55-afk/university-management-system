<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Campus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\RecordStatus;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class DocumentApprovalBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_approval_schema_is_created(): void
    {
        $this->assertTrue(Schema::hasTable('documents'));
        $this->assertTrue(Schema::hasTable('document_versions'));
        $this->assertTrue(Schema::hasTable('approvals'));

        $this->assertTrue(Schema::hasColumns('documents', [
            'owner_type', 'owner_id', 'document_type', 'storage_key', 'original_name',
            'mime_type', 'size_bytes', 'checksum', 'uploaded_by', 'status', 'archived_at',
        ]));
        $this->assertTrue(Schema::hasColumns('document_versions', [
            'document_id', 'version_no', 'storage_key', 'checksum', 'created_by', 'decision_reference',
        ]));
        $this->assertTrue(Schema::hasColumns('approvals', [
            'subject_type', 'subject_id', 'decision', 'approver_id', 'decided_at', 'reference',
        ]));
    }

    public function test_database_holds_metadata_only_and_storage_is_private(): void
    {
        $this->assertFalse(Schema::hasColumn('documents', 'content'));
        $this->assertFalse(Schema::hasColumn('documents', 'file'));

        $document = Document::factory()->create();

        $this->assertStringStartsWith('private/', $document->storage_key);
        $this->assertSame(64, strlen($document->checksum));
    }

    public function test_checksum_is_mandatory(): void
    {
        $this->expectException(QueryException::class);

        Document::factory()->create(['checksum' => null]);
    }

    public function test_public_storage_key_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Document::factory()->create(['storage_key' => 'public/documents/file.pdf']);
    }

    public function test_owner_morph_and_owner_scope_work(): void
    {
        $campus = Campus::factory()->create();
        $document = Document::factory()->create([
            'owner_type' => Campus::class,
            'owner_id' => $campus->id,
        ]);

        $this->assertTrue($document->fresh()->owner->is($campus));
        $this->assertSame(
            [$document->id],
            Document::query()->forOwner(Campus::class, $campus->id)->pluck('id')->all()
        );
    }

    public function test_versions_are_numbered_and_the_latest_is_current(): void
    {
        $document = Document::factory()->create();

        DocumentVersion::factory()->create(['document_id' => $document->id, 'version_no' => 1]);
        DocumentVersion::factory()->create(['document_id' => $document->id, 'version_no' => 2]);

        $this->assertSame(2, $document->currentVersion()->version_no);
        $this->assertSame(2, $document->versions()->count());
    }

    public function test_duplicate_version_number_is_rejected(): void
    {
        $document = Document::factory()->create();
        DocumentVersion::factory()->create(['document_id' => $document->id, 'version_no' => 1]);

        $this->expectException(QueryException::class);

        DocumentVersion::factory()->create(['document_id' => $document->id, 'version_no' => 1]);
    }

    public function test_approval_records_approver_time_reference_and_bilingual_decision(): void
    {
        $approval = Approval::factory()->create(['decision' => 'rejected']);

        $this->assertNotNull($approval->approver);
        $this->assertNotNull($approval->decided_at);
        $this->assertNotEmpty($approval->reference);
        $this->assertDatabaseHas('approvals', [
            'id' => $approval->id,
            'decision' => 'rejected',
        ]);

        App::setLocale('ar');
        $this->assertSame('مرفوض', $approval->decisionLabel());

        App::setLocale('en');
        $this->assertSame('Rejected', $approval->decisionLabel());
    }

    public function test_archiving_a_document_keeps_metadata_and_removes_it_from_active_scope(): void
    {
        $document = Document::factory()->create();

        $document->status = RecordStatus::Archived;
        $document->archived_at = now();
        $document->save();

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        $this->assertSame(0, Document::query()->active()->count());
    }

    public function test_versions_and_approvals_cannot_be_edited(): void
    {
        $version = DocumentVersion::factory()->create();
        $approval = Approval::factory()->create(['decision' => 'rejected']);

        try {
            $version->update(['checksum' => str_repeat('b', 64)]);
            $this->fail('تعديل النسخة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->expectException(LogicException::class);

        $approval->update(['decision' => 'approved']);
    }

    public function test_documents_versions_and_approvals_cannot_be_deleted(): void
    {
        $document = Document::factory()->create();
        $version = DocumentVersion::factory()->create();
        $approval = Approval::factory()->create();

        try {
            $document->delete();
            $this->fail('حذف الوثيقة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        try {
            $version->delete();
            $this->fail('حذف النسخة يجب أن يُرفض.');
        } catch (LogicException) {
            // متوقع
        }

        $this->expectException(LogicException::class);

        $approval->delete();
    }
}
