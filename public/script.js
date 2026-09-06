let currentPage = 0;
const PAGE_SIZE = 20;
const BASE = location.pathname.replace(/\/[^\/]*$/, '/');

async function loadStats() {
  try {
    const res = await fetch(BASE + 'api/stats');
    const data = await res.json();
    
    document.getElementById('statTotal').textContent = data.total;
    document.getElementById('statAlive').textContent = data.alive;
    document.getElementById('statPending').textContent = data.pending;
    document.getElementById('statDead').textContent = data.archived || data.dead;
    
    // Header badge
    document.getElementById('headerStats').innerHTML = `
      <span class="stat-badge green">${data.total} jobs</span>
    `;
    
    // Country chart
    const countryChart = document.getElementById('countryChart');
    const maxCountry = Math.max(...data.byCountry.map(c => c.count));
    countryChart.innerHTML = data.byCountry.map(c => `
      <div class="chart-bar">
        <span class="chart-bar-label">${c.country}</span>
        <div class="chart-bar-track">
          <div class="chart-bar-fill" style="width: ${(c.count / maxCountry) * 100}%"></div>
        </div>
        <span class="chart-bar-count">${c.count}</span>
      </div>
    `).join('');
    
    // Source chart
    const sourceChart = document.getElementById('sourceChart');
    const maxSource = Math.max(...data.bySource.map(s => s.count));
    sourceChart.innerHTML = data.bySource.map(s => `
      <div class="chart-bar">
        <span class="chart-bar-label">${s.source}</span>
        <div class="chart-bar-track">
          <div class="chart-bar-fill" style="width: ${(s.count / maxSource) * 100}%"></div>
        </div>
        <span class="chart-bar-count">${s.count}</span>
      </div>
    `).join('');
    
  } catch (e) {
    console.error('Error loading stats:', e);
  }
}

// Load jobs
async function loadJobs(page = 0) {
  try {
    const search = document.getElementById('searchInput').value;
    const country = document.getElementById('countryFilter').value;
    const source = document.getElementById('sourceFilter').value;
    const type = document.getElementById('typeFilter').value;
    
    let url = `${BASE}api/jobs?limit=${PAGE_SIZE}&offset=${page * PAGE_SIZE}`;
    if (search) url += `&q=${encodeURIComponent(search)}`;
    if (country) url += `&country=${country}`;
    if (source) url += `&source=${source}`;
    if (type) url += `&type=${type}`;
    if (document.getElementById('showDead').checked) url += `&show_all=1`;
    
    const res = await fetch(url);
    const data = await res.json();
    
    const tbody = document.getElementById('jobsBody');
    const jobsCount = document.getElementById('jobsCount');
    
    jobsCount.textContent = `Showing ${data.jobs.length} of ${data.total} jobs`;
    
    if (data.jobs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="loading">No jobs found</td></tr>';
      return;
    }
    
    tbody.innerHTML = data.jobs.map(job => `
      <tr>
        <td>
          <a href="${escapeHtml(job.source_url)}" target="_blank" style="color: #60a5fa; text-decoration: none;">
            ${escapeHtml(job.title.substring(0, 60))}
          </a>
        </td>
        <td>${escapeHtml(job.company)}</td>
        <td>${escapeHtml(job.location || 'Remote')}</td>
        <td><span class="badge badge-${job.job_type}">${job.job_type}</span></td>
        <td>${escapeHtml(job.source)}</td>
        <td><span class="badge badge-${job.link_status}">${job.link_status}</span></td>
        <td>
          <a href="${escapeHtml(job.source_url)}" target="_blank" class="btn-small btn-secondary">Visit</a>
        </td>
      </tr>
    `).join('');
    
    // Pagination
    currentPage = page;
    const totalPages = Math.ceil(data.total / PAGE_SIZE);
    const pagination = document.getElementById('pagination');
    
    if (totalPages > 1) {
      let paginationHTML = '';
      if (page > 0) {
        paginationHTML += `<button onclick="loadJobs(${page - 1})" class="btn-small btn-secondary">← Prev</button>`;
      }
      paginationHTML += `<span>Page ${page + 1} of ${totalPages}</span>`;
      if (page < totalPages - 1) {
        paginationHTML += `<button onclick="loadJobs(${page + 1})" class="btn-small btn-secondary">Next →</button>`;
      }
      pagination.innerHTML = paginationHTML;
    } else {
      pagination.innerHTML = '';
    }
    
  } catch (e) {
    console.error('Error loading jobs:', e);
    document.getElementById('jobsBody').innerHTML = '<tr><td colspan="7" class="loading">Error loading jobs</td></tr>';
  }
}

function applyFilters() {
  loadJobs(0);
}

function resetFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('countryFilter').value = '';
  document.getElementById('sourceFilter').value = '';
  document.getElementById('typeFilter').value = '';
  loadJobs(0);
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Search on Enter key
document.getElementById('searchInput').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') applyFilters();
});

// Auto-validate: cek pending jobs setiap 5 menit
async function autoValidate() {
  try {
    const res = await fetch(BASE + 'api/admin/auto-validate', {method: 'POST'});
    const data = await res.json();
    if (data.success && data.validated) {
      console.log(`Auto-validate: ${data.validated.alive} alive, ${data.validated.dead} dead`);
      loadStats();
      loadJobs(currentPage);
    }
  } catch(e) {}
}

// Auto-clean: soft delete dead jobs setiap 10 menit (sudah include di auto-validate)

// Auto-refresh setiap 60 detik
loadStats();
loadJobs();
setInterval(() => {
  loadStats();
  loadJobs(currentPage);
}, 60000);

// Auto-validate setiap 5 menit (include clean)
setInterval(autoValidate, 300000);
