const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const fs = require('fs');
puppeteer.use(StealthPlugin());

async function scrapeDDG(browser, query, source) {
  const page = await browser.newPage();
  const jobs = [];
  try {
    await page.goto(`https://html.duckduckgo.com/html/?q=${encodeURIComponent(query)}`, {
      waitUntil: 'domcontentloaded', timeout: 30000
    });
    await new Promise(r => setTimeout(r, 2000));
    
    const results = await page.evaluate(() => {
      const items = [];
      document.querySelectorAll('.result, .results_links, .web-result').forEach(el => {
        const link = el.querySelector('a.result__a, a.result__url, .result__title a');
        const snippetEl = el.querySelector('.result__snippet, .result__body');
        if (link) {
          const href = link.getAttribute('href') || link.href;
          items.push({
            title: link.innerText.trim(),
            url: href,
            snippet: snippetEl?.innerText?.trim()?.substring(0, 200) || ''
          });
        }
      });
      return items;
    });
    
    for (const r of results) {
      let url = r.url;
      // DDG redirects through //duckduckgo.com/l/?uddg= encoded URL
      const uddg = url.match(/uddg=([^&]+)/);
      if (uddg) url = decodeURIComponent(uddg[1]);
      
      jobs.push({
        source, source_url: url,
        title: r.title.substring(0, 100),
        company: r.snippet.split('\n')[0]?.substring(0, 50) || 'Various',
        location: 'Indonesia', country: 'Indonesia',
        job_type: 'fulltime', salary_min: null, salary_max: null, salary_currency: 'IDR',
        description: r.snippet, tags: JSON.stringify([source, 'indonesia']),
        posted_at: new Date().toISOString()
      });
    }
    process.stderr.write(`  ${source}: ${jobs.length} results from "${query.substring(0,40)}..."\n`);
  } catch(e) {
    process.stderr.write(`  ${source} err: ${e.message.substring(0,60)}\n`);
  }
  await page.close();
  await new Promise(r => setTimeout(r, 1500));
  return jobs;
}

(async () => {
  const browser = await puppeteer.launch({headless:'new', args:['--no-sandbox','--disable-blink-features=AutomationControlled']});
  let allJobs = [];

  const queries = [
    ['jobstreet lowongan kerja developer 2026', 'jobstreet'],
    ['jobstreet lowongan kerja designer 2026', 'jobstreet'],
    ['jobstreet lowongan kerja engineer 2026', 'jobstreet'],
    ['jobstreet lowongan kerja marketing 2026', 'jobstreet'],
    ['jobstreet lowongan kerja data analyst 2026', 'jobstreet'],
    ['jobstreet lowongan kerja admin 2026', 'jobstreet'],
    ['jobstreet lowongan kerja sales 2026', 'jobstreet'],
    ['jobstreet lowongan kerja finance 2026', 'jobstreet'],
    ['jobstreet lowongan kerja remote 2026', 'jobstreet'],
    ['kalibrr lowongan kerja developer indonesia', 'kalibrr'],
    ['kalibrr lowongan kerja engineer indonesia', 'kalibrr'],
    ['kalibrr lowongan kerja designer indonesia', 'kalibrr'],
    ['karir.com lowongan kerja developer', 'karir'],
    ['karir.com lowongan kerja engineer', 'karir'],
    ['karir.com lowongan kerja designer', 'karir'],
  ];

  for (const [q, src] of queries) {
    const r = await scrapeDDG(browser, q, src);
    allJobs = allJobs.concat(r);
  }

  await browser.close();
  fs.writeFileSync(__dirname + '/google_jobs.json', JSON.stringify(allJobs), 'utf8');
  process.stderr.write(`\nTotal: ${allJobs.length}\n`);
  console.log(JSON.stringify({count: allJobs.length}));
})().catch(e => { process.stderr.write(`Fatal: ${e.message}\n`); process.exit(1); });
