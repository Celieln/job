<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Job Aggregator</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Login Form -->
  <div id="loginBox" class="login-box">
    <h2>Admin Login</h2>
    <input type="text" id="loginUser" placeholder="Username" value="admin">
    <input type="password" id="loginPass" placeholder="Password">
    <button onclick="doLogin()" class="btn-primary">Login</button>
    <p id="loginError" class="error-text"></p>
  </div>

  <!-- Admin Content (hidden until login) -->
  <div id="adminContent" style="display:none;">
    <header>
      <div class="header-content">
        <h1>Admin Panel</h1>
        <nav>
          <a href="">Dashboard</a>
          <a href="admin.php" class="active">Admin Panel</a>
          <button onclick="doLogout()" class="btn-small btn-danger">Logout</button>
        </nav>
      </div>
    </header>

    <main>
      <!-- Scan Controls -->
      <section class="admin-section">
        <h3>Manual Scan</h3>
        <div class="scan-controls">
          <select id="scanSource">
            <option value="all">All Sources (24 scrapers)</option>
            <optgroup label="International">
              <option value="remoteok">RemoteOK</option>
              <option value="remotive">Remotive</option>
              <option value="himalayas">Himalayas</option>
              <option value="weworkremotely">WeWorkRemotely</option>
              <option value="arbeitnow">Arbeitnow</option>
              <option value="remotedotorg">RemoteJobs.org</option>
              <option value="linkedin">LinkedIn</option>
              <option value="fiverr">Fiverr</option>
              <option value="indeed">Indeed</option>
            </optgroup>
            <optgroup label="Indonesia">
              <option value="gighub">Gighub.id</option>
              <option value="projects_co_id">Projects.co.id</option>
              <option value="fastwork">Fastwork.id</option>
              <option value="glints">Glints</option>
              <option value="jobstreet">JobStreet</option>
              <option value="kalibrr">Kalibrr</option>
              <option value="karir">Karir.com</option>
              <option value="lokerid">LokerID</option>
              <option value="tokopedia">Tokopedia Jobs</option>
              <option value="shopee">Shopee Jobs</option>
            </optgroup>
            <optgroup label="China">
              <option value="boss_zhipin">BOSS直聘</option>
              <option value="zhaopin">智联招聘</option>
              <option value="liepin">猎聘</option>
              <option value="51job">前程无忧</option>
            </optgroup>
          </select>
          <button onclick="runScan()" class="btn-primary" id="scanBtn">Start Scan</button>
          <button onclick="validateLinks()" class="btn-secondary" id="validateBtn">Validate Links</button>
          <button onclick="cleanDead()" class="btn-secondary" id="cleanBtn">Clean Dead Jobs</button>
          <button onclick="cleanAll()" class="btn-danger" id="cleanAllBtn">Full Clean Cycle</button>
        </div>
        <div id="scanStatus" class="status-message"></div>
      </section>

      <!-- Live Stats -->
      <section class="admin-section">
        <h3>Live Statistics</h3>
        <div class="admin-stats">
          <div class="admin-stat">
            <span class="admin-stat-number" id="adminTotal">0</span>
            <span class="admin-stat-label">Total Jobs</span>
          </div>
          <div class="admin-stat">
            <span class="admin-stat-number" id="adminAlive">0</span>
            <span class="admin-stat-label">Alive</span>
          </div>
          <div class="admin-stat">
            <span class="admin-stat-number" id="adminDead">0</span>
            <span class="admin-stat-label">Dead</span>
          </div>
          <div class="admin-stat">
            <span class="admin-stat-number" id="adminPending">0</span>
            <span class="admin-stat-label">Pending</span>
          </div>
        </div>
      </section>

      <!-- Source Status -->
      <section class="admin-section">
        <h3>Source Status</h3>
        <table id="sourceTable">
          <thead>
            <tr>
              <th>Source</th>
              <th>Type</th>
              <th>Country</th>
              <th>Jobs</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="sourceBody"></tbody>
        </table>
      </section>

      <!-- Recent Scans -->
      <section class="admin-section">
        <h3>Recent Scans</h3>
        <table id="scansTable">
          <thead>
            <tr>
              <th>Time</th>
              <th>Type</th>
              <th>Source</th>
              <th>Status</th>
              <th>Found</th>
              <th>Added</th>
            </tr>
          </thead>
          <tbody id="scansBody"></tbody>
        </table>
      </section>

      <!-- Job Management -->
      <section class="admin-section">
        <h3>Manage Jobs</h3>
        <div class="manage-controls">
          <input type="text" id="manageSearch" placeholder="Search jobs to manage...">
          <button onclick="searchJobs()" class="btn-secondary">Search</button>
        </div>
        <table id="manageTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Source</th>
              <th>Link Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="manageBody"></tbody>
        </table>
      </section>
    </main>
  </div>

  <script>
    const API = location.pathname.replace(/\/[^\/]*$/, '/') + 'api';
    let adminKey = localStorage.getItem('adminKey') || '';

    async function apiFetch(url, options = {}) {
      if (adminKey) {
        options.headers = options.headers || {};
        options.headers['Authorization'] = 'Bearer ' + adminKey;
      }
      const res = await fetch(url, options);
      if (res.status === 401) {
        showLogin();
        throw new Error('Unauthorized');
      }
      return res;
    }

    function showLogin() {
      document.getElementById('loginBox').style.display = 'flex';
      document.getElementById('adminContent').style.display = 'none';
      localStorage.removeItem('adminKey');
      adminKey = '';
    }

    function showAdmin() {
      document.getElementById('loginBox').style.display = 'none';
      document.getElementById('adminContent').style.display = 'block';
    }

    async function doLogin() {
      const user = document.getElementById('loginUser').value;
      const pass = document.getElementById('loginPass').value;
      const err = document.getElementById('loginError');
      
      try {
        const res = await fetch(API + '/admin/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username: user, password: pass })
        });
        const data = await res.json();
        
        if (data.success) {
          adminKey = pass;
          localStorage.setItem('adminKey', pass);
          showAdmin();
          loadStats();
        } else {
          err.textContent = data.message || 'Login failed';
        }
      } catch (e) {
        err.textContent = 'Connection error';
      }
    }

    async function doLogout() {
      await fetch(API + '/admin/logout', { method: 'POST' });
      showLogin();
    }

    // Enter key login
    document.getElementById('loginPass').addEventListener('keypress', (e) => {
      if (e.key === 'Enter') doLogin();
    });

    async function runScan() {
      const btn = document.getElementById('scanBtn');
      const source = document.getElementById('scanSource').value;
      const status = document.getElementById('scanStatus');
      
      btn.disabled = true;
      status.innerHTML = '<span class="loading">Scanning... This may take a while.</span>';
      
      try {
        const res = await apiFetch(API + '/admin/scan', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ source })
        });
        const data = await res.json();
        status.innerHTML = `<span class="success">Scan complete! Found: ${data.result?.totalFound || 0}, Added: ${data.result?.totalAdded || 0}</span>`;
        loadStats();
      } catch (e) {
        status.innerHTML = `<span class="error">Error: ${e.message}</span>`;
      }
      
      btn.disabled = false;
    }

    async function validateLinks() {
      const btn = document.getElementById('validateBtn');
      const status = document.getElementById('scanStatus');
      
      btn.disabled = true;
      status.innerHTML = '<span class="loading">Validating links... Please wait.</span>';
      
      try {
        const res = await apiFetch(API + '/admin/validate', { method: 'POST' });
        const data = await res.json();
        status.innerHTML = `<span class="success">Validation complete! Alive: ${data.result?.alive}, Dead: ${data.result?.dead}, Expired: ${data.result?.expired}</span>`;
        loadStats();
      } catch (e) {
        status.innerHTML = `<span class="error">Error: ${e.message}</span>`;
      }
      
      btn.disabled = false;
    }

    async function cleanDead() {
      const btn = document.getElementById('cleanBtn');
      const status = document.getElementById('scanStatus');
      
      btn.disabled = true;
      status.innerHTML = '<span class="loading">Cleaning dead jobs...</span>';
      
      try {
        const res = await apiFetch(API + '/admin/clean', { method: 'POST' });
        const data = await res.json();
        status.innerHTML = `<span class="success">Clean complete! Dead removed: ${data.result?.deadRemoved}, Old removed: ${data.result?.oldRemoved}</span>`;
        loadStats();
      } catch (e) {
        status.innerHTML = `<span class="error">Error: ${e.message}</span>`;
      }
      
      btn.disabled = false;
    }

    async function cleanAll() {
      const btn = document.getElementById('cleanAllBtn');
      const status = document.getElementById('scanStatus');
      
      btn.disabled = true;
      status.innerHTML = '<span class="loading">Running full clean cycle... Please wait.</span>';
      
      try {
        const res = await apiFetch(API + '/admin/clean-all', { method: 'POST' });
        const data = await res.json();
        status.innerHTML = `<span class="success">Full cycle complete!</span>`;
        loadStats();
      } catch (e) {
        status.innerHTML = `<span class="error">Error: ${e.message}</span>`;
      }
      
      btn.disabled = false;
    }

    async function loadStats() {
      try {
        const res = await apiFetch(API + '/stats');
        const data = await res.json();
        
        document.getElementById('adminTotal').textContent = data.total;
        document.getElementById('adminAlive').textContent = data.alive;
        document.getElementById('adminDead').textContent = data.dead;
        document.getElementById('adminPending').textContent = data.pending;
        
        const sourceRes = await apiFetch(API + '/sources');
        const sources = await sourceRes.json();
        const sourceBody = document.getElementById('sourceBody');
        sourceBody.innerHTML = sources.map(s => `
          <tr>
            <td>${s.name}</td>
            <td><span class="badge badge-${s.type}">${s.type}</span></td>
            <td>${s.country}</td>
            <td>${s.jobCount}</td>
            <td><button onclick="scanSource('${s.id}')" class="btn-small">Scan</button></td>
          </tr>
        `).join('');
        
        const logsRes = await apiFetch(API + '/admin/logs');
        const logs = await logsRes.json();
        const scansBody = document.getElementById('scansBody');
        scansBody.innerHTML = logs.map(l => `
          <tr>
            <td>${new Date(l.started_at).toLocaleString()}</td>
            <td>${l.scan_type}</td>
            <td>${l.source}</td>
            <td><span class="badge badge-${l.status === 'completed' ? 'green' : l.status === 'running' ? 'yellow' : 'red'}">${l.status}</span></td>
            <td>${l.jobs_found || 0}</td>
            <td>${l.jobs_added || 0}</td>
          </tr>
        `).join('');
      } catch (e) {
        console.error('Error loading stats:', e);
      }
    }

    async function scanSource(sourceId) {
      const status = document.getElementById('scanStatus');
      status.innerHTML = `<span class="loading">Scanning ${sourceId}...</span>`;
      
      try {
        const res = await apiFetch(API + '/admin/scan', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ source: sourceId })
        });
        const data = await res.json();
        status.innerHTML = `<span class="success">${sourceId} scan complete! Found: ${data.result?.found}, Added: ${data.result?.added}</span>`;
        loadStats();
      } catch (e) {
        status.innerHTML = `<span class="error">Error scanning ${sourceId}: ${e.message}</span>`;
      }
    }

    async function searchJobs() {
      const query = document.getElementById('manageSearch').value;
      try {
        const res = await apiFetch(API + `/jobs?q=${encodeURIComponent(query)}&limit=20`);
        const data = await res.json();
        
        const manageBody = document.getElementById('manageBody');
        manageBody.innerHTML = data.jobs.map(j => `
          <tr>
            <td>${j.id}</td>
            <td>${j.title.substring(0, 50)}</td>
            <td>${j.source}</td>
            <td><span class="badge badge-${j.link_status}">${j.link_status}</span></td>
            <td>
              <button onclick="validateJob(${j.id})" class="btn-small">Validate</button>
              <button onclick="deleteJob(${j.id})" class="btn-small btn-danger">Delete</button>
            </td>
          </tr>
        `).join('');
      } catch (e) {
        console.error('Error searching jobs:', e);
      }
    }

    async function validateJob(id) {
      try {
        await apiFetch(API + `/admin/validate/${id}`, { method: 'POST' });
        loadStats();
        searchJobs();
      } catch (e) {
        console.error('Error validating job:', e);
      }
    }

    async function deleteJob(id) {
      if (!confirm('Delete this job?')) return;
      try {
        await apiFetch(API + `/admin/jobs/${id}`, { method: 'DELETE' });
        loadStats();
        searchJobs();
      } catch (e) {
        console.error('Error deleting job:', e);
      }
    }

    // Init
    if (adminKey) {
      showAdmin();
      loadStats();
    } else {
      showLogin();
    }
  </script>
</body>
</html>
