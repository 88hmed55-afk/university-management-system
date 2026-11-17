<?php

namespace App\Models;

use App\RecordStatus;
use Database\Factories\SectionMeetingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;

class SectionMeeting extends Model
{
    /** @use HasFactory<SectionMeetingFactory> */
    use HasFactory;

    protected $fillable = [
        'course_section_id', 'day_of_week', 'start_time', 'end_time', 'room_id',
        'valid_from', 'valid_to', 'status', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'status' => RecordStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SectionMeeting $meeting): void {
            if ($meeting->day_of_week < 1 || $meeting->day_of_week > 7) {
                throw new InvalidArgumentException('يوم الاجتماع يجب أن يكون بين 1 و7.');
            }

            if (strcmp((string) $meeting->end_time, (string) $meeting->start_time) <= 0) {
                throw new InvalidArgumentException('وقت نهاية الاجتماع يجب أن يكون بعد بدايته.');
            }

            if ($meeting->valid_to !== null
                && $meeting->valid_to->lessThanOrEqualTo($meeting->valid_from)) {
                throw new InvalidArgumentException('تاريخ نهاية الاجتماع يجب أن يكون بعد بدايته.');
            }

            $conflict = static::query()
                ->when($meeting->exists, fn (Builder $query) => $query->whereKeyNot($meeting->getKey()))
                ->where(function (Builder $query) use ($meeting): void {
                    $query->where('course_section_id', $meeting->course_section_id);
                    if ($meeting->room_id !== null) {
                        $query->orWhere('room_id', $meeting->room_id);
                    }
                })
                ->where('day_of_week', $meeting->day_of_week)
                ->where('start_time', '<', (string) $meeting->end_time)
                ->where('end_time', '>', (string) $meeting->start_time)
                ->where('valid_from', '<=', $meeting->valid_to?->toDateString() ?? '9999-12-31')
                ->where(function (Builder $query) use ($meeting): void {
                    $query->whereNull('valid_to')
                        ->orWhere('valid_to', '>=', $meeting->valid_from->toDateString());
                })
                ->exists();

            if ($conflict) {
                throw new InvalidArgumentException('تعارض في وقت الاجتماع أو حجز القاعة.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('اجتماعات الشعب لا تُحذف؛ تُغلق بالأرشفة.');
        });
    }

    /** @return BelongsTo<CourseSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
