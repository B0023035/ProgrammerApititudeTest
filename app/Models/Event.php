namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'passphrase',
        'begin',
        'end',
        'exam_type',
    ];

    protected $casts = [
        'begin' => 'datetime',
        'end' => 'datetime',
    ];

    public function isActive(): bool
    {
        $now = now();
        return $now->between($this->begin, $this->end);
    }

    public function verifyPassphrase(string $input): bool
    {
        return $this->isActive() && $this->passphrase === $input;
    }
}
