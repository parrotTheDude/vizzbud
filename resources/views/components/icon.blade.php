@props(['name', 'class' => 'w-8 h-8'])

@php
    $svg = file_get_contents(resource_path("icons/{$name}.svg"));
    $svg = preg_replace('/<svg/', "<svg class=\"{$class}\"", $svg, 1);
@endphp

{!! $svg !!}