const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const fs = require('fs');
puppeteer.use(StealthPlugin());

async function scrapeGlints(browser) {
  const jobs = [];
  const kw = ['developer','remote','designer','engineer','marketing','data','sales','admin','finance','hr','accounting','project','network','system','business','content','digital'];
  for (const k of kw) {
    const page = await browser.newPage();
    try {
      await page.goto(`https://glints.com/id/opportunities/jobs/explore?keyword=${k}&country=ID`, {waitUntil:'networkidle2', timeout:30000});
      await new Promise(r => setTimeout(r, 2000));
      const pj = await page.evaluate(() => {
        const r = [];
        document.querySelectorAll('a[href*="/id/opportunities/jobs/"]').forEach(a => {
          if (!a.href.includes('/jobs/') || a.href.endsWith('/explore')) return;
          const t = a.innerText.trim();
          if (!t || t.length < 3) return;
          let c = a.parentElement; for (let i=0;i<5;i++) if(c?.parentElement) c=c.parentElement;
          const ls = (c?.innerText||'').split('\n').map(l=>l.trim()).filter(Boolean);
          let co='Various',lo='Indonesia',sa='';
          const sk = /^(Full|Part|Contract|Intern|Hybrid|Remote|Flexible|tahun|bulan|Premium|URGENT|Gaji|Tidak|Minimal|SMA|Sarjana|Penuh|Waktu)/i;
          for (const l of ls) {
            if (l.match(/Rp|Gaji/i)) sa=l;
            if (l.match(/Jakarta|Surabaya|Bandung|Yogyakarta|Bali|Tangerang|Bekasi|Depok|Sleman|Semarang|Medan|Makassar|Palembang|Manado/i)) lo=l;
          }
          for (const l of ls) {
            if (l===t||sk.test(l)||l.length<3||l.length>60||l.match(/Jakarta|Surabaya|Bandung/)) continue;
            co=l; break;
          }
          r.push({t,co,lo,sa,u:a.href.split('?')[0]});
        });
        return r;
      });
      for (const j of pj) {
        jobs.push({source:'glints',source_url:j.u,title:j.t,company:j.co,location:j.lo,country:'Indonesia',job_type:'fulltime',salary_min:null,salary_max:null,salary_currency:'IDR',description:j.t+(j.sa?' - '+j.sa:''),tags:JSON.stringify(['glints','indonesia']),posted_at:new Date().toISOString()});
      }
      process.stderr.write(`  glints ${k}: ${pj.length}\n`);
    } catch(e) {}
    await page.close();
    await new Promise(r => setTimeout(r, 1200));
  }
  return jobs;
}

(async () => {
  const browser = await puppeteer.launch({headless:'new', args:['--no-sandbox','--disable-blink-features=AutomationControlled']});
  const jobs = await scrapeGlints(browser);
  await browser.close();
  const out = __dirname + '/glints_jobs.json';
  fs.writeFileSync(out, JSON.stringify(jobs), 'utf8');
  process.stderr.write(`\nGlints total: ${jobs.length}\n`);
  console.log(JSON.stringify({count:jobs.length}));
})().catch(e => { process.stderr.write(`Fatal: ${e.message}\n`); process.exit(1); });
