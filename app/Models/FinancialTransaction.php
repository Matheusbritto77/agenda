<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'team_member_id',
        'type',
        'category',
        'title',
        'description',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date:Y-m-d',
        'paid_at' => 'date:Y-m-d',
    ];

    protected $appends = [
        'is_overdue',
        'computed_status',
    ];

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'paid' || $this->status === 'cancelled') {
            return false;
        }

        if (! $this->due_date) {
            return false;
        }

        return Carbon::parse($this->due_date)->isPast() && ! Carbon::parse($this->due_date)->isToday();
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'paid') {
            return 'paid';
        }
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }
        if ($this->is_overdue) {
            return 'overdue';
        }

        return 'pending';
    }

    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeIncomes(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForPeriod(Builder $query, ?string $start, ?string $end): Builder
    {
        if ($start && $end) {
            return $query->whereBetween('due_date', [$start, $end]);
        } elseif ($start) {
            return $query->where('due_date', '>=', $start);
        } elseif ($end) {
            return $query->where('due_date', '<=', $end);
        }

        return $query;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'team_member_id');
    }
}
