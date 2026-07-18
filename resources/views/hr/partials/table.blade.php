<div class="hr-core-table-wrap">
    <table class="hr-core-table">
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $index => $cell)
                        <td data-label="{{ $headers[$index] ?? '' }}">{!! $cell !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" class="hr-core-muted">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
