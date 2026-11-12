<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;

class BpjsConfiguration extends Model
{
    protected $connection = 'central';

    protected $table = 'application_bpjs_configurations';

    protected $fillable = ['id', 'cons_id', 'secret_key', 'user_key_antrol', 'user_key_pcare', 'kode_aplikasi', 'username', 'password'];

    protected $hidden = ['secret_key', 'user_key_antrol', 'user_key_pcare', 'password'];

    protected function casts(): array
    {
        return [
            'secret_key' => 'encrypted',
            'user_key_antrol' => 'encrypted',
            'user_key_pcare' => 'encrypted',
            'password' => 'encrypted',
        ];
    }

    public function formCredentials(): array
    {
        $state = ['values' => [], 'invalid' => []];
        foreach (['secret_key', 'user_key_antrol', 'user_key_pcare', 'password'] as $field) {
            try {
                $value = $this->getAttribute($field);
                $state['values'][$field] = $value;
                if (blank($value)) {
                    $state['invalid'][] = $field;
                }
            } catch (DecryptException $exception) {
                // Jangan menganggap ciphertext rusak sebagai kredensial plaintext yang valid.
                $state['values'][$field] = '';
                $state['invalid'][] = $field;
            }
        }

        return $state;
    }

    public function originalIsEquivalent($key)
    {
        try {
            return parent::originalIsEquivalent($key);
        } catch (DecryptException $exception) {
            if ($this->isEncryptedCastable($key)) {
                // Nilai asli yang rusak tidak boleh menghalangi penyimpanan penggantinya.
                return false;
            }
            throw $exception;
        }
    }
}
