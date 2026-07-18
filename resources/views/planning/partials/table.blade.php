<div class="planning-table-wrap">
    <table class="planning-table">
        <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td>{!! $cell !!}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}"><div class="planning-muted">No records found.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
