const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const fs = require('fs');
puppeteer.use(StealthPlugin());

async function scrapeSite(browser, name, url, extractFn) {
  const page = await browser.newPage();
  try {
    await page.goto(url, {waitUntil:'networkidle2', timeout:30000});
    await new Promise(r => setTimeout(r, 3000));
    const jobs = await page.evaluate(extractFn);
    await page.close();
    return jobs;
  } catch(e) {
    process.stderr.write(`${name}: ${e.message}\n`);
    await page.close();
    return [];
  }
}

async function main() {
  const browser = await puppeteer.launch({headless:'new', args:['--no-sandbox','--disable-blink-features=AutomationControlled']});
  let allJobs = [];

  // === GUNTER ===
  process.stderr.write('Scraping JobStreet via Google...\n');
  const jsJobs = await scrapeSite(browser, 'jobstreet',
    'https://www.google.com/search?q=site:id.jobstreet.com+lowongan+kerja+developer&num=50',
    () => {
      const results = [];
      document.querySelectorAll('a[href*="id.jobstreet.com/id/job/"]').forEach(a => {
        const href = a.href;
        const match = href.match(/id\.jobstreet\.com\/id\/job\/(\d+)/);
        if (!match) return;
        const title = a.closest('div')?.innerText?.split('\n')[0] || '';
        if (title.length > 5) {
          results.push({
            title: title.substring(0, 100),
            url: `https://www.jobstreet.co.id/id/job/${match[1]}`
          });
        }
      });
      return results;
    }
  );
  for (const j of jsJobs) {
    allJobs.push({
      source: 'jobstreet', source_url: j.url, title: j.title,
      company: 'Various', location: 'Indonesia', country: 'Indonesia',
      job_type: 'fulltime', salary_min: null, salary_max: null, salary_currency: 'IDR',
      description: j.title, tags: JSON.stringify(['jobstreet','indonesia']),
      posted_at: new Date().toISOString()
    });
  }
  process.stderr.write(`  jobstreet: ${jsJobs.length}\n`);

  // === KALIBRR ===
  process.stderr.write('Scraping Kalibrr via Google...\n');
  const kbJobs = await scrapeSite(browser, 'kalibrr',
    'https://www.google.com/search?q=site:kalibrr.com+lowongan+kerja+developer+indonesia&num=50',
    () => {
      const results = [];
      document.querySelectorAll('a[href*="kalibrr.com/id/job-board/"]').forEach(a => {
        const href = a.href;
        const match = href.match(/kalibrr\.com\/id\/job-board\/[^\/]+\/(\d+)/);
        if (!match) return;
        const title = a.closest('div')?.innerText?.split('\n')[0] || '';
        if (title.length > 5) {
          results.push({
            title: title.substring(0, 100),
            url: `https://www.kalibrr.com/id/job-board/k/${match[1]}`
          });
        }
      });
      return results;
    }
  );
  for (const j of kbJobs) {
    allJobs.push({
      source: 'kalibrr', source_url: j.url, title: j.title,
      company: 'Various', location: 'Indonesia', country: 'Indonesia',
      job_type: 'fulltime', salary_min: null, salary_max: null, salary_currency: 'IDR',
      description: j.title, tags: JSON.stringify(['kalibrr','indonesia']),
      posted_at: new Date().toISOString()
    });
  }
  process.stderr.write(`  kalibrr: ${kbJobs.length}\n`);

  // === KARIR.COM ===
  process.stderr.write('Scraping Karir via Google...\n');
  const krJobs = await scrapeSite(browser, 'karir',
    'https://www.google.com/search?q=site:karir.com+lowongan+kerja+developer&num=50',
    () => {
      const results = [];
      document.querySelectorAll('a[href*="karir.com/id/lowongan/"]').forEach(a => {
        const href = a.href;
        const title = a.closest('div')?.innerText?.split('\n')[0] || '';
        if (title.length > 5) {
          results.push({
            title: title.substring(0, 100),
            url: href
          });
        }
      });
      return results;
    }
  );
  for (const j of krJobs) {
    allJobs.push({
      source: 'karir', source_url: j.url, title: j.title,
      company: 'Various', location: 'Indonesia', country: 'Indonesia',
      job_type: 'fulltime', salary_min: null, salary_max: null, salary_currency: 'IDR',
      description: j.title, tags: JSON.stringify(['karir','indonesia']),
      posted_at: new Date().toISOString()
    });
  }
  process.stderr.write(`  karir: ${krJobs.length}\n`);

  await browser.close();
  fs.writeFileSync(__dirname + '/indo_scraped.json', JSON.stringify(allJobs), 'utf8');
  process.stderr.write(`\nTotal: ${allJobs.length}\n`);
  console.log(JSON.stringify({count: allJobs.length}));
}

main().catch(e => { process.stderr.write(`Fatal: ${e.message}\n`); process.exit(1); });
