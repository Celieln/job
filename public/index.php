<?php
// Auto-scan trigger: kalau >30 menit sejak scan terakhir, trigger background scan
require_once __DIR__ . '/auto_trigger.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Job Aggregator Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <div class="header-content">
      <h1>Remote Jobs Aggregator</h1>
      <div class="header-stats" id="headerStats">
        <span class="stat-badge green">Loading...</span>
      </div>
      <nav>
        <a href="" class="active">Dashboard</a>
        <a href="admin.php">Admin Panel</a>
      </nav>
    </div>
  </header>

  <main>
    <!-- Filters -->
    <section class="filters">
      <input type="text" id="searchInput" placeholder="Search jobs...">
      <select id="countryFilter">
        <option value="">All Countries</option>
        <option value="Global">Global</option>
        <option value="Indonesia">Indonesia</option>
        <option value="USA">USA</option>
        <option value="UK">UK</option>
        <option value="Europe">Europe</option>
      </select>
      <select id="sourceFilter">
        <option value="">All Sources</option>
        <option value="remoteok">RemoteOK</option>
        <option value="remotive">Remotive</option>
        <option value="himalayas">Himalayas</option>
        <option value="workbeam">Workbeam</option>
        <option value="jobicy">Jobicy</option>
        <option value="weworkremotely">WeWorkRemotely</option>
        <option value="hackernews">HackerNews</option>
        <option value="gighub">Gighub.id</option>
        <option value="projects_co_id">Projects.co.id</option>
        <option value="fastwork">Fastwork.id</option>
        <option value="glints">Glints</option>
      </select>
      <select id="typeFilter">
        <option value="">All Types</option>
        <option value="fulltime">Full Time</option>
        <option value="parttime">Part Time</option>
        <option value="contract">Contract</option>
        <option value="freelance">Freelance</option>
      </select>
      <label class="toggle-label">
        <input type="checkbox" id="showDead" onchange="applyFilters()"> Show Dead Jobs
      </label>
      <button onclick="applyFilters()" class="btn-primary">Apply</button>
      <button onclick="resetFilters()" class="btn-secondary">Reset</button>
    </section>

    <!-- Statistics Cards -->
    <section class="stats-cards" id="statsCards">
      <div class="stat-card">
        <div class="stat-number" id="statTotal">0</div>
        <div class="stat-label">Total Jobs</div>
      </div>
      <div class="stat-card green">
        <div class="stat-number" id="statAlive">0</div>
        <div class="stat-label">Active Links</div>
      </div>
      <div class="stat-card yellow">
        <div class="stat-number" id="statPending">0</div>
        <div class="stat-label">Pending Check</div>
      </div>
      <div class="stat-card red">
        <div class="stat-number" id="statDead">0</div>
        <div class="stat-label">Dead Links</div>
      </div>
    </section>

    <!-- Country Distribution -->
    <section class="chart-section">
      <h3>Jobs by Country</h3>
      <div id="countryChart" class="chart-bars"></div>
    </section>

    <!-- Source Distribution -->
    <section class="chart-section">
      <h3>Jobs by Source</h3>
      <div id="sourceChart" class="chart-bars"></div>
    </section>

    <!-- Jobs Table -->
    <section class="jobs-section">
      <div class="jobs-header">
        <h3>Job Listings</h3>
        <span id="jobsCount">Loading...</span>
      </div>
      <table id="jobsTable">
        <thead>
          <tr>
            <th>Title</th>
            <th>Company</th>
            <th>Location</th>
            <th>Type</th>
            <th>Source</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="jobsBody">
          <tr><td colspan="7" class="loading">Loading jobs...</td></tr>
        </tbody>
      </table>
      <div class="pagination" id="pagination"></div>
    </section>
  </main>

  <script src="script.js"></script>
</body>
</html>
