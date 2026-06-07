<?php

namespace App\Models;

use App\Models\Traits\HasBlameable;
use App\Models\Traits\HasSoftDelete;
use App\Models\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyConfig extends Model
{
    use HasUuidV7, HasBlameable, HasSoftDelete;

    protected $table = 'company_configs';

    protected $fillable = [
        'id',
        'company_id',
        'key',
        'type',
        'value',
        'description',
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

    /**
     * @deprecated Logo is now a direct column on the `companies` table.
     *             Use $company->logo and Company::getLogoDataUriAttribute() instead.
     *             Kept here only for legacy fallback compatibility.
     */
    public static function getLogo(?string $companyId = null): ?string
    {
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $company->logo) {
                return $company->logo;
            }
        }
        // Legacy fallback
        return static::getValue('logo', null, $companyId);
    }
}
