<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate Renderer</title>
    {{-- Kita akan memuat Fabric.js langsung di sini --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <style>
        /* Menghilangkan margin/padding agar PDF pas */
        body, html {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    {{-- Kanvas tempat desain akan dirender --}}
    <canvas id="canvas"></canvas>

    <script>
        // Mengambil data yang dikirim dari controller
        const templateData = @json($templateJson);
        const participantData = @json($participantData);

        // Inisialisasi kanvas
        const canvas = new fabric.Canvas('canvas', {
            width: 1122, // Ukuran A4 Landscape dalam px @ 96 DPI
            height: 793,
        });

        // Muat desain dari JSON ke kanvas
        canvas.loadFromJSON(templateData, function() {
            // Setelah desain dimuat, ganti semua placeholder
            canvas.forEachObject(function(obj) {
                if (obj.isPlaceholder && obj.text) {
                    let text = obj.text;
                    // Ganti placeholder dengan data yang sesuai
                    text = text.replace('{{nama_penerima}}', participantData.recipientName || '');
                    text = text.replace('{{nomor_sertifikat}}', participantData.certificateNumber || '');
                    text = text.replace('{{jenis_sertifikat}}', participantData.certificateType || '');
                    text = text.replace('{{nama_acara}}', participantData.eventName || '');
                    text = text.replace('{{tanggal_acara}}', participantData.eventDate || '');
                    text = text.replace('{{tanggal_penandatanganan}}', participantData.signingDate || '');
                    text = text.replace('{{deskripsi_1}}', participantData.description1 || '');
                    text = text.replace('{{deskripsi_2}}', participantData.description2 || '');
                    text = text.replace('{{deskripsi_3}}', participantData.description3 || '');
                    text = text.replace('{{id_lengkap_peserta}}', participantData.recipientFullId || '');
                    text = text.replace('{{peran_penerima}}', participantData.recipientRole || '');
                    
                    obj.set('text', text);
                }
            });

            // Render ulang kanvas dengan data yang sudah diganti
            canvas.renderAll();
        });
    </script>
</body>
</html>
