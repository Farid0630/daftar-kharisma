<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail User</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        .header { margin-bottom: 14px; }
        .title { font-size: 16px; font-weight: bold; }
        .sub { color: #475569; margin-top: 4px; }
        .section-title { margin-top: 16px; font-size: 13px; font-weight: bold; }
        .card { border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { width: 190px; background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-no { background: #fee2e2; color: #991b1b; }
        .muted { color: #64748b; font-size: 10px; }
        .empty { color: #64748b; font-style: italic; }
        pre { margin: 0; white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body>
@php
    // Field yang tidak boleh ditampilkan di PDF
    $exclude = [
        'password','remember_token','two_factor_secret','two_factor_recovery_codes',
        'created_at','updated_at', // optional, kalau mau tampilkan hapus dari exclude
    ];

    $excludeLike = [
        'token', 'secret', 'remember', 'two_factor'
    ];

    $isExcluded = function($key) use ($exclude, $excludeLike) {
        $k = strtolower((string)$key);
        if (in_array($k, $exclude, true)) return true;
        foreach ($excludeLike as $needle) {
            if (str_contains($k, $needle)) return true;
        }
        return false;
    };

    $renderVal = function($v) {
        if (is_null($v)) return '-';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_array($v)) return json_encode($v, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        if (is_object($v)) return json_encode($v, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        return (string)$v;
    };

    $toPairs = function($model) use ($isExcluded, $renderVal) {
        if (!$model) return [];
        $arr = method_exists($model, 'toArray') ? $model->toArray() : (array)$model;

        // sort key biar rapi
        ksort($arr);

        $out = [];
        foreach ($arr as $k => $v) {
            if ($isExcluded($k)) continue;
            $out[] = [
                'key' => (string)$k,
                'val' => $renderVal($v),
            ];
        }
        return $out;
    };

    $userPairs = $toPairs($user);
    $mandiriPairs = $toPairs($pmbMandiri);
    $kipPairs = $toPairs($pmbKip);
    $yayasanPairs = $toPairs($pmbYayasan);
@endphp

<div class="header">
    <div class="title">Detail Data User</div>
    <div class="sub">Dicetak: {{ now()->format('d M Y H:i') }}</div>
    <div class="muted">User: #{{ $user->id }} — {{ $user->name }} ({{ $user->email }})</div>
</div>

<div class="section-title">A. Data User</div>
<div class="card">
    <table>
        @foreach($userPairs as $row)
            <tr>
                <th>{{ $row['key'] }}</th>
                <td>
                    @if($row['key'] === 'is_admin')
                        @if(!empty($user->is_admin))
                            <span class="badge badge-ok">YES</span>
                        @else
                            <span class="badge badge-no">NO</span>
                        @endif
                    @else
                        <pre>{{ $row['val'] }}</pre>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</div>

<div class="section-title">B. PMB Mandiri</div>
<div class="card">
    @if($pmbMandiri)
        <table>
            @foreach($mandiriPairs as $row)
                <tr>
                    <th>{{ $row['key'] }}</th>
                    <td><pre>{{ $row['val'] }}</pre></td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="empty">Tidak ditemukan data PMB Mandiri untuk email user ini.</div>
    @endif
</div>

<div class="section-title">C. PMB KIP</div>
<div class="card">
    @if($pmbKip)
        <table>
            @foreach($kipPairs as $row)
                <tr>
                    <th>{{ $row['key'] }}</th>
                    <td><pre>{{ $row['val'] }}</pre></td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="empty">Tidak ditemukan data PMB KIP untuk email user ini.</div>
    @endif
</div>

<div class="section-title">D. PMB Yayasan</div>
<div class="card">
    @if($pmbYayasan)
        <table>
            @foreach($yayasanPairs as $row)
                <tr>
                    <th>{{ $row['key'] }}</th>
                    <td><pre>{{ $row['val'] }}</pre></td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="empty">Tidak ditemukan data PMB Yayasan untuk email user ini.</div>
    @endif
</div>

</body>
</html>
