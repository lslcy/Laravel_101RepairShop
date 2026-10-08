@props(['status' => 'Unknown', 'tone' => null])

@php
    $label = trim((string) $status) ?: 'Unknown';
    $resolvedTone = $tone ?? match (strtolower($label)) {
        'completed', 'paid', 'active', 'available', 'in stock', 'approved' => 'success',
        'pending', 'processing', 'in progress', 'under repair', 'waiting for parts', 'partial', 'busy', 'low stock' => 'warning',
        'cancelled', 'canceled', 'rejected', 'unpaid', 'expired', 'critical', 'out of stock' => 'danger',
        'confirmed', 'scheduled' => 'info',
        default => 'neutral',
    };
    $toneClass = match ($resolvedTone) {
        'success' => 'ui-status-success',
        'warning' => 'ui-status-warning',
        'danger' => 'ui-status-danger',
        'info' => 'ui-status-info',
        default => 'ui-status-neutral',
    };
@endphp

<span {{ $attributes->class(['ui-status-badge', $toneClass]) }}>{{ $slot->isEmpty() ? $label : $slot }}</span>
