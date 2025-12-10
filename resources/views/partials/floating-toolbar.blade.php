<!-- Floating Toolbar Component -->
<div id="floating-toolbar">
    <div class="floating-pill">
        <div class="pill-row pill-formatting">
            <div id="floating-formatting-group" class="floating-formatting">
                <!-- Font Family -->
                <div class="pill-item pill-select dropdown-host" id="floating-font-family">
                    <span class="value">Arial</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <span class="pill-divider"></span>
                
                <!-- Font Size -->
                <div class="pill-item pill-select dropdown-host" id="floating-font-size">
                    <span class="value">24</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <span class="pill-divider"></span>
                
                <!-- Line Height -->
                <div class="pill-item dropdown-host" id="floating-line-height">
                    <i class="fas fa-arrows-alt-v"></i>
                    <span class="value">1.20</span>
                </div>
                <span class="pill-divider"></span>
                
                <!-- Font Color -->
                <div class="pill-item pill-color dropdown-host" id="floating-font-color">
                    <button type="button" class="pill-button color-trigger" id="floating-color-trigger" title="Lihat format warna">
                        <span class="color-dot"></span>
                        <i class="fas fa-palette"></i>
                    </button>
                    <span class="value sr-only">#000000</span>
                    <div class="color-dropdown" id="floating-color-dropdown" role="menu">
                        <div class="color-picker-section">
                            <label class="color-picker-label" for="floating-color-input">Pilih Warna</label>
                            <input type="color" id="floating-color-input" class="color-native-input" value="#000000">
                        </div>
                        <div class="color-swatches-section">
                            <span class="swatch-title">Warna Umum</span>
                            <div class="swatch-grid" id="floating-color-swatches"></div>
                        </div>
                        <button type="button" class="color-advanced-toggle" id="floating-color-advanced-toggle">Format Lanjutan</button>
                        <div class="color-advanced" id="floating-color-advanced">
                            <div class="color-dropdown-tabs" role="tablist">
                                <button type="button" class="color-tab active" data-format="hex">HEX</button>
                                <button type="button" class="color-tab" data-format="rgb">RGB</button>
                                <button type="button" class="color-tab" data-format="hsl">HSL</button>
                            </div>
                            <div class="color-dropdown-body">
                                <label class="color-format-label" id="floating-color-label">HEX</label>
                                <input type="text" class="color-display" id="floating-color-display" value="#000000">
                                <small class="color-helper" id="floating-color-helper">Gunakan format sesuai tab aktif lalu tekan Enter.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <span class="pill-divider"></span>
                
                <!-- Text Style Buttons -->
                <div class="pill-button-group" id="floating-style-buttons">
                    <button type="button" class="pill-button" id="floating-bold" title="Bold">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button type="button" class="pill-button" id="floating-italic" title="Italic">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button type="button" class="pill-button" id="floating-underline" title="Underline">
                        <i class="fas fa-underline"></i>
                    </button>
                </div>
                <span class="pill-divider"></span>
                
                <!-- Text Alignment (Horizontal) -->
                <div class="pill-button-group align-group" id="floating-align-horizontal">
                    <button type="button" class="pill-button floating-align-button" id="floating-align-left" data-align="left" title="Rata kiri">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <button type="button" class="pill-button floating-align-button" id="floating-align-center" data-align="center" title="Rata tengah">
                        <i class="fas fa-align-center"></i>
                    </button>
                    <button type="button" class="pill-button floating-align-button" id="floating-align-right" data-align="right" title="Rata kanan">
                        <i class="fas fa-align-right"></i>
                    </button>
                </div>
                
                <!-- Object Alignment (Vertical) - Hidden by default, shown for multi-select -->
                <span class="pill-divider align-vertical-divider is-hidden"></span>
                <div class="pill-button-group align-group align-vertical is-hidden" id="floating-align-vertical">
                    <button type="button" class="pill-button floating-align-button" id="floating-align-top" data-align="top" title="Sejajarkan ke atas">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="pill-button floating-align-button" id="floating-align-middle" data-align="middle" title="Sejajarkan ke tengah vertikal">
                        <i class="fas fa-arrows-alt-v"></i>
                    </button>
                    <button type="button" class="pill-button floating-align-button" id="floating-align-bottom" data-align="bottom" title="Sejajarkan ke bawah">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
            </div>
            
            <!-- Info Label (shows selection count) -->
            <span class="pill-info" id="floating-toolbar-info">—</span>
        </div>
        
        <!-- Divider for Group Actions Row -->
        <div class="pill-row-divider" id="floating-row-divider"></div>
        
        <!-- Group/Ungroup Actions Row - Hidden by default, shown for multi-select -->
        <div class="pill-row pill-groups" id="floating-group-row">
            <div id="floating-group-actions" class="floating-group-actions">
                <button id="float-group" class="pill-button" title="Group Selection (Ctrl+G)">
                    <i class="fas fa-object-group"></i>
                    <span>Group</span>
                </button>
                <button id="float-ungroup" class="pill-button" title="Ungroup (Ctrl+Shift+G)">
                    <i class="fas fa-object-ungroup"></i>
                    <span>Ungroup</span>
                </button>
                <label id="align-mode-toggle" class="pill-button mb-0">
                    <input type="checkbox" id="align-within-group-checkbox">
                    <span class="toggle-label">Align Within</span>
                </label>
            </div>
        </div>
    </div>
</div>
