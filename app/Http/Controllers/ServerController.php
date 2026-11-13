<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function index(): View
    {
        return view('servers.index', [
            'servers' => request()->user()->servers()->withCount('monitors')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('servers.create', ['server' => new Server()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $server = $request->user()->servers()->create($this->validated($request));

        return redirect()
            ->route('servers.index')
            ->with('status', "Server \"{$server->name}\" ditambahkan.");
    }

    public function edit(Server $server): View
    {
        return view('servers.edit', ['server' => $server]);
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        $server->update($this->validated($request, $server));

        return redirect()
            ->route('servers.index')
            ->with('status', "Server \"{$server->name}\" diperbarui.");
    }

    public function destroy(Server $server): RedirectResponse
    {
        $name = $server->name;
        $server->delete();

        return redirect()
            ->route('servers.index')
            ->with('status', "Server \"{$name}\" dihapus. Monitor terkait tetap ada tanpa server.");
    }

    /**
     * @return array{name: string, hostname: string, ip: string|null, description: string|null}
     */
    private function validated(Request $request, ?Server $server = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('servers', 'name')
                    ->where('user_id', $request->user()->id)
                    ->ignore($server?->id),
            ],
            'hostname' => ['required', 'string', 'max:255'],
            'ip' => ['nullable', 'ip'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['description'] = $data['description'] ?? null;
        $data['ip'] = $data['ip'] ?? null;

        return $data;
    }
}
