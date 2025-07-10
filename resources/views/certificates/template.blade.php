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
            padding-top: 100px; /* Sedikit dinaikkan untuk memberi ruang lebih di bawah */
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
            margin: 0 auto 15px auto;
        }
        .description {
            font-size: 16px;
            line-height: 1.5;
            padding: 0 12%; /* Sedikit diperlebar */
        }
        .event-name {
            font-weight: bold;
        }
        /* Style untuk deskripsi kustom */
        .custom-description {
            font-style: italic;
            margin-top: 5px;
        }

        /* --- Bagian Bawah (Footer) --- */
        .footer-container {
            position: absolute;
            bottom: 60px; /* Jarak dari paling bawah halaman */
            width: 100%;
            left: 0;
        }
        .signing-location-date {
            font-size: 15px;
            margin-bottom: 15px; /* Jarak antara tanggal dan tanda tangan */
        }
        .signatures-container {
            width: 90%;
            margin: 0 auto; /* Menengahkan kontainer tanda tangan */
            display: table;
            table-layout: fixed;
            border-collapse: collapse;
            border-spacing: 0;
        }
        .signature-block {
            display: table-cell;
            text-align: center;
            vertical-align: top;
            padding: 0;
        }
        .signer-content {
            max-width: 220px; 
            margin: 0 auto;
        }
        .signature-image {
            max-height: 80px; /* Sedikit disesuaikan */
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
        .participant-id {
            position: absolute;
            bottom: 20px;
            left: 30px;
            font-size: 11px;
            color: #555;
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

                {{-- Menampilkan peran jika ada --}}
                @if(!empty($recipientRole))
                    <br>Sebagai <strong>{{ $recipientRole }}</strong>
                @endif
                
                {{-- Menampilkan deskripsi kustom jika diisi --}}
                @if(!empty($description1))
                    <br><span class="custom-description">{{ $description1 }}</span>
                @endif
                @if(!empty($description2))
                    <br><span class="custom-description">{{ $description2 }}</span>
                @endif
                @if(!empty($description3))
                    <br><span class="custom-description">{{ $description3 }}</span>
                @endif

                <br>
                yang diadakan pada tanggal {{ $eventDate }}
            </p>
        </div>

        {{-- Kontainer untuk semua elemen di bagian bawah --}}
        <div class="footer-container">
            {{-- Menampilkan tanggal penandatanganan jika ada --}}
            @if(!empty($signingDate))
                <p class="signing-location-date">Ditetapkan di Jakarta, pada tanggal {{ $signingDate }}</p>
            @endif

            {{-- Menampilkan tanda tangan --}}
            @if (!empty($signatures))
            <div class="signatures-container">
                @foreach ($signatures as $signature)
                    <div class="signature-block">
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

        {{-- Menampilkan ID Peserta jika ada --}}
        @if(!empty($recipientFullId) && trim($recipientFullId) !== '/')
            <div class="participant-id">
                ID Peserta: {{ $recipientFullId }}
            </div>
        @endif

    </div>
</body>
</html>
