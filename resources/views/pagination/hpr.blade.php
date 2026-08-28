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
