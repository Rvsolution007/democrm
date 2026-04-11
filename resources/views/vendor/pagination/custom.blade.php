@if ($paginator->hasPages())
<div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;gap:12px">
    {{-- Left: Showing info --}}
    <span style="font-size:13px;color:#64748b;font-weight:500">
        Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
    </span>

    {{-- Right: Page buttons --}}
    <nav style="display:flex;align-items:center;gap:6px">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span style="display:inline-flex;align-items:center;padding:8px 14px;font-size:13px;font-weight:500;color:#94a3b8;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;cursor:not-allowed">
                &laquo; Previous
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="display:inline-flex;align-items:center;padding:8px 14px;font-size:13px;font-weight:500;color:#334155;background:#fff;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;transition:all .2s" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
                &laquo; Previous
            </a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                {{-- Dots --}}
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;font-size:13px;color:#94a3b8">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        {{-- Active Page --}}
                        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;font-size:13px;font-weight:700;color:#fff;background:linear-gradient(135deg,#f97316,#ea580c);border:none;border-radius:8px;box-shadow:0 2px 8px rgba(249,115,22,0.35)">
                            {{ $page }}
                        </span>
                    @else
                        {{-- Normal Page --}}
                        <a href="{{ $url }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;font-size:13px;font-weight:500;color:#334155;background:#fff;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;transition:all .2s" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="display:inline-flex;align-items:center;padding:8px 14px;font-size:13px;font-weight:500;color:#334155;background:#fff;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;transition:all .2s" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#fff';this.style.borderColor='#e2e8f0'">
                Next &raquo;
            </a>
        @else
            <span style="display:inline-flex;align-items:center;padding:8px 14px;font-size:13px;font-weight:500;color:#94a3b8;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;cursor:not-allowed">
                Next &raquo;
            </span>
        @endif
    </nav>
</div>
@endif
