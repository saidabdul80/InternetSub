<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class MikrotikService
{
    private $socket = null;
    private ?string $lastCommand = null;

    public function provisionAccessUser(
        string $username,
        string $password,
        string $profile,
        ?string $comment = null,
        ?int $planType = null
    ): void
    {
        $provisioner = strtolower((string) config('services.mikrotik.provisioner', 'hotspot'));

        if ($provisioner === 'userman') {
            $this->createOrUpdateUserManagerUser($username, $password, $profile, $comment);

            return;
        }

        $this->createOrUpdateHotspotUser($username, $password, $profile, $comment, $planType);
    }

    public function createOrUpdateHotspotUser(
        string $username,
        string $password,
        string $profile,
        ?string $comment = null,
        ?int $planType = null
    ): void
    {
        $this->connect();

        try {
            $userId = $this->findHotspotUserIdByName($username);
            $hotspotLimitAttributes = $this->hotspotLimitAttributesForPlan($planType);

            if ($userId !== null) {
                $attributes = array_merge([
                    '=.id='.$userId,
                    '=password='.$password,
                    '=profile='.$profile,
                    '=disabled=no',
                ], $hotspotLimitAttributes);

                if ($comment !== null && $comment !== '') {
                    $attributes[] = '=comment='.$comment;
                }

                $this->command('/ip/hotspot/user/set', $attributes);
                // 2. IMPORTANT: Reset counters so they can start fresh with the new limit
                $this->command('/ip/hotspot/user/reset-counters', [
                    '=.id=' . $userId
                ]);

                return;
            }

            $attributes = array_merge([
                '=name='.$username,
                '=password='.$password,
                '=profile='.$profile,
                '=disabled=no',
            ], $hotspotLimitAttributes);

            if ($comment !== null && $comment !== '') {
                $attributes[] = '=comment='.$comment;
            }

            $this->command('/ip/hotspot/user/add', $attributes);
        } finally {
            $this->disconnect();
        }
    }

    public function createOrUpdateUserManagerUser(string $username, string $password, string $profile, ?string $comment = null): void
    {
        $this->connect();

        try {
            $customer = (string) config('services.mikrotik.userman_customer', 'admin');
            $sharedUsers = (string) config('services.mikrotik.userman_shared_users', '1');

            $userId = $this->findUserManagerUserIdByUsername($username);

            if ($userId !== null) {
                $setAttributes = [
                    '=.id='.$userId,
                    '=password='.$password,
                    '=shared-users='.$sharedUsers,
                ];

                if ($comment !== null && $comment !== '') {
                    $setAttributes[] = '=comment='.$comment;
                }

                $this->command('/tool/user-manager/user/set', $setAttributes);
            } else {
                $addAttributes = [
                    '=customer='.$customer,
                    '=username='.$username,
                    '=password='.$password,
                    '=shared-users='.$sharedUsers,
                ];

                if ($comment !== null && $comment !== '') {
                    $addAttributes[] = '=comment='.$comment;
                }

                $this->command('/tool/user-manager/user/add', $addAttributes);
            }

            $this->activateUserManagerProfile($customer, $username, $profile);
        } finally {
            $this->disconnect();
        }
    }

    public function profileForPlan(int $planType): string
    {
        $map = (array) config('services.mikrotik.profile_map', []);

        if (array_key_exists((string) $planType, $map)) {
            return (string) $map[(string) $planType];
        }

        if (array_key_exists($planType, $map)) {
            return (string) $map[$planType];
        }

        return (string) config('services.mikrotik.default_profile', 'default');
    }

    /**
     * @return array<int, string>
     */
    private function hotspotLimitAttributesForPlan(?int $planType): array
    {
        if ($planType === null) {
            return [];
        }

        $limitsMap = (array) config('services.mikrotik.hotspot_plan_limits', []);
        $planLimits = [];

        if (array_key_exists((string) $planType, $limitsMap)) {
            $planLimits = (array) $limitsMap[(string) $planType];
        } elseif (array_key_exists($planType, $limitsMap)) {
            $planLimits = (array) $limitsMap[$planType];
        }

        if ($planLimits === []) {
            return [];
        }

        $attributes = [];

        $limitUptime = trim((string) ($planLimits['limit_uptime'] ?? ''));
        if ($limitUptime !== '') {
            $attributes[] = '=limit-uptime='.$limitUptime;
        }

        $limitBytesTotalRaw = $planLimits['limit_bytes_total'] ?? null;
        if ($limitBytesTotalRaw !== null) {
            $limitBytesTotal = trim((string) $limitBytesTotalRaw);
            if ($limitBytesTotal !== '' && ctype_digit($limitBytesTotal) && $limitBytesTotal !== '0') {
                $attributes[] = '=limit-bytes-total='.$limitBytesTotal;
            }
        }

        return $attributes;
    }

    private function connect(): void
    {
        $host = (string) config('services.mikrotik.host');
        $port = (int) config('services.mikrotik.port', 8728);
        $username = (string) config('services.mikrotik.username');
        $password = (string) config('services.mikrotik.password');
        $timeout = (int) config('services.mikrotik.timeout', 5);
        $useSsl = filter_var(config('services.mikrotik.use_ssl', false), FILTER_VALIDATE_BOOLEAN);

        if ($host === '' || $username === '' || $password === '') {
            throw new RuntimeException('MikroTik connection settings are incomplete.');
        }

        $transport = $useSsl ? 'ssl://' : '';

        $socket = @fsockopen($transport.$host, $port, $errno, $errstr, $timeout);

        if (! is_resource($socket)) {
            throw new RuntimeException("Unable to connect to MikroTik API: {$errstr} ({$errno}).");
        }

        stream_set_timeout($socket, $timeout);

        $this->socket = $socket;

        $loginReplies = $this->command('/login', [
            '=name='.$username,
            '=password='.$password,
        ]);

        $legacyChallenge = $this->extractReplyAttribute($loginReplies, 'ret');

        if ($legacyChallenge !== null && $legacyChallenge !== '') {
            $challengeBinary = hex2bin($legacyChallenge);

            if ($challengeBinary === false) {
                throw new RuntimeException('MikroTik returned an invalid login challenge.');
            }

            $response = '00'.md5(chr(0).$password.$challengeBinary);

            $this->command('/login', [
                '=name='.$username,
                '=response='.$response,
            ]);
        }
    }

    private function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    private function findHotspotUserIdByName(string $username): ?string
    {
        $replies = $this->command('/ip/hotspot/user/print', [
            '?name='.$username,
        ]);

        foreach ($replies as $reply) {
            if (($reply['type'] ?? '') !== '!re') {
                continue;
            }

            $attributes = $reply['attributes'] ?? [];
            $id = $attributes['.id'] ?? null;

            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        return null;
    }

    private function findUserManagerUserIdByUsername(string $username): ?string
    {
        $replies = $this->command('/tool/user-manager/user/print');

        foreach ($replies as $reply) {
            if (($reply['type'] ?? '') !== '!re') {
                continue;
            }

            $attributes = $reply['attributes'] ?? [];
            $currentUsername = (string) ($attributes['username'] ?? $attributes['name'] ?? '');

            if ($currentUsername !== $username) {
                continue;
            }

            $id = $attributes['.id'] ?? null;

            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        return null;
    }

    private function activateUserManagerProfile(string $customer, string $username, string $profile): void
    {
        $attempts = [
            [
                '=customer='.$customer,
                '=user='.$username,
                '=profile='.$profile,
            ],
            [
                '=customer='.$customer,
                '=username='.$username,
                '=profile='.$profile,
            ],
            [
                '=customer='.$customer,
                '=user='.$username,
                '=profile='.$profile,
                '=numbers=1',
            ],
        ];

        $lastException = null;

        foreach ($attempts as $attributes) {
            try {
                $this->command('/tool/user-manager/user/create-and-activate-profile', $attributes);

                return;
            } catch (RuntimeException $exception) {
                $message = strtolower($exception->getMessage());

                if (str_contains($message, 'already') || str_contains($message, 'active profile')) {
                    return;
                }

                $lastException = $exception;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new RuntimeException('Failed to activate User Manager profile.');
    }

    /**
     * @param  array<int, string>  $attributes
     * @return array<int, array{type: string, attributes: array<string, string>}>
     */
    private function command(string $command, array $attributes = []): array
    {
        $this->lastCommand = $command;
        $this->writeSentence(array_merge([$command], $attributes));

        return $this->readReplies();
    }

    /**
     * @param  array<int, string>  $words
     */
    private function writeSentence(array $words): void
    {
        foreach ($words as $word) {
            $this->writeWord($word);
        }

        $this->writeWord('');
    }

    private function writeWord(string $word): void
    {
        $this->writeLength(strlen($word));
        $this->writeRaw($word);
    }

    private function writeRaw(string $payload): void
    {
        if (! is_resource($this->socket)) {
            throw new RuntimeException('MikroTik socket is not connected.');
        }

        $remaining = $payload;

        while ($remaining !== '') {
            $written = fwrite($this->socket, $remaining);

            if ($written === false || $written === 0) {
                throw new RuntimeException('Failed to write to MikroTik socket.');
            }

            $remaining = substr($remaining, $written);
        }
    }

    private function writeLength(int $length): void
    {
        if ($length < 0x80) {
            $this->writeRaw(chr($length));

            return;
        }

        if ($length < 0x4000) {
            $length |= 0x8000;
            $this->writeRaw(chr(($length >> 8) & 0xFF).chr($length & 0xFF));

            return;
        }

        if ($length < 0x200000) {
            $length |= 0xC00000;
            $this->writeRaw(chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF));

            return;
        }

        if ($length < 0x10000000) {
            $length |= 0xE0000000;
            $this->writeRaw(chr(($length >> 24) & 0xFF).chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF));

            return;
        }

        $this->writeRaw(chr(0xF0).chr(($length >> 24) & 0xFF).chr(($length >> 16) & 0xFF).chr(($length >> 8) & 0xFF).chr($length & 0xFF));
    }

    /**
     * @return array<int, array{type: string, attributes: array<string, string>}>
     */
    private function readReplies(): array
    {
        $replies = [];

        while (true) {
            $words = $this->readSentence();

            if ($words === []) {
                continue;
            }

            $type = array_shift($words);

            if (! is_string($type) || $type === '') {
                continue;
            }

            $attributes = $this->parseAttributes($words);

            if ($type === '!trap' || $type === '!fatal') {
                $message = $attributes['message'] ?? $attributes['category'] ?? 'MikroTik API command failed.';
                $context = [
                    'command' => $this->lastCommand,
                    'reply_type' => $type,
                    'attributes' => $attributes,
                ];

                Log::error('MikroTik API trap/fatal response', $context);

                $command = $this->lastCommand ?: 'unknown command';
                $serializedAttributes = json_encode($attributes, JSON_UNESCAPED_SLASHES);

                throw new RuntimeException(sprintf(
                    'MikroTik API command failed on %s (%s): %s',
                    $command,
                    $type,
                    $serializedAttributes ?: $message
                ));
            }

            $replies[] = [
                'type' => $type,
                'attributes' => $attributes,
            ];

            if ($type === '!done') {
                break;
            }
        }

        return $replies;
    }

    /**
     * @return array<int, string>
     */
    private function readSentence(): array
    {
        $words = [];

        while (true) {
            $word = $this->readWord();

            if ($word === '') {
                break;
            }

            $words[] = $word;
        }

        return $words;
    }

    private function readWord(): string
    {
        $length = $this->readLength();

        if ($length === 0) {
            return '';
        }

        return $this->readRaw($length);
    }

    private function readLength(): int
    {
        $char = $this->readRaw(1);
        $length = ord($char);

        if (($length & 0x80) === 0x00) {
            return $length;
        }

        if (($length & 0xC0) === 0x80) {
            $length &= ~0xC0;
            $length = ($length << 8) + ord($this->readRaw(1));

            return $length;
        }

        if (($length & 0xE0) === 0xC0) {
            $length &= ~0xE0;
            $length = ($length << 8) + ord($this->readRaw(1));
            $length = ($length << 8) + ord($this->readRaw(1));

            return $length;
        }

        if (($length & 0xF0) === 0xE0) {
            $length &= ~0xF0;
            $length = ($length << 8) + ord($this->readRaw(1));
            $length = ($length << 8) + ord($this->readRaw(1));
            $length = ($length << 8) + ord($this->readRaw(1));

            return $length;
        }

        $length = ord($this->readRaw(1));
        $length = ($length << 8) + ord($this->readRaw(1));
        $length = ($length << 8) + ord($this->readRaw(1));
        $length = ($length << 8) + ord($this->readRaw(1));

        return $length;
    }

    private function readRaw(int $length): string
    {
        if (! is_resource($this->socket)) {
            throw new RuntimeException('MikroTik socket is not connected.');
        }

        $buffer = '';

        while (strlen($buffer) < $length) {
            $chunk = fread($this->socket, $length - strlen($buffer));

            if (! is_string($chunk) || $chunk === '') {
                throw new RuntimeException('Failed to read from MikroTik socket.');
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }

    /**
     * @param  array<int, string>  $words
     * @return array<string, string>
     */
    private function parseAttributes(array $words): array
    {
        $attributes = [];

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            if (str_starts_with($word, '=')) {
                $parts = explode('=', $word, 3);
                $key = $parts[1] ?? '';
                $value = $parts[2] ?? '';

                if ($key !== '') {
                    $attributes[$key] = $value;
                }

                continue;
            }

            if (str_starts_with($word, '.')) {
                $parts = explode('=', $word, 2);
                $key = $parts[0];
                $value = $parts[1] ?? '';
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    /**
     * @param  array<int, array{type: string, attributes: array<string, string>}>  $replies
     */
    private function extractReplyAttribute(array $replies, string $key): ?string
    {
        foreach ($replies as $reply) {
            $attributes = $reply['attributes'] ?? [];
            $value = $attributes[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
