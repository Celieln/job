const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const fs = require('fs');
puppeteer.use(StealthPlugin());

async function scrapeGoogle(browser, query, siteFilter, source, limit = 50) {
  const page = await browser.newPage();
  const jobs = [];
  try {
    await page.goto(`https://www.google.com/search?q=${encodeURIComponent(query)}&num=${limit}`, {
      waitUntil: 'networkidle2', timeout: 30000
    });
    await new Promise(r => setTimeout(r, 3000));
    
    const results = await page.evaluate((src) => {
      const items = [];
      document.querySelectorAll('#search .g, #rso .g').forEach(el => {
        const link = el.querySelector('a[href]');
        const titleEl = el.querySelector('h3');
        const snippetEl = el.querySelector('.VwiC3b, [data-sncf], .lEBKkf');
        if (link && titleEl) {
          items.push({
            title: titleEl.innerText.trim(),
            url: link.href,
            snippet: snippetEl?.innerText?.trim()?.substring(0, 200) || ''
          });
        }
      });
      return items;
    }, source);
    
    for (const r of results) {
      if (siteFilter && !r.url.includes(siteFilter)) continue;
      jobs.push({
        source, source_url: r.url,
        title: r.title.substring(0, 100),
        company: r.snippet.split('\n')[0]?.substring(0, 50) || 'Various',
        location: 'Indonesia', country: 'Indonesia',
        job_type: 'fulltime', salary_min: null, salary_max: null, salary_currency: 'IDR',
        description: r.snippet, tags: JSON.stringify([source, 'indonesia']),
        posted_at: new Date().toISOString()
      });
    }
    process.stderr.write(`  ${source} "${query}": ${jobs.length} results\n`);
  } catch(e) {
    process.stderr.write(`  ${source} err: ${e.message.substring(0,60)}\n`);
  }
  await page.close();
  await new Promise(r => setTimeout(r, 2000));
  return jobs;
}

async function scrapeJobStreetGoogle(browser) {
  let jobs = [];
  const queries = [
    'site:id.jobstreet.com lowongan kerja developer',
    'site:id.jobstreet.com lowongan kerja designer',
    'site:id.jobstreet.com lowongan kerja engineer',
    'site:id.jobstreet.com lowongan kerja marketing',
    'site:id.jobstreet.com lowongan kerja admin',
    'site:id.jobstreet.com lowongan kerja data',
    'site:id.jobstreet.com lowongan kerja sales',
    'site:id.jobstreet.com lowongan kerja finance',
    'site:id.jobstreet.com lowongan kerja remote',
    'site:glints.com lowongan kerja developer indonesia',
  ];
  for (const q of queries) {
    const r = await scrapeGoogle(browser, q, 'id.jobstreet.com', 'jobstreet');
    jobs = jobs.concat(r);
    if (jobs.length > 300) break;
  }
  return jobs;
}

async function scrapeKalibrrGoogle(browser) {
  let jobs = [];
  const queries = [
    'site:kalibrr.com lowongan kerja developer indonesia',
    'site:kalibrr.com lowongan kerja engineer indonesia',
    'site:kalibrr.com lowongan kerja designer indonesia',
  ];
  for (const q of queries) {
    const r = await scrapeGoogle(browser, q, 'kalibrr.com', 'kalibrr');
    jobs = jobs.concat(r);
  }
  return jobs;
}

async function scrapeKarirGoogle(browser) {
  let jobs = [];
  const queries = [
    'site:karir.com lowongan kerja developer',
    'site:karir.com lowongan kerja engineer',
    'site:karir.com lowongan kerja designer',
  ];
  for (const q of queries) {
    const r = await scrapeGoogle(browser, q, 'karir.com', 'karir');
    jobs = jobs.concat(r);
  }
  return jobs;
}

(async () => {
  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-blink-features=AutomationControlled']
  });
  
  let allJobs = [];
  
  process.stderr.write('=== JobStreet via Google ===\n');
  allJobs = allJobs.concat(await scrapeJobStreetGoogle(browser));
  
  process.stderr.write('=== Kalibrr via Google ===\n');
  allJobs = allJobs.concat(await scrapeKalibrrGoogle(browser));
  
  process.stderr.write('=== Karir via Google ===\n');
  allJobs = allJobs.concat(await scrapeKarirGoogle(browser));
  
  await browser.close();
  
  fs.writeFileSync(__dirname + '/google_jobs.json', JSON.stringify(allJobs), 'utf8');
  process.stderr.write(`\nTotal: ${allJobs.length}\n`);
  console.log(JSON.stringify({ count: allJobs.length }));
})().catch(e => {
  process.stderr.write(`Fatal: ${e.message}\n`);
  process.exit(1);
});
