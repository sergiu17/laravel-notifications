<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0-rc2/dist/web/pusher.min.js"></script>

    <style>
        .status-badge { font-weight: 500; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .status-pending   { background: #fef3c7; color: #92400e; }
        .status-queued    { background: #dbeafe; color: #1e40af; }
        .status-processed { background: #dcfce7; color: #166534; }
        .status-failed    { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #374151; }
        .status-invalid   { background: #fecaca; color: #7f1d1d; }

        @keyframes flash {
            0%   { background-color: #fef9c3; }
            100% { background-color: transparent; }
        }
        .flash-update { animation: flash 1.5s ease-out; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
<div class="max-w-7xl mx-auto p-6" x-data="dashboard()" x-init="init()">

    <header class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Notifications Dashboard</h1>
        <div class="flex items-center text-sm">
            <span class="w-2 h-2 rounded-full mr-2"
                  :class="connected ? 'bg-green-500' : 'bg-gray-400'"></span>
            <span x-text="connected ? 'Live' : 'Connecting…'"
                  class="text-gray-600"></span>
        </div>
    </header>

    {{-- Filters --}}
    <form method="GET" action="{{ url('/dashboard') }}"
          class="bg-white p-4 rounded shadow mb-4 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">

        <label class="block text-sm md:col-span-1">
            <span class="text-gray-600">Status</span>
            <select name="status" class="w-full border rounded p-2 mt-1">
                <option value="">— all —</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}"
                            @selected(($filters['status'] ?? '') === $status->value)>
                        {{ $status->value }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="block text-sm md:col-span-1">
            <span class="text-gray-600">Channel</span>
            <select name="channel" class="w-full border rounded p-2 mt-1">
                <option value="">— all —</option>
                @foreach ($channels as $channel)
                    <option value="{{ $channel->value }}"
                            @selected(($filters['channel'] ?? '') === $channel->value)>
                        {{ $channel->value }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="block text-sm md:col-span-2">
            <span class="text-gray-600">Batch ID</span>
            <input type="text" name="batch_id"
                   value="{{ $filters['batch_id'] ?? '' }}"
                   placeholder="01jzw…"
                   class="w-full border rounded p-2 mt-1 font-mono text-xs">
        </label>

        <label class="block text-sm md:col-span-1">
            <span class="text-gray-600">From</span>
            <input type="datetime-local" name="from"
                   value="{{ $filters['from'] ?? '' }}"
                   class="w-full border rounded p-2 mt-1">
        </label>

        <label class="block text-sm md:col-span-1">
            <span class="text-gray-600">To</span>
            <input type="datetime-local" name="to"
                   value="{{ $filters['to'] ?? '' }}"
                   class="w-full border rounded p-2 mt-1">
        </label>

        <div class="md:col-span-6 flex gap-2 justify-end">
            <a href="{{ url('/dashboard') }}"
               class="px-4 py-2 rounded bg-gray-200 text-gray-700 text-sm hover:bg-gray-300">
                Reset
            </a>
            <button type="submit"
                    class="px-4 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">
                Apply filters
            </button>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                <tr>
                    <th class="text-left p-3">ID</th>
                    <th class="text-left p-3">Batch</th>
                    <th class="text-left p-3">Channel</th>
                    <th class="text-left p-3">Recipient</th>
                    <th class="text-left p-3">Priority</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-left p-3">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($notifications as $n)
                    <tr id="notification-{{ $n->id }}" class="border-t hover:bg-gray-50">
                        <td class="p-3 font-mono text-xs">{{ $n->id }}</td>
                        <td class="p-3 font-mono text-xs text-gray-600">
                            <a href="{{ url('/dashboard?batch_id=' . $n->batch_id) }}"
                               class="hover:underline">
                                {{ \Illuminate\Support\Str::limit($n->batch_id, 12, '…') }}
                            </a>
                        </td>
                        <td class="p-3">{{ $n->channel }}</td>
                        <td class="p-3 font-mono text-xs">{{ $n->recipient }}</td>
                        <td class="p-3">{{ $n->priority?->value }}</td>
                        <td class="p-3">
                            <span class="status-badge status-{{ $n->status?->value }}"
                                  data-status-cell>
                                {{ $n->status?->value }}
                            </span>
                        </td>
                        <td class="p-3 text-gray-600 text-xs">
                            {{ $n->created_at?->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-500">
                            No notifications match these filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $notifications->links() }}
    </div>

</div>

<script>
    function dashboard() {
        return {
            connected: false,

            init() {
                const pusher = new Pusher(@json(config('broadcasting.connections.reverb.key')), {
                    cluster: 'mt1',
                    wsHost:  @json(config('broadcasting.connections.reverb.options.host')),
                    wsPort:  @json((int) config('broadcasting.connections.reverb.options.port')),
                    wssPort: @json((int) config('broadcasting.connections.reverb.options.port')),
                    forceTLS: @json(config('broadcasting.connections.reverb.options.scheme') === 'https'),
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                });

                pusher.connection.bind('connected',    () => this.connected = true);
                pusher.connection.bind('disconnected', () => this.connected = false);
                pusher.connection.bind('error',        () => this.connected = false);

                const channel = pusher.subscribe('notifications');
                channel.bind('notification.status-updated', (data) => this.applyUpdate(data));
                channel.bind('notification.created', (data) => this.appendNotification(data));
            },

            applyUpdate(data) {
                const row = document.getElementById(`notification-${data.id}`);
                if (!row) return;   // not visible on this page / under current filters

                const badge = row.querySelector('[data-status-cell]');
                if (!badge) return;

                // Update the badge text + colour class.
                badge.textContent = data.status;
                badge.className = `status-badge status-${data.status}`;

                // Flash the row briefly to draw the eye.
                row.classList.remove('flash-update');
                void row.offsetWidth;       // force reflow so the animation restarts
                row.classList.add('flash-update');
            },

            appendNotification(data) {
                const tbody = document.querySelector('tbody');

                if (document.getElementById(`notification-${data.id}`)) return;

                // Remove "No notifications match…" empty-state row if present.
                const emptyCell = tbody.querySelector('td[colspan]');
                if (emptyCell) emptyCell.closest('tr').remove();

                const batchShort = data.batch_id
                    ? `${String(data.batch_id).slice(0, 12)}${String(data.batch_id).length > 12 ? '…' : ''}`
                    : '';

                const tr = document.createElement('tr');
                tr.id = `notification-${data.id}`;
                tr.className = 'border-t hover:bg-gray-50';
                tr.innerHTML = `
                    <td class="p-3 font-mono text-xs">${data.id}</td>
                    <td class="p-3 font-mono text-xs text-gray-600">
                        <a href="/dashboard?batch_id=${encodeURIComponent(data.batch_id ?? '')}"
                           class="hover:underline">${batchShort}</a>
                    </td>
                    <td class="p-3">${data.channel}</td>
                    <td class="p-3 font-mono text-xs">${data.recipient}</td>
                    <td class="p-3">${data.priority}</td>
                    <td class="p-3">
                        <span class="status-badge status-${data.status}"
                              data-status-cell>${data.status}</span>
                    </td>
                    <td class="p-3 text-gray-600 text-xs">just now</td>
                `;

                tbody.prepend(tr);
                tr.classList.add('flash-update');

                const MAX_ROWS = 25;

                while (tbody.rows.length > MAX_ROWS) {
                    tbody.deleteRow(-1);
                }
            },
        };
    }
</script>
</body>
</html>
