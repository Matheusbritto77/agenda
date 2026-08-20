<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = static::resolveTenantId();

            if ($tenantId === null) {
                return;
            }

            $table = $builder->getModel()->getTable();
            $builder->where($table . '.user_id', $tenantId);
        });

        static::creating(function (Model $model): void {
            if (! empty($model->user_id)) {
                return;
            }

            $tenantId = static::resolveTenantId();

            if ($tenantId !== null) {
                $model->user_id = $tenantId;
            }
        });
    }

    protected static function resolveTenantId(): ?int
    {
        $bookingTenant = app()->bound('bookingTenant') ? app('bookingTenant') : null;

        if ($bookingTenant instanceof User) {
            return $bookingTenant->parent_id ? (int) $bookingTenant->parent_id : (int) $bookingTenant->id;
        }

        $user = Auth::user();

        if ($user instanceof User) {
            return $user->parent_id ? (int) $user->parent_id : (int) $user->id;
        }

        return null;
    }
}
