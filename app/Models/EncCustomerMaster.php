<?php

namespace App\Models;

use App\Traits\EncryptsCustomerData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EncCustomerMaster extends Model
{
    use HasFactory;

    protected $table = 'srno_customer_master';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = true;

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name',
        'entrycode',
        'email',
        'phone',
        'whatsappno',
        'isverified',
        'role_id',
        'address1',
        'address2',
        'state',
        'district',
        'taluka',
        'city',
        'noofbranch',
        'concernperson',
        'packagecode',
        'packagename',
        'subpackagecode',
        'caversion',
        'implementation',
        'active',
        'deactive',
        'messageID',
        'customerlanguage',
        'PhoneForIndex',
        'WhatsAppNoForIndex',
        'WebCustomerCode',
        'WebCustomerCodeStatus',
        'issetWebCustomerCode',
        'DuplicateMarked',
        'LongCustomerId',
        'UpdateToCallCenter',
    ];


    protected $hidden = [
        'password',
        'otp',
        'compserialotp',
        'otp_expires_time',
        'otpdatetime',
    ];

    protected $casts = [
        'active'             => 'integer',
        'deactive'           => 'integer',
        'DuplicateMarked'    => 'integer',
        'UpdateToCallCenter' => 'integer',
        'noofbranch'         => 'integer',
        'packagecode'        => 'integer',
        'subpackagecode'     => 'integer',
        'role_id'            => 'integer',
        'WebCustomerCode'    => 'integer',
        'LongCustomerId'     => 'integer',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];


    public function package()
    {
        return $this->belongsTo(
            Package::class,
            'packagecode',
            'id'
        );
    }

    /**
     * Accessor: Decrypt name when accessed
     */
    public function getDecryptedNameAttribute(): ?string
    {
        return $this->decryptField($this->attributes['name'] ?? null);
    }

    /**
     * Accessor: Decrypt phone when accessed
     */
    public function getDecryptedPhoneAttribute(): ?string
    {
        return $this->decryptField($this->attributes['phone'] ?? null);
    }

    /**
     * Accessor: Decrypt email when accessed
     */
    public function getDecryptedEmailAttribute(): ?string
    {
        return $this->decryptField($this->attributes['email'] ?? null);
    }

    /**
     * Accessor: Decrypt city when accessed
     */
    public function getDecryptedCityAttribute(): ?string
    {
        return $this->decryptField($this->attributes['city'] ?? null);
    }

    /**
     * Accessor: Decrypt address1 when accessed
     */
    public function getDecryptedAddress1Attribute(): ?string
    {
        return $this->decryptField($this->attributes['address1'] ?? null);
    }

    /**
     * Accessor: Decrypt address2 when accessed
     */
    public function getDecryptedAddress2Attribute(): ?string
    {
        return $this->decryptField($this->attributes['address2'] ?? null);
    }

    /**
     * Accessor: Decrypt concernperson when accessed
     */
    public function getDecryptedConcernpersonAttribute(): ?string
    {
        return $this->decryptField($this->attributes['concernperson'] ?? null);
    }

    /**
     * Accessor: Decrypt state when accessed
     */
    public function getDecryptedStateAttribute(): ?string
    {
        return $this->decryptField($this->attributes['state'] ?? null);
    }

    /**
     * Accessor: Decrypt district when accessed
     */
    public function getDecryptedDistrictAttribute(): ?string
    {
        return $this->decryptField($this->attributes['district'] ?? null);
    }

    /**
     * Accessor: Decrypt taluka when accessed
     */
    public function getDecryptedTalukaAttribute(): ?string
    {
        return $this->decryptField($this->attributes['taluka'] ?? null);
    }

    /**
     * Accessor: Decrypt whatsappno when accessed
     */
    public function getDecryptedWhatsappnoAttribute(): ?string
    {
        return $this->decryptField($this->attributes['whatsappno'] ?? null);
    }

    /**
     * Helper method to decrypt a specific field value
     */
    public function decryptValue(string $fieldName): ?string
    {
        return $this->decryptField($this->attributes[$fieldName] ?? null);
    }

    /**
     * Mutator: Encrypt name when setting
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $this->encryptField($value);
    }

    /**
     * Mutator: Encrypt phone when setting
     */
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = $this->encryptField($value);
        $this->attributes['PhoneForIndex'] = $this->encryptField($value);
    }

    /**
     * Mutator: Encrypt email when setting
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $this->encryptField($value);
    }

    /**
     * Mutator: Encrypt city when setting
     */
    public function setCityAttribute($value)
    {
        $this->attributes['city'] = $this->encryptField($value);
    }
}
