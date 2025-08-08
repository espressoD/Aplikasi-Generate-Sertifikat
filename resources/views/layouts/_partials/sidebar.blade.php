<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard') }}" class="brand-link">
      <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Sertifikat App</span>
    </a>

    <div class="sidebar">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('certificates.bulk.form') }}" class="nav-link {{ request()->is('certificates.bulk.form*') ? 'active' : '' }}"class="nav-link">
                <i class="nav-icon fas fa-file-upload"></i>
                <p>Generate Bulk</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('certificates.list') }}" class="nav-link {{ request()->is('certificates*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-list"></i>
              <p>Daftar Sertifikat</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('batches.list') }}" class="nav-link {{ request()->is('batches*') ? 'active' : '' }}">
              <i class="nav-icon fas fa-boxes"></i>
              <p>Daftar Batch</p>
            </a>
          </li>
        </ul>
      </nav>
      </div>
    </aside>