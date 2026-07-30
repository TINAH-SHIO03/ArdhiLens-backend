@php
    /** @var bool $eligible */
    /** @var string|null $reason */
    /** @var string $mode */
@endphp

<div class="space-y-3 text-sm">
    <p>
        <strong>Certificate type:</strong> {{ $mode }}
    </p>
    @if ($eligible)
        <p class="text-success-600 dark:text-success-400">
            This verification log is eligible for certificate issuance.
        </p>
    @else
        <p class="text-danger-600 dark:text-danger-400">
            Not eligible{{ $reason ? ': '.$reason : '.' }}
        </p>
    @endif
</div>
