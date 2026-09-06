const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());
const fs = require('fs');

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
          const lines = (card ? card.innerText : '').split('\n').map(l => l.trim()).filter(Boolean);
          let company = 'Various', location = 'Indonesia', salary = '';
          const skipWords = /^(Full|Part|Contract|Intern|Hybrid|Remote|Flexible|tahun|bulan|minggu|hari|menit|lalu|Premium|URGENT|Gaji|Tidak|Minimal|SMA|Sarjana|Penuh|Waktu)/i;
          for (const line of lines) {
            if (line.match(/Rp|Gaji/i)) salary = line;
            if (line.match(/(Jakarta|Surabaya|Bandung|Yogyakarta|Bali|Tangerang|Bekasi|Depok|Sleman|Semarang|Medan|Makassar|Palembang|Manado)/i)) location = line;
          }
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

(async () => {
  const browser = await puppeteer.launch({headless:'new', args:['--no-sandbox','--disable-blink-features=AutomationControlled']});
  const jobs = await scrapeGlints(browser);
  await browser.close();
  process.stderr.write(`\nTotal: ${jobs.length}\n`);
  fs.writeFileSync(__dirname + '/glints_jobs.json', JSON.stringify(jobs), 'utf8');
  console.log(JSON.stringify({count: jobs.length}));
})().catch(e => { process.stderr.write(`Fatal: ${e.message}\n`); process.exit(1); });
