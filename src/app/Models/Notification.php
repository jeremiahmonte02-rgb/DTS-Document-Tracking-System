<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Represents an internal alert delivered to a single user, fanning out
 * department-level events (uploads, receipts) to every member of the
 * responsible department.
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'document_id',
        'type',
        'title',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * The user this notification was delivered to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The department whose activity triggered this notification.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The document this notification references, when applicable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Fan out one notification row per active user of the responsible
     * department so each member can mark their own copy as read.
     *
     * @param int $departmentId The department whose users should be notified.
     * @param string $type Internal discriminator (e.g. 'uploaded', 'received').
     * @param string $title Short alert headline.
     * @param string $message Human-readable detail.
     * @param string|null $documentId Document UUID this alert relates to.
     */
    public static function broadcastToDepartment(
        int $departmentId,
        string $type,
        string $title,
        string $message,
        ?string $documentId = null
    ): void {
        $userIds = DB::table('users')
            ->where('department_id', $departmentId)
            ->where('status', 'active')
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $userIds->map(fn ($userId) => [
            'user_id'       => $userId,
            'department_id' => $departmentId,
            'document_id'   => $documentId,
            'type'          => $type,
            'title'         => $title,
            'message'       => $message,
            'read_at'       => null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ])->all();

        DB::table('notifications')->insert($rows);
    }
}