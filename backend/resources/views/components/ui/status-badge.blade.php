@props(['active' => true, 'on' => 'Aktif', 'off' => 'Nonaktif'])
<span {{ $attributes->class(['badge', $active ? 'badge-ok' : 'badge-off']) }}>{{ $active ? $on : $off }}</span>
