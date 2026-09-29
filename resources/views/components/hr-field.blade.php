@props([
    'label',
    'model',
    'type' => 'text',
    'options' => null,
    'placeholder' => null,
    'full' => false,
])

<div class="{{ $full ? 'md:col-span-2' : '' }}">
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>

    @if($options)
        <select wire:model="{{ $model }}"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error($model) border-red-500 @enderror">
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ $type }}" wire:model="{{ $model }}" placeholder="{{ $placeholder }}"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error($model) border-red-500 @enderror">
    @endif

    @error($model) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
</div>
