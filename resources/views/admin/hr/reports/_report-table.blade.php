{{-- $title, $headers, $rows (array of arrays), $exportRoute, $exportParams --}}
<div class="panel">
    <div class="panel-head" style="justify-content:space-between;">
        <h3>{{ $title }} ({{ count($rows) }})</h3>
        <div class="export-dd" style="position:relative;">
            <button type="button" class="btn btn-primary" style="padding:8px 14px;font-size:12.5px;" onclick="this.nextElementSibling.classList.toggle('open')">
                Export ▾
            </button>
            <div class="export-dd-menu">
                <a href="{{ route($exportRoute, array_merge($exportParams, ['format' => 'pdf'])) }}">⬇ Download PDF</a>
                <a href="{{ route($exportRoute, array_merge($exportParams, ['format' => 'excel'])) }}">⬇ Download Excel</a>
                <a href="{{ route($exportRoute, array_merge($exportParams, ['format' => 'print'])) }}" target="_blank">🖶 Print</a>
            </div>
        </div>
    </div>
    @if (empty($rows))
        <div style="padding:32px 20px;text-align:center;color:var(--ink-soft);font-size:13.5px;">No data for this selection.</div>
    @else
        <div style="overflow-x:auto;">
            <table>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</div>

<style>
    .export-dd-menu{display:none;position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);min-width:180px;z-index:20;overflow:hidden;}
    .export-dd-menu.open{display:block;}
    .export-dd-menu a{display:block;padding:10px 14px;font-size:12.5px;font-weight:600;color:var(--ink);text-decoration:none;}
    .export-dd-menu a:hover{background:var(--bg);}
</style>

<script>
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.export-dd-menu.open').forEach(function (menu) {
            if (!menu.parentElement.contains(e.target)) menu.classList.remove('open');
        });
    });
</script>
