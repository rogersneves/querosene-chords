@php $items = $getState(); @endphp
@if(is_array($items) && count($items))
    <dl class="space-y-0.5 text-sm">
        @foreach($items as $key => $value)
            <div class="flex gap-1">
                <dt class="font-semibold text-gray-300 shrink-0">{{ $key }}:</dt>
                <dd class="text-gray-100 break-all">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
@endif
