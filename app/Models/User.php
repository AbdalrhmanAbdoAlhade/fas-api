<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;


    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
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
}
