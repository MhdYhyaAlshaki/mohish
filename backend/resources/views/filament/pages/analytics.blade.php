<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl bg-white p-4 shadow">
            <p class="text-sm text-gray-500">Gross Revenue</p>
            <p class="text-2xl font-semibold">{{ number_format($this->overview['gross_revenue'] ?? 0, 4) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow">
            <p class="text-sm text-gray-500">Net Profit</p>
            <p class="text-2xl font-semibold">{{ number_format($this->overview['net_profit'] ?? 0, 4) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow">
            <p class="text-sm text-gray-500">DAU / WAU</p>
            <p class="text-2xl font-semibold">{{ $this->overview['dau'] ?? 0 }} / {{ $this->overview['wau'] ?? 0 }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-xl bg-white p-4 shadow">
        <h2 class="text-lg font-semibold mb-3">Fraud Buckets</h2>
        <div class="grid grid-cols-3 gap-4">
            <div class="p-3 rounded bg-green-50">Low: {{ $this->fraudBuckets['low'] ?? 0 }}</div>
            <div class="p-3 rounded bg-yellow-50">Medium: {{ $this->fraudBuckets['medium'] ?? 0 }}</div>
            <div class="p-3 rounded bg-red-50">High: {{ $this->fraudBuckets['high'] ?? 0 }}</div>
        </div>
    </div>

    <div class="mt-6 rounded-xl bg-white p-4 shadow">
        <h2 class="text-lg font-semibold mb-3">Top Users by Profit Contribution</h2>
        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2">User</th>
                        <th class="py-2">Net Profit</th>
                        <th class="py-2">Completed Ads</th>
                        <th class="py-2">Completion Rate</th>
                        <th class="py-2">Risk Avg</th>
                        <th class="py-2">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->topUsers as $item)
                        <tr class="border-b">
                            <td class="py-2">{{ $item['name'] }} <span class="text-gray-400">({{ $item['email'] }})</span></td>
                            <td class="py-2">{{ number_format($item['net_profit'], 4) }}</td>
                            <td class="py-2">{{ $item['completed_ads'] }}</td>
                            <td class="py-2">{{ number_format($item['completion_rate'] * 100, 2) }}%</td>
                            <td class="py-2">{{ $item['risk_score_avg'] }}</td>
                            <td class="py-2">{{ $item['score'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
