const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());

async function scrapeGlints() {
  const browser = await puppeteer.launch({headless:'new', args:['--no-sandbox','--disable-blink-features=AutomationControlled']});
  const jobs = [];
  const keywords = ['developer', 'remote', 'designer', 'engineer', 'marketing'];

  try {
    for (const kw of keywords) {
      const page = await browser.newPage();
      try {
        await page.goto(`https://glints.com/id/opportunities/jobs/explore?keyword=${kw}&country=ID`, {waitUntil:'networkidle2', timeout:30000});
        await new Promise(r => setTimeout(r, 3000));

        const pageJobs = await page.evaluate(() => {
          const results = [];
          document.querySelectorAll('a[href*="/id/opportunities/"]').forEach(a => {
            const href = a.href;
            if (!href.includes('/jobs/')) return;
            const text = a.innerText;
            const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
            if (lines.length < 2) return;
            
            let title = lines[0];
            let company = '';
            let location = '';
            let salary = '';
            
            for (const line of lines) {
              if (line.match(/^(PT|CV|PT\.|UD|Persero|Tbk|Inc|Ltd)/i) || line.match(/(Indonesia|Jakarta|Surabaya|Bandung|Yogyakarta|Bali|Tangerang|Bekasi|Depok|Tangerang|Sleman|Semarang)/i)) {
                if (!company && !line.match(/(Jakarta|Surabaya|Bandung|Yogyakarta|Bali)/i)) company = line;
                else if (!location) location = line;
              }
              if (line.match(/Rp|Gaji/i)) salary = line;
            }
            
            if (!location) location = 'Indonesia';
            
            results.push({title, company: company || 'Various', location, salary, url: href});
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
        process.stderr.write(`  ${kw}: ${pageJobs.length} jobs\n`);
      } catch (e) { process.stderr.write(`  ${kw} error: ${e.message}\n`); }
      await page.close();
      await new Promise(r => setTimeout(r, 1500));
    }
  } finally { await browser.close(); }
  return jobs;
}

(async () => {
  const jobs = await scrapeGlints();
  process.stderr.write(`glints total: ${jobs.length}\n`);
  console.log(JSON.stringify(jobs));
})().catch(e => { process.stderr.write(`Fatal: ${e.message}\n`); process.exit(1); });
