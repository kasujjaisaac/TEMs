<div class="commercial-table-wrap">
    <table class="commercial-table">
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
                <tr>
                    <td colspan="{{ count($headers) }}" class="commercial-muted" data-label="Status">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
