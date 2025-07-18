<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate Renderer</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <style>
        body, html {
            margin: 0;
            padding: 0;
        }
        @media print {
            #print-button {
                display: none;
            }
        }
        #print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <button id="print-button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    <canvas id="canvas"></canvas>

    {{-- Injected data from Controller --}}
    <script id="renderer-data" type="application/json">
        {
            "template": @json($templateJson),
            "participant": @json($participantData)
        }
    </script>

    @verbatim
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        try {
            const dataElement = document.getElementById('renderer-data');
            const data = JSON.parse(dataElement.textContent);
            const templateData = data.template;
            const participantData = data.participant;

            if (!templateData || !templateData.objects) return;

            const canvas = window.canvas = new fabric.Canvas('canvas', {
                width: 1122,
                height: 793,
            });

            // Fungsi untuk memuat objek dari JSON setelah background di-render
            function loadObjects() {
                canvas.loadFromJSON(templateData, function () {
                    function replacePlaceholders(currentText, key, value) {
                        let newText = currentText.replace(new RegExp('{{' + key + '}}', 'g'), value || '');
                        newText = newText.replace(new RegExp('@{{' + key + '}}', 'g'), value || '');
                        return newText;
                    }

                    canvas.forEachObject(function (obj) {
                        // KASUS 1: Grup tanda tangan (Logika yang Diperbaiki)
                        if (obj.isSignatureBlock) {
                            const index = obj.signatureIndex;
                            const sigData = participantData.signatures?.[index];

                            // Lewati jika tidak ada data untuk tanda tangan ini
                            if (!sigData) return;

                            // --- Langkah 1: Ganti placeholder teks terlebih dahulu ---
                            // Iterasi melalui setiap objek di dalam grup (gambar/rect, nama, jabatan)
                            obj.forEachObject(function(item) {
                                if (item.text) { // Proses hanya objek yang merupakan teks
                                    const sigNum = index + 1;
                                    const namePlaceholder = '{{nama_penandatangan_' + sigNum + '}}';
                                    const titlePlaceholder = '{{jabatan_penandatangan_' + sigNum + '}}';
                                    let currentText = item.text;

                                    // Cek dan ganti placeholder nama
                                    if (currentText.includes(namePlaceholder)) {
                                        item.set('text', currentText.replace(namePlaceholder, sigData.name || ''));
                                    } 
                                    // Cek dan ganti placeholder jabatan
                                    else if (currentText.includes(titlePlaceholder)) {
                                        item.set('text', currentText.replace(titlePlaceholder, sigData.title || ''));
                                    }
                                }
                            });

                            // --- Langkah 2: Tangani penggantian gambar ---
                            const placeholderRect = obj._objects.find(o => o.type === 'rect');

                            // Cek jika ada gambar base64 DAN ada placeholder berbentuk kotak (rect)
                            if (sigData.image_base64 && placeholderRect) {
                                fabric.Image.fromURL(sigData.image_base64, function(img) {
                                    
                                    // Atur skala dan posisi gambar agar pas dengan placeholder
                                    img.set({
                                        originX: placeholderRect.originX,
                                        originY: placeholderRect.originY,
                                        left: placeholderRect.left,
                                        top: placeholderRect.top,
                                        scaleX: placeholderRect.width / img.width,
                                        scaleY: placeholderRect.height / img.height,
                                    });

                                    // Hapus kotak placeholder dan tambahkan gambar asli ke grup
                                    obj.remove(placeholderRect);
                                    obj.add(img);

                                    // --- Langkah 3: Sesuaikan kembali posisi teks berdasarkan tinggi gambar BARU ---
                                    const imageHeight = img.getScaledHeight();
                                    const nameTextObj = obj._objects.find(o => o.text && o.fontWeight === 'bold');
                                    const titleTextObj = obj._objects.find(o => o.text && o.fontWeight !== 'bold');

                                    if (nameTextObj) {
                                        // Posisikan nama tepat di bawah gambar
                                        nameTextObj.set('top', img.top + imageHeight + 5);
                                    }
                                    if (titleTextObj) {
                                        // Posisikan jabatan di bawah nama
                                        titleTextObj.set('top', img.top + imageHeight + 30);
                                    }
                                    
                                    // Perbarui koordinat grup dan render ulang kanvas
                                    obj.setCoords();
                                    canvas.renderAll();
                                });
                            } else {
                                // Jika tidak ada gambar, cukup render ulang perubahan teks
                                canvas.renderAll();
                            }
                        }

                        // KASUS 2: Teks biasa
                        else if (obj.text) {
                            let text = obj.text;
                            text = replacePlaceholders(text, 'nama_penerima', participantData.recipientName);
                            text = replacePlaceholders(text, 'nomor_sertifikat', participantData.certificateNumber);
                            text = replacePlaceholders(text, 'jenis_sertifikat', participantData.certificateType);
                            text = replacePlaceholders(text, 'nama_acara', participantData.eventName);
                            text = replacePlaceholders(text, 'tanggal_acara', participantData.eventDate);
                            text = replacePlaceholders(text, 'tanggal_penandatanganan', participantData.signingDate);
                            text = replacePlaceholders(text, 'deskripsi_1', participantData.description1);
                            text = replacePlaceholders(text, 'deskripsi_2', participantData.description2);
                            text = replacePlaceholders(text, 'deskripsi_3', participantData.description3);
                            text = replacePlaceholders(text, 'id_lengkap_peserta', participantData.recipientFullId);
                            text = replacePlaceholders(text, 'peran_penerima', participantData.recipientRole);
                            obj.set('text', text);
                        }
                    });


                    canvas.renderAll();
                });
            }

            // Cek dan render background jika tersedia
            if (templateData.background_image_base64) {
                fabric.Image.fromURL(templateData.background_image_base64, function (bgImage) {
                    bgImage.set({
                        left: 0,
                        top: 0,
                        originX: 'left',
                        originY: 'top',
                        selectable: false,
                        evented: false,
                        scaleX: canvas.width / bgImage.width,
                        scaleY: canvas.height / bgImage.height
                    });

                    canvas.add(bgImage);
                    canvas.sendToBack(bgImage);
                    loadObjects();
                });
            } else {
                loadObjects(); // Langsung load objek jika tidak ada background
            }
        } catch (e) {
            console.error("JavaScript Error:", e);
        }
    });
    </script>
    @endverbatim
</body>
</html>
