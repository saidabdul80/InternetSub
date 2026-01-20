<?php

namespace Database\Seeders;

use App\Models\MikrotikRouter;
use Illuminate\Database\Seeder;

class MikrotikRouterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MikrotikRouter::create([
            "name"=> "api-user",
            "host"=> "192.168.88.1",
            "port"=> 8728,
            "username"=> "api-user",
            "password"=> "goodnews12345678",
            "use_ssl"=> 0,
            "timeout"=> 10,
            "is_active"=> 1,
            "created_at"=> "2026-01-20 18:20:51",
            "updated_at"=> "2026-01-20 18:20:51",
        ]);
    }
}
