<?php

namespace App\Support;

class HotspotLoginUrl
{
    public static function build(string $baseUrl, string $dst, string $username, string $password): string
    {
        $params = [
            'username' => $username,
            'phone' => $username,
            'password' => $password,
            'autologin' => 1,
        ];

        if (trim($dst) !== '') {
            $params['dst'] = $dst;
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query($params);
    }
}
