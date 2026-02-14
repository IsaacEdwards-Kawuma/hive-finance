<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = session('current_company_id') ?? request()->header('X-Company-Id');
        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}
