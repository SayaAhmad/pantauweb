<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentMetricController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) $request->header('X-Agent-Token', '');

        if ($token === '') {
            return response()->json(['message' => 'Token agent wajib dikirim pada header X-Agent-Token.'], 401);
        }

        $server = Server::query()
            ->whereNotNull('agent_token_hash')
            ->get()
            ->first(fn (Server $candidate) => Hash::check($token, $candidate->agent_token_hash));

        if ($server === null) {
            return response()->json(['message' => 'Token agent tidak valid.'], 401);
        }

        $data = $request->validate([
            'cpu_percent' => ['nullable', 'numeric', 'between:0,100'],
            'ram_percent' => ['nullable', 'numeric', 'between:0,100'],
            'disk_percent' => ['nullable', 'numeric', 'between:0,100'],
            'load_average' => ['nullable', 'numeric', 'min:0'],
        ]);

        $server->metrics()->create([
            'cpu_percent' => $data['cpu_percent'] ?? null,
            'ram_percent' => $data['ram_percent'] ?? null,
            'disk_percent' => $data['disk_percent'] ?? null,
            'load_average' => $data['load_average'] ?? null,
            'collected_at' => now(),
        ]);

        $server->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['status' => 'ok'], 201);
    }
}
