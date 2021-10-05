<?php

namespace App\Lib;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;

class AESLib {

    public function __construct($cipher = 'AES-128-CBC')
    {
        $key = env('API_KEY','oZOC1akwNnxk0mEY');
        $iv = env('API_IV','49E362C26AA63XEC');
        $this->key = $key;
        $this->iv  = $iv;
        $this->cipher = $cipher;
    }

    public function genIV()
    {
        return substr(sha1(time()),rand(1,10),16);
    }

    public function encode($value,$iv='')
    {
        $iv = ($iv)? $iv : $this->iv;
        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, 0, $iv);

        if ($encrypted === false) {
//            echo 'key='.$this->key.'<br/>';
//            echo 'iv='.$this->iv.'<br/>';
//            throw new EncryptException('Could not encrypt the data.');
        }

        return $encrypted;
    }


    public function decode($value,$iv = '')
    {
        $iv = ($iv)? $iv : $this->iv;
        $decrypted = openssl_decrypt($value, $this->cipher, $this->key, OPENSSL_ZERO_PADDING, $iv);

        if ($decrypted === false) {
//            throw new DecryptException('Could not decrypt the data.');
        }

        return $decrypted;
    }

}
