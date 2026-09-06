const puppeteer = require('puppeteer');

async function scrapeLinkedIn() {
  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
  });

  const jobs = [];
  const urls = [
    'https://www.linkedin.com/jobs/search?keywords=developer&location=Indonesia&f_TPR=r604800',
    'https://www.linkedin.com/jobs/search?keywords=remote&location=Indonesia&f_TPR=r604800',
    'https://www.linkedin.com/jobs/search?keywords=designer&location=Indonesia&f_TPR=r604800',
  ];

  try {
    for (const url of urls) {
      const page = await browser.newPage();
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
      
      try {
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });
        await new Promise(r => setTimeout(r, 3000));

        const pageJobs = await page.evaluate(() => {
          const results = [];
          document.querySelectorAll('.base-card, .job-search-card, [data-entity-urn]').forEach(el => {
            const title = el.querySelector('.base-search-card__title, h3')?.textContent?.trim();
            const company = el.querySelector('.base-search-card__subtitle, h4')?.textContent?.trim();
            const location = el.querySelector('.job-search-card__location')?.textContent?.trim();
            const link = el.querySelector('a')?.href;
            
            if (title && title.length > 3) {
              results.push({
                title,
                company: company || 'Various',
                location: location || 'Indonesia',
                url: link || ''
              });
            }
          });
          return results;
        });

        for (const job of pageJobs) {
          jobs.push({
            source: 'linkedin',
            source_url: job.url || 'https://linkedin.com/jobs',
            title: job.title,
            company: job.company,
            location: job.location,
            country: 'Indonesia',
            job_type: 'fulltime',
            salary_min: null,
            salary_max: null,
            salary_currency: 'IDR',
            description: job.title,
            tags: JSON.stringify(['linkedin', 'indonesia']),
            posted_at: new Date().toISOString()
          });
        }
        
        process.stderr.write(`  ${url.split('?')[1]}: ${pageJobs.length} jobs\n`);
      } catch (e) {
        process.stderr.write(`  Error: ${e.message}\n`);
      }
      
      await page.close();
      await new Promise(r => setTimeout(r, 2000));
    }
  } finally {
    await browser.close();
  }

  return jobs;
}

async function main() {
  const jobs = await scrapeLinkedIn();
  process.stderr.write(`linkedin: ${jobs.length} jobs total\n`);
  console.log(JSON.stringify(jobs));
}

main().catch(e => {
  process.stderr.write(`Fatal: ${e.message}\n`);
  process.exit(1);
});
