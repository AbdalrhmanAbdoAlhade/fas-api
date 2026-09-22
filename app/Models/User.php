<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
  const ROLE_TENANT         = 'tenant';
    public const ROLE_COORDINATOR = 'coordinator';
    public const ROLE_HOTEL_OWNER = 'hotel_owner';
    public const ROLE_COMPANY_OWNER = 'company_owner';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'full_name',
        'last_name',
        'birth_date',
        'phone',
         'registration_role',
        'image',
        'gender',
        'national_id',
        'national_img',
        'nationality',
        'status',
        'tax_certificate',
        'ownership_deed', // صك الملكية أو شهادة الملكية (path to file)
        'commercial_register', // السجل التجاري (path to file)
        'property_type', // نوع العقار
        'city', // المدينة
        'address', // عنوان العقار التفصيلي
        'area', // المساحة (متر مربع)
        'rooms', // عدد الغرف
        'locale',
      'bank_name',
    'iban',
    'bank_account',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
       
    // 🔹 دوال التحقق من الأدوار
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isHotelOwner()
    {
        return $this->role === self::ROLE_HOTEL_OWNER;
    }

    public function isCompanyOwner()
    {
        return $this->role === self::ROLE_COMPANY_OWNER;
    }

    public function isUser()
    {
        return $this->role === self::ROLE_USER;
    }
  
      public function isTenant()
      {
          return $this->role === self::ROLE_TENANT;
      }
    // 🔹 العلاقات

        public function company()
    {
         return $this->hasOne(Company::class);
    }


        public function hotels()
        {
            return $this->hasMany(Hotel::class); // صاحب الفندق يمكنه امتلاك عدة فنادق
        }
        
         public function coordinator()
    {
        // العلاقة صحيحة، وتعتمد على أن 'id' في جدول 'coordinators' هو المفتاح الأجنبي.
        return $this->hasOne(Coordinator::class);
    }
    
    // العلاقة مع الصلاحيات
public function permissions(): BelongsToMany
{
    return $this->belongsToMany(Permission::class, 'user_permissions')
        ->withPivot('granted_by', 'granted_at');
}

/**
 * هل الموظف عنده صلاحية معينة؟
 */
public function hasPermission(string $permission): bool
{
    if ($this->role === self::ROLE_ADMIN) {
        return true;
    }

    return $this->permissions()->where('name', $permission)->exists();
}

/**
 * هل عنده أي صلاحية من القائمة؟
 */
public function hasAnyPermission(array $permissions): bool
{
    if ($this->role === self::ROLE_ADMIN) {
        return true;
    }

    return $this->permissions()->whereIn('name', $permissions)->exists();
}
/**
 * تحويل الموديل إلى مصفوفة مع إضافة الصلاحيات للموظف فقط.
 */
public function toArray(): array
{
    $data = parent::toArray();

    // لو المستخدم موظف، نرجع معاه الصلاحيات
    if ($this->role === self::ROLE_EMPLOYEE) {
        $this->loadMissing('permissions');

        // لو عايز ترجع أسماء الصلاحيات فقط
        $data['permissions'] = $this->permissions->pluck('name')->values();

        // ولو عايز ترجع بيانات الصلاحية كاملة (id, name, ...)
        // $data['permissions'] = $this->permissions;
    }

    return $data;
}
/**
 * هل هو موظف؟
 */
public function isEmployee(): bool
{
    return $this->role === self::ROLE_EMPLOYEE;
}
}
