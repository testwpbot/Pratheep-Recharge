@once
<style>
.hpr-pager{
  display:flex !important;
  flex-wrap:wrap;
  align-items:center;
  gap:8px;
  margin:4px 0;
}
.hpr-pager__pages{
  display:flex !important;
  flex-wrap:wrap;
  align-items:center;
  gap:8px;
}
.hpr-pager a.hpr-pager__btn,
.hpr-pager span.hpr-pager__btn{
  display:inline-flex !important;
  align-items:center;
  justify-content:center;
  box-sizing:border-box;
  min-width:38px;
  height:38px;
  padding:0 14px;
  border-radius:10px;
  border:1.6px solid rgba(11,42,91,.16);
  background:#fff;
  color:#0b2a5b !important;
  font-family:inherit;
  font-weight:800;
  font-size:13px;
  line-height:1;
  text-decoration:none !important;
  box-shadow:0 1px 0 rgba(7,27,61,.04);
}
.hpr-pager a.hpr-pager__btn:hover{
  border-color:#e8a317;
  background:#fffdf6;
  color:#0b2a5b !important;
}
.hpr-pager span.hpr-pager__btn.is-on{
  background:linear-gradient(135deg,#0b2a5b,#071b3d);
  color:#fff !important;
  border-color:transparent;
}
.hpr-pager span.hpr-pager__btn.is-off{
  opacity:.4;
  pointer-events:none;
}
.hpr-pager__gap{
  padding:0 4px;
  color:#6b7a99;
  font-weight:800;
}
</style>
@endonce
@if ($paginator->hasPages())
  <nav class="hpr-pager" role="navigation" aria-label="Pages">
    @if ($paginator->onFirstPage())
      <span class="hpr-pager__btn is-off">Previous</span>
    @else
      <a class="hpr-pager__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
    @endif

    <span class="hpr-pager__pages">
      @foreach ($elements as $element)
        @if (is_string($element))
          <span class="hpr-pager__gap">{{ $element }}</span>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <span class="hpr-pager__btn is-on">{{ $page }}</span>
            @else
              <a class="hpr-pager__btn" href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach
        @endif
      @endforeach
    </span>

    @if ($paginator->hasMorePages())
      <a class="hpr-pager__btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
    @else
      <span class="hpr-pager__btn is-off">Next</span>
    @endif
  </nav>
@endif
