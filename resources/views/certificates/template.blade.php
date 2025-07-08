<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Helvetica', sans-serif;
            text-align: center;
            color: #333;
        }
        .certificate-container {
            width: 100%;
            height: 100%;
            position: relative;
            background-color: #fff;
        }
        .main-content {
            padding-top: 120px;
        }
        .main-title {
            font-size: 54px;
            font-weight: bold;
            letter-spacing: 5px;
            margin-bottom: 0;
        }
        .subtitle {
            font-size: 24px;
            letter-spacing: 2px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .cert-number {
            font-size: 14px;
            margin-bottom: 20px;
        }
        .given-to {
            font-size: 18px;
            margin-bottom: 0;
        }
        .recipient-name {
            font-family: 'Times', serif;
            font-style: italic;
            font-size: 72px;
            margin: -10px 0;
            color: #1a1a1a;
        }
        .name-underline {
            width: 50%;
            border-bottom: 1px solid #999;
            margin: 0 auto 20px auto;
        }
        .description {
            font-size: 16px;
            line-height: 1.6;
            padding: 0 10%;
        }
        .event-name {
            font-weight: bold;
        }

        /* --- PERBAIKAN: Kembali ke layout table yang lebih andal untuk PDF --- */
        .signatures-container {
            position: absolute;
            bottom: 220px;
            width: 60%; /*ATUR LEBAR TOTAL DARI BLOK TANDA TANGAN DI SINI */
            left: 0;
            right: 0;
            margin: 0 auto; /* INI YANG MENENGAHKAN */
            display: table;
            table-layout: fixed;
            border-collapse: collapse;
            border-spacing: 0;
        }
        .signature-block {
            display: table-cell;
            text-align: center;
            vertical-align: top;
            padding: 0; /* Padding di sel luar kita nolkan */
        }
        /* Kontainer baru untuk mengontrol lebar konten di dalam sel */
        .signer-content {
            /* ATUR LEBAR MAKSIMAL DARI BLOK TANDA TANGAN DI SINI */
            max-width: 220px; 
            margin: 0 auto; /* Membuat kontainer ini berada di tengah sel */
        }
        .signature-image {
            max-height: 100px; 
            margin-bottom: 5px;
        }
        .signer-name {
            font-weight: bold;
            margin: 0;
            padding: 0;
            font-size: 14px;
        }
        .signer-name u {
            text-decoration: none;
            border-bottom: 1px solid #333;
            padding-bottom: 2px;
        }
        .signer-title {
            font-size: 13px;
            margin-top: 4px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        
        <div class="main-content">
            <p class="main-title">SERTIFIKAT</p>
            <p class="subtitle">PARTISIPASI</p>
            <p class="cert-number">NO: {{ $certificateNumber }}</p>

            <p class="given-to">Diberikan kepada:</p>
            <p class="recipient-name">{{ $recipientName }}</p>
            <div class="name-underline"></div>

            <p class="description">
                Telah berpartisipasi dalam acara
                <span class="event-name">"{{ $eventName }}"</span>
                <br>
                yang diadakan pada tanggal {{ $formattedDate }}
            </p>
        </div>

        @if (!empty($signatures))
        <div class="signatures-container">
            @foreach ($signatures as $signature)
                <div class="signature-block">
                    {{-- PERBAIKAN: Bungkus konten dengan div baru untuk kontrol spasi --}}
                    <div class="signer-content">
                        <img src="{{ $signature['image_path'] }}" class="signature-image" alt="Tanda Tangan">
                        <p class="signer-name"><u>{{ $signature['name'] }}</u></p>
                        <p class="signer-title">{!! nl2br(e($signature['title'])) !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

    </div>
</body>
</html>
