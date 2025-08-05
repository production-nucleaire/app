@foreach ($plants as $plant)
    <div wire:key="plant-{{ $plant['id'] }}" class="border-b border-slate-200 py-2">
        <h3 class="font-semibold">{{ $plant['name'] }}</h3>
        <p class="text-sm text-slate-500">Active Reactors: {{ $plant['active_reactors'] }}</p>
    </div>
@endforeach