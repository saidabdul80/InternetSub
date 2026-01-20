<?php

namespace App\Services;

use App\Models\MikrotikRouter;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class MikrotikService
{
    public function __construct(private readonly ?MikrotikRouter $router = null)
    {
    }

    public function createHotspotUser(
        string $username,
        string $password,
        string $profile,
        string $comment
    ): void {
        $client = new Client($this->resolveConfig());

        $query = (new Query('/ip/hotspot/user/add'))
            ->equal('name', $username)
            ->equal('password', $password)
            ->equal('profile', $profile)
            ->equal('comment', $comment);

        $client->query($query)->read();
    }

    public function upsertHotspotUser(
        string $username,
        string $password,
        string $profile,
        string $comment
    ): void {
        $client = new Client($this->resolveConfig());
        $userId = $this->findHotspotUserId($client, $username);

        if ($userId !== null) {
            $this->resetHotspotUser($client, $userId, $password, $profile, $comment);
            $this->resetHotspotUserCounters($client, $userId);

            return;
        }

        $query = (new Query('/ip/hotspot/user/add'))
            ->equal('name', $username)
            ->equal('password', $password)
            ->equal('profile', $profile)
            ->equal('comment', $comment);

        $client->query($query)->read();
    }

    private function findHotspotUserId(Client $client, string $username): ?string
    {
        $query = (new Query('/ip/hotspot/user/print'))
            ->where('name', $username);
        $results = $client->query($query)->read();

        if ($results === [] || ! isset($results[0]['.id'])) {
            return null;
        }

        return (string) $results[0]['.id'];
    }

    private function resetHotspotUser(
        Client $client,
        string $userId,
        string $password,
        string $profile,
        string $comment
    ): void {
        $query = (new Query('/ip/hotspot/user/set'))
            ->equal('.id', $userId)
            ->equal('password', $password)
            ->equal('profile', $profile)
            ->equal('comment', $comment)
            ->equal('disabled', 'no');

        $client->query($query)->read();
    }

    private function resetHotspotUserCounters(Client $client, string $userId): void
    {
        $query = (new Query('/ip/hotspot/user/reset-counters'))
            ->equal('numbers', $userId);

        $client->query($query)->read();
    }

    private function resolveConfig(): Config
    {
        if ($this->router !== null) {
            return new Config([
                'host' => $this->router->host,
                'user' => $this->router->username,
                'pass' => $this->router->password,
                'port' => (int) $this->router->port,
                'ssl' => (bool) $this->router->use_ssl,
                'timeout' => (int) $this->router->timeout,
            ]);
        }

        $socketOptions = config('hotspot.mikrotik.socket_options', []);

        return new Config([
            'host' => config('hotspot.mikrotik.host'),
            'user' => config('hotspot.mikrotik.username'),
            'pass' => config('hotspot.mikrotik.password'),
            'port' => (int) config('hotspot.mikrotik.port'),
            'ssl' => (bool) config('hotspot.mikrotik.ssl'),
            'timeout' => (int) config('hotspot.mikrotik.timeout'),
            'socket_options' => is_array($socketOptions) ? $socketOptions : [],
        ]);
    }

    public function testConnection(): bool
    {
        try {
            $client = new Client($this->resolveConfig());
            // Try a simple query
            $query = new Query('/system/identity/print');
            $client->query($query)->read();
            return true;
        } catch (\Exception $e) {
            Log::error('Mikrotik connection test failed', [
                'error' => $e->getMessage(),
                'config' => $this->resolveConfig()->getParameters(),
            ]);
            return false;
        }
    }
}
