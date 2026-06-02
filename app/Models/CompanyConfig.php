<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyConfig extends Model
{
    protected $table = 'company_configs';

    protected $fillable = [
        'company_id',
        'key',
        'type',
        'value',
        'descripton',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function getValue(string $key, mixed $default = null, ?string $companyId = null): mixed
    {
        $query = static::where('key', $key);
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $config = $query->first();
        return $config?->value ?? $default;
    }

    public static function getLogo(?string $companyId = null): ?string
    {
        // Prefer the new companies.logo field (light variant) if set.
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $company->logo) {
                return $company->logo;
            }
        }
        // Fallback to legacy company_configs table for backward compatibility
        return static::getValue('logo', null, $companyId);
    }
}
