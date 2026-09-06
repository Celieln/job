const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

async function scrapeGlints(browser) {
  const jobs = [];
  const keywords = ['developer', 'remote', 'designer', 'engineer', 'marketing', 'data', 'sales', 'admin', 'finance', 'hr', 'accounting', 'project', 'network', 'system', 'business', 'content', 'digital'];

  for (const kw of keywords) {
    const page = await browser.newPage();
    try {
      await page.goto(`https://glints.com/id/opportunities/jobs/explore?keyword=${kw}&country=ID`, {waitUntil:'networkidle2', timeout:30000});
      await new Promise(r => setTimeout(r, 2000));

      const pageJobs = await page.evaluate(() => {
        const results = [];
        document.querySelectorAll('a[href*="/id/opportunities/jobs/"]').forEach(a => {
          const href = a.href;
          if (!href.includes('/jobs/') || href.endsWith('/explore')) return;
          const title = a.innerText.trim();
          if (!title || title.length < 3) return;

          let card = a.parentElement;
          for (let i = 0; i < 5; i++) { if (card && card.parentElement) card = card.parentElement; }
          const cardText = card ? card.innerText : '';
          const lines = cardText.split('\n').map(l => l.trim()).filter(Boolean);

          let company = 'Various', location = 'Indonesia', salary = '';
          const skipWords = /^(Full|Part|Contract|Intern|Hybrid|Remote|Remote\/|Flexible|tahun|bulan|minggu|hari|menit|lalu|Premium|URGENT|Gaji|Tidak|Minimal|SMA|Sarjana|Penuh|Waktu)/i;
          
          for (const line of lines) {
            if (line.match(/Rp|Gaji/i)) salary = line;
            if (line.match(/(Jakarta|Surabaya|Bandung|Yogyakarta|Bali|Tangerang|Bekasi|Depok|Sleman|Semarang|Surabaya|Medan| Makassar|Palembang|Manado)/i)) location = line;
          }
          
          // Company is usually the line after title that doesn't match skip patterns
          for (const line of lines) {
            if (line === title) continue;
            if (skipWords.test(line)) continue;
            if (line.length < 3 || line.length > 60) continue;
            if (line.match(/Jakarta|Surabaya|Bandung/)) continue;
            company = line;
            break;
          }

          results.push({title, company, location, salary, url: href.split('?')[0]});
        });
        return results;
      });

      for (const job of pageJobs) {
        jobs.push({
          source: 'glints', source_url: job.url, title: job.title,
          company: job.company, location: job.location, country: 'Indonesia',
          job_type: 'fulltime', salary_min: null, salary_max: null, salary_currency: 'IDR',
          description: job.title + (job.salary ? ' - ' + job.salary : ''),
          tags: JSON.stringify(['glints', 'indonesia']), posted_at: new Date().toISOString()
        });
      }
      process.stderr.write(`  glints ${kw}: ${pageJobs.length}\n`);
    } catch (e) { process.stderr.write(`  glints ${kw} err: ${e.message}\n`); }
    await page.close();
    await new Promise(r => setTimeout(r, 1500));
  }
  return jobs;
}

async function scrapeKalibrr(browser) {
  const jobs = [];
  try {
    const page = await browser.newPage();
    await page.goto('https://www.kalibrr.com/id/job-board/k/developer', {waitUntil:'networkidle2', timeout:30000});
    await new Promise(r => setTimeout(r, 3000));
    
    const pageJobs = await page.evaluate(() => {
      const results = [];
      document.querySelectorAll('a[href*="/id/job/"]').forEach(a => {
        const title = a.innerText.trim();
        if (title && title.length > 3 && !title.match(/^(Sign|Log|Browse|Find)/i)) {
          let card = a.parentElement?.parentElement;
          const text = card ? card.innerText : '';
          const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
          let company = 'Various', location = 'Indonesia';
          for (const line of lines) {
            if (line.match(/(Jakarta|Surabaya|Bandung|Remote|Indonesia)/i)) location = line;
            if (line !== title && line.length > 2 && line.length < 50 && !line.match(/^(Full|Part|Remote|Jakarta|Bandung)/i)) company = line;
          }
          results.push({title, company, location, url: a.href.split('?')[0]});
        }
      });
      return results;
    });
    
    for (const job of pageJobs) {
      jobs.push({
        source: 'kalibrr', source_url: job.url, title: job.title,
        company: job.company, location: job.location, country: 'Indonesia',
        job_type: 'fulltime', salary_min: null, salary_max: null, salary_currency: 'IDR',
        description: job.title, tags: JSON.stringify(['kalibrr', 'indonesia']),
        posted_at: new Date().toISOString()
      });
    }
    await page.close();
  } catch (e) { process.stderr.write(`kalibrr err: ${e.message}\n`); }
  return jobs;
}

async function scrapeKarir(browser) {
  const jobs = [];
  try {
    const page = await browser.newPage();
    await page.goto('https://www.karir.com/id/lowongan-kerja?q=developer', {waitUntil:'networkidle2', timeout:30000});
    await new Promise(r => setTimeout(r, 3000));
    
    const pageJobs = await page.evaluate(() => {
      const results = [];
      document.querySelectorAll('a[href*="/id/lowongan/"]').forEach(a => {
        const title = a.innerText.trim();
        if (title && title.length > 3) {
          let card = a.parentElement?.parentElement;
          const text = card ? card.innerText : '';
          const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
          let company = 'Various', location = 'Indonesia';
          for (const line of lines) {
            if (line.match(/(Jakarta|Surabaya|Bandung|Remote)/i)) location = line;
            if (line !== title && line.length > 2 && line.length < 50) company = line;
          }
          results.push({title, company, location, url: a.href.split('?')[0]});
        }
      });
      return results;
    });
    
    for (const job of pageJobs) {
      jobs.push({
        source: 'karir', source_url: job.url, title: job.title,
        company: job.company, location: job.location, country: 'Indonesia',
        job_type: 'fulltime', salary_min: null, salary_max: null, salary_currency: 'IDR',
        description: job.title, tags: JSON.stringify(['karir', 'indonesia']),
        posted_at: new Date().toISOString()
      });
    }
    await page.close();
  } catch (e) { process.stderr.write(`karir err: ${e.message}\n`); }
  return jobs;
}

(async () => {
  const browser = await puppeteer.launch({headless:'new', args:['--no-sandbox','--disable-blink-features=AutomationControlled']});
  let allJobs = [];
  
  try {
    process.stderr.write('Scraping Glints...\n');
    allJobs = allJobs.concat(await scrapeGlints(browser));
    
    process.stderr.write('Scraping Kalibrr...\n');
    allJobs = allJobs.concat(await scrapeKalibrr(browser));
    
    process.stderr.write('Scraping Karir...\n');
    allJobs = allJobs.concat(await scrapeKarir(browser));
  } finally {
    await browser.close();
  }
  
  process.stderr.write(`\nTotal: ${allJobs.length} jobs\n`);
  console.log(JSON.stringify(allJobs));
})().catch(e => { process.stderr.write(`Fatal: ${e.message}\n`); process.exit(1); });
