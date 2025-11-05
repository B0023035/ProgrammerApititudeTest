<?php

// app/Models/ExamViolation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'user_id',
        'violation_type',
        'violation_details',
        'detected_at',
    ];

    protected $casts = [
        'violation_details' => 'array',
        'detected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 違反タイプの定数
    public const VIOLATION_TAB_SWITCH = 'tab_switch';

    public const VIOLATION_WINDOW_BLUR = 'window_blur';

    public const VIOLATION_FULLSCREEN_EXIT = 'fullscreen_exit';

    public const VIOLATION_COPY_PASTE = 'copy_paste';

    public const VIOLATION_RIGHT_CLICK = 'right_click';

    /**
     * 違反が発生した試験セッション
     */
    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    /**
     * 違反したユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 違反タイプの一覧を取得
     */
    public static function getViolationTypes(): array
    {
        return [
            self::VIOLATION_TAB_SWITCH => 'タブ切り替え',
            self::VIOLATION_WINDOW_BLUR => 'ウィンドウフォーカス外れ',
            self::VIOLATION_FULLSCREEN_EXIT => 'フルスクリーン解除',
            self::VIOLATION_COPY_PASTE => 'コピー&ペースト',
            self::VIOLATION_RIGHT_CLICK => '右クリック',
        ];
    }
}
