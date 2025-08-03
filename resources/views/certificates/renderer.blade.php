<!DOCTYPE html>
<html>
<head>
    <title>Certificate Renderer</title>
    <style>
        body, html { margin: 0; padding: 0; }
        #certificate-canvas { width: 1123px; height: 794px; }
    </style>
</head>
<body>
    <div id="loader" style="font-family: sans-serif; font-size: 24px; text-align: center; padding-top: 40vh;">
        Memuat sertifikat...
    </div>
    <canvas id="certificate-canvas" style="display: none;"></canvas>

    <script id="renderer-data" type="application/json">
        {
            "template": @json($templateJson),
            "participant": @json($participantData)
        }
    </script>
    <script>
        {!! file_get_contents(public_path('js/fabric.min.js')) !!}
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.__done__ = false;
            const rendererData = JSON.parse(document.getElementById('renderer-data').textContent);
            const templateData = rendererData.template;
            const participantData = rendererData.participant;

            // Keep signatures in their original array format for easier processing
            // No need to flatten signature data anymore since we handle it dynamically

            // Debug logging
            console.log('Participant Data:', participantData);
            console.log('Template Data:', templateData);

            // Inisialisasi Kanvas
            const canvas = new fabric.Canvas('certificate-canvas', {
                width: 1123,
                height: 794,
            });

            // Muat template dari JSON
            canvas.loadFromJSON(templateData, function() {
                // Manipulasi objek SETELAH kanvas dimuat
                canvas.getObjects().forEach(function(obj) {
                    // KASUS 1: Grup tanda tangan (Logic from old version - exact copy)
                    if (obj.isSignatureBlock) {
                        const index = obj.signatureIndex;
                        const sigData = participantData.signatures?.[index];

                        // Skip if no signature data for this block
                        if (!sigData) return;

                        // --- Step 1: Replace text placeholders first ---
                        obj.forEachObject(function(item) {
                            if (item.text) { // Process only text objects
                                const sigNum = index + 1;
                                const namePlaceholder1 = '{' + '{nama_penandatangan_' + sigNum + '}' + '}';
                                const titlePlaceholder1 = '{' + '{jabatan_penandatangan_' + sigNum + '}' + '}';
                                const namePlaceholder2 = '@{' + '{nama_penandatangan_' + sigNum + '}' + '}';
                                const titlePlaceholder2 = '@{' + '{jabatan_penandatangan_' + sigNum + '}' + '}';
                                let currentText = item.text;

                                console.log('Processing signature text:', currentText, 'for index:', index);
                                console.log('Looking for placeholders:', namePlaceholder1, titlePlaceholder1);
                                console.log('Signature data:', sigData);

                                // Check and replace name placeholder (both formats)
                                if (currentText.includes(namePlaceholder1) || currentText.includes(namePlaceholder2)) {
                                    currentText = currentText.replace(namePlaceholder1, sigData.name || '');
                                    currentText = currentText.replace(namePlaceholder2, sigData.name || '');
                                    item.set('text', currentText);
                                    console.log('Replaced name placeholder with:', sigData.name);
                                } 
                                // Check and replace title placeholder (both formats)
                                else if (currentText.includes(titlePlaceholder1) || currentText.includes(titlePlaceholder2)) {
                                    currentText = currentText.replace(titlePlaceholder1, sigData.title || '');
                                    currentText = currentText.replace(titlePlaceholder2, sigData.title || '');
                                    item.set('text', currentText);
                                    console.log('Replaced title placeholder with:', sigData.title);
                                }
                            }
                        });

                        // --- Step 2: Handle image replacement ---
                        const placeholderRect = obj._objects.find(o => o.type === 'rect');

                        // Check if there's image data AND a placeholder rectangle
                        if (sigData.image_base64 && placeholderRect) {
                            fabric.Image.fromURL(sigData.image_base64, function(img) {
                                
                                // Set image properties to match the placeholder rectangle
                                img.set({
                                    originX: placeholderRect.originX,
                                    originY: placeholderRect.originY,
                                    left: placeholderRect.left,
                                    top: placeholderRect.top,
                                    scaleX: placeholderRect.width / img.width,
                                    scaleY: placeholderRect.height / img.height,
                                });

                                // Remove the placeholder rectangle and add the actual image
                                obj.remove(placeholderRect);
                                obj.add(img);

                                // --- Step 3: Adjust text positions based on NEW image height ---
                                const imageHeight = img.getScaledHeight();
                                const nameTextObj = obj._objects.find(o => o.text && o.fontWeight === 'bold');
                                const titleTextObj = obj._objects.find(o => o.text && o.fontWeight !== 'bold');

                                if (nameTextObj) {
                                    // Position name text right below the image
                                    nameTextObj.set('top', img.top + imageHeight + 5);
                                }
                                if (titleTextObj) {
                                    // Position title text below the name
                                    titleTextObj.set('top', img.top + imageHeight + 30);
                                }
                                
                                // Update group coordinates and re-render
                                obj.setCoords();
                                canvas.renderAll();
                            });
                        } else {
                            // If no image, just render text changes
                            canvas.renderAll();
                        }
                    }

                    // KASUS 2: Regular text objects
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
                        // Add nilai placeholders
                        text = replacePlaceholders(text, 'nilai_1', participantData.nilai1);
                        text = replacePlaceholders(text, 'nilai_2', participantData.nilai2);
                        text = replacePlaceholders(text, 'nilai_3', participantData.nilai3);
                        text = replacePlaceholders(text, 'nilai_4', participantData.nilai4);
                        obj.set('text', text);
                    }
                });

                // Tampilkan kanvas dan sembunyikan loader
                document.getElementById('loader').style.display = 'none';
                canvas.getElement().style.display = 'block';
                canvas.renderAll();

                // Beri sinyal selesai ke Browsershot
                window.__done__ = true;
            });
        });

        function toSnakeCase(str) {
            return str.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`).replace(/^_/, '');
        }

        // Helper function for replacing placeholders (from old version)
        function replacePlaceholders(currentText, key, value) {
            let newText = currentText.replace(new RegExp('{' + '{' + key + '}' + '}', 'g'), value || '');
            newText = newText.replace(new RegExp('@{' + '{' + key + '}' + '}', 'g'), value || '');
            return newText;
        }
    </script>
</body>
</html>