@php
  $name = $name ?? 'file';
  $current = $current ?? '';
  $hint = $hint ?? 'PNG, JPG or WEBP · max 2MB';
  $button = $button ?? 'Choose image';
  $accept = $accept ?? 'image/*';
@endphp
<div class="hpr-file" data-hpr-file>
  <div class="hpr-file__preview {{ $current ? '' : 'is-empty' }}">
    @if($current)
      <img src="{{ $current }}" alt="">
    @else
      <span class="hpr-file__ph">Preview</span>
    @endif
  </div>
  <div class="hpr-file__side">
    <label class="hpr-file__btn">
      <input type="file" name="{{ $name }}" accept="{{ $accept }}">
      <x-icon name="upload" :size="14"/>
      <span>{{ $button }}</span>
    </label>
    <small class="hpr-file__name">{{ $hint }}</small>
  </div>
</div>
