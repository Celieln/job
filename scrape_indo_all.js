const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const fs = require('fs');
puppeteer.use(StealthPlugin());

async function scrapeGlints(browser) {
  const jobs = [];
  const keywords = ['developer','remote','designer','engineer','marketing','data','sales','admin','finance','hr','accounting','project','network','system','business','content','digital'];
  for (const kw of keywords) {
    const page = await browser.newPage();
    try {
      await page.goto(`https://glints.com/id/opportunities/jobs/explore?keyword=${kw}&country=ID`, {waitUntil:'networkidle2', timeout:30000});
      await new Promise(r => setTimeout(r, 2000));
      const pageJobs = await page.evaluate(() => {
        const results = [];
        document.querySelectorAll('a[href*="/id/opportunities/jobs/"]').forEach(a => {
          if (!a.href.includes('/jobs/') || a.href.endsWith('/explore')) return;
          const title = a.innerText.trim();
          if (!title || title.length < 3) return;
          let card = a.parentElement;
          for (let i = 0; i < 5; i++) { if (card?.parentElement) card = card.parentElement; }
          const lines = (card?.innerText || '').split('\n').map(l => l.trim()).filter(Boolean);
          let company = 'Various', location = 'Indonesia', salary = '';
          const skip = /^(Full|Part|Contract|Intern|Hybrid|Remote|Flexible|tahun|bulan|Premium|URGENT|Gaji|Tidak|Minimal|SMA|Sarjana|Penuh|Waktu)/i;
          for (const l of lines) {
            if (l.match(/Rp|Gaji/i)) salary = l;
            if (l.match(/Jakarta|Surabaya|Bandung|Yogyakarta|Bali|Tangerang|Bekasi|Depok|Sleman|Semarang|Medan|Makassar|Palembang|Manado/i)) location = l;
          }
          for (const l of lines) {
            if (l === title || skip.test(l) || l.length < 3 || l.length > 60 || l.match(/Jakarta|Surabaya|Bandung/)) continue;
            company = l; break;
          }
          results.push({title, company, location, salary, url: a.href.split('?')[0]});
        });
        return results;
      });
      for (const j of pageJobs) {
        jobs.push({
          source:'glints', source_url:j.url, title:j.title, company:j.company, location:j.location,
          country:'Indonesia', job_type:'fulltime', salary_min:null, salary_max:null, salary_currency:'IDR',
          description:j.title+(j.salary?' - '+j.salary:''), tags:JSON.stringify(['glints','indonesia']),
          posted_at:new Date().toISOString()
        });
      }
      process.stderr.write(`  glints ${kw}: ${pageJobs.length}\n`);
    } catch(e) { process.stderr.write(`  glints ${kw} err\n`); }
    await page.close();
    await new Promise(r => setTimeout(r, 1500));
  }
  return jobs;
}

async function scrapeJobStreet(browser) {
  const jobs = [];
  const keywords = ['developer','designer','engineer','marketing','admin','finance','data','sales','hr','project'];
  for (const kw of keywords) {
    const page = await browser.newPage();
    try {
      await page.goto(`https://id.jobstreet.com/id/job-search?keyword=${kw}&sortMode=ListedDate`, {waitUntil:'networkidle2', timeout:30000});
      await new Promise(r => setTimeout(r, 5000));
      
      const title = await page.title();
      if (title.includes('moment') || title.includes('Cloudflare')) {
        process.stderr.write(`  jobstreet ${kw}: Cloudflare blocked\n`);
        await page.close();
        continue;
      }
      
      const pageJobs = await page.evaluate(() => {
        const results = [];
        document.querySelectorAll('a[href*="/id/job/"]').forEach(a => {
          if (!a.href.includes('/job/')) return;
          const title = a.innerText.trim();
          if (!title || title.length < 3 || title.length > 150) return;
          let card = a.parentElement;
          for (let i = 0; i < 5; i++) { if (card?.parentElement) card = card.parentElement; }
          const text = card?.innerText || '';
          const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
          let company = 'Various', location = 'Indonesia', salary = '';
          for (const l of lines) {
            if (l.match(/Rp|Gaji|Salary|IDR/i)) salary = l;
            if (l.match(/Jakarta|Surabaya|Bandung|Yogyakarta|Bali|Tangerang|Bekasi|Depok|Remote/i)) location = l;
          }
          for (const l of lines) {
            if (l === title || l.length < 3 || l.length > 60) continue;
            if (l.match(/Full|Part|Contract|Remote|Tahun|Bulan|Premium|URGENT|Gaji/i)) continue;
            company = l; break;
          }
          results.push({title, company, location, salary, url: a.href.split('?')[0]});
        });
        return results;
      });
      
      for (const j of pageJobs) {
        jobs.push({
          source:'jobstreet', source_url:j.url, title:j.title, company:j.company, location:j.location,
          country:'Indonesia', job_type:'fulltime', salary_min:null, salary_max:null, salary_currency:'IDR',
          description:j.title+(j.salary?' - '+j.salary:''), tags:JSON.stringify(['jobstreet','indonesia']),
          posted_at:new Date().toISOString()
        });
      }
      process.stderr.write(`  jobstreet ${kw}: ${pageJobs.length}\n`);
    } catch(e) { process.stderr.write(`  jobstreet ${kw} err: ${e.message.substring(0,50)}\n`); }
    await page.close();
    await new Promise(r => setTimeout(r, 2000));
  }
  return jobs;
}

async function scrapeKalibrr(browser) {
  const jobs = [];
  const keywords = ['developer','designer','engineer','data','marketing','admin'];
  for (const kw of keywords) {
    const page = await browser.newPage();
    try {
      await page.goto(`https://www.kalibrr.com/id/job-board/k/${kw}?location=Indonesia`, {waitUntil:'networkidle2', timeout:30000});
      await new Promise(r => setTimeout(r, 3000));
      const pageJobs = await page.evaluate(() => {
        const results = [];
        document.querySelectorAll('a[href*="/id/job-board/"]').forEach(a => {
          const title = a.innerText.trim();
          if (title && title.length > 3 && !title.match(/^(Sign|Log|Browse|Find|Kalibrr)/i)) {
            let card = a.parentElement?.parentElement;
            const text = card?.innerText || '';
            const lines = text.split('\n').map(l => l.trim()).filter(Boolean);
            let company = 'Various', location = 'Indonesia';
            for (const l of lines) {
              if (l.match(/Jakarta|Surabaya|Bandung|Remote|Indonesia/i)) location = l;
              if (l !== title && l.length > 2 && l.length < 50 && !l.match(/Full|Part|Remote|Jakarta/i)) company = l;
            }
            results.push({title, company, location, url: a.href.split('?')[0]});
          }
        });
        return results;
      });
      for (const j of pageJobs) {
        jobs.push({
          source:'kalibrr', source_url:j.url, title:j.title, company:j.company, location:j.location,
          country:'Indonesia', job_type:'fulltime', salary_min:null, salary_max:null, salary_currency:'IDR',
          description:j.title, tags:JSON.stringify(['kalibrr','indonesia']), posted_at:new Date().toISOString()
        });
      }
      process.stderr.write(`  kalibrr ${kw}: ${pageJobs.length}\n`);
    } catch(e) { process.stderr.write(`  kalibrr ${kw} err\n`); }
    await page.close();
    await new Promise(r => setTimeout(r, 1500));
  }
  return jobs;
}

(async () => {
  const browser = await puppeteer.launch({headless:'new', args:['--no-sandbox','--disable-blink-features=AutomationControlled']});
  let allJobs = [];
  
  process.stderr.write('=== Glints ===\n');
  allJobs = allJobs.concat(await scrapeGlints(browser));
  
  process.stderr.write('=== JobStreet ===\n');
  allJobs = allJobs.concat(await scrapeJobStreet(browser));
  
  process.stderr.write('=== Kalibrr ===\n');
  allJobs = allJobs.concat(await scrapeKalibrr(browser));
  
  await browser.close();
  
  fs.writeFileSync(__dirname + '/indo_all.json', JSON.stringify(allJobs), 'utf8');
  process.stderr.write(`\nTotal: ${allJobs.length}\n`);
  console.log(JSON.stringify({count: allJobs.length}));
})().catch(e => { process.stderr.write(`Fatal: ${e.message}\n`); process.exit(1); });
