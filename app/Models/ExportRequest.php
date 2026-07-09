<?php

namespace App\Models;

use App\Enums\ExportFormat;
use App\Enums\ExportModule;
use App\Enums\ExportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string              $id
 * @property string              $user_id
 * @property ExportModule        $module
 * @property ExportFormat        $format
 * @property ExportStatus        $status
 * @property array<string,mixed> $filters
 * @property string|null         $file_path
 * @property int|null            $row_count
 * @property string|null         $failure_message
 * @property Carbon|null         $expires_at
 * @property Carbon|null         $created_at
 * @property Carbon|null         $updated_at
 */
class ExportRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'module',
        'format',
        'status',
        'filters',
        'file_path',
        'row_count',
        'failure_message',
        'expires_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'module'     => ExportModule::class,
            'format'     => ExportFormat::class,
            'status'     => ExportStatus::class,
            'filters'    => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
