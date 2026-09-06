const puppeteer = require('puppeteer');
const fs = require('fs');

const SOURCES = {
  jobstreet: {
    urls: [
      'https://www.jobstreet.co.id/id/find-jobs?keyword=developer&sortMode=ListedDate',
      'https://www.jobstreet.co.id/id/find-jobs?keyword=remote&sortMode=ListedDate',
      'https://www.jobstreet.co.id/id/find-jobs?keyword=designer&sortMode=ListedDate',
    ],
    country: 'Indonesia'
  },
  glints: {
    urls: [
      'https://glints.com/id/opportunities/jobs/explore?country=ID&type=FULLTIME&sort=latest',
      'https://glints.com/id/opportunities/jobs/explore?country=ID&sort=latest',
    ],
    country: 'Indonesia'
  },
  kalibrr: {
    urls: [
      'https://www.kalibrr.com/id-ID/jobs',
    ],
    country: 'Indonesia'
  },
  linkedin: {
    urls: [
      'https://www.linkedin.com/jobs/search?keywords=developer&location=Indonesia&f_TPR=r604800',
      'https://www.linkedin.com/jobs/search?keywords=remote&location=China&f_TPR=r604800',
      'https://www.linkedin.com/jobs/search?keywords=developer&location=Global&f_TPR=r604800',
    ],
    country: 'Global'
  },
};

async function scrapeSource(sourceName, config) {
  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
  });

  const jobs = [];

  try {
    for (const url of config.urls) {
      const page = await browser.newPage();
      
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
      await page.setViewport({ width: 1920, height: 1080 });
      
      try {
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });
        await new Promise(r => setTimeout(r, 3000)); // Wait for JS rendering

        // Extract jobs based on source
        const pageJobs = await page.evaluate((source, country) => {
          const results = [];
          
          if (source === 'jobstreet') {
            // JobStreet job cards
            document.querySelectorAll('a[href*="/job/"]').forEach(el => {
              const title = el.querySelector('h2, h3, [data-testid="job-title"]')?.textContent?.trim();
              const company = el.closest('[class*="card"]')?.querySelector('[data-testid="company-name"], [class*="company"]')?.textContent?.trim();
              const location = el.closest('[class*="card"]')?.querySelector('[data-testid="job-location"], [class*="location"]')?.textContent?.trim();
              if (title && title.length > 3) {
                results.push({
                  title: title.substring(0, 200),
                  company: company || 'Perusahaan Indonesia',
                  location: location || 'Indonesia',
                  url: el.href
                });
              }
            });
          }
          
          if (source === 'glints') {
            // Glints job cards
            document.querySelectorAll('a[href*="/opportunities/"]').forEach(el => {
              const title = el.querySelector('h3, h2, [class*="title"]')?.textContent?.trim();
              const company = el.querySelector('[class*="company"]')?.textContent?.trim();
              const location = el.querySelector('[class*="location"]')?.textContent?.trim();
              if (title && title.length > 3) {
                results.push({
                  title: title.substring(0, 200),
                  company: company || 'Various Companies',
                  location: location || 'Indonesia',
                  url: el.href
                });
              }
            });
          }
          
          if (source === 'kalibrr') {
            // Kalibrr job cards
            document.querySelectorAll('a[href*="/rec/"]').forEach(el => {
              const title = el.querySelector('h3, h2, [class*="title"]')?.textContent?.trim();
              const company = el.querySelector('[class*="company"]')?.textContent?.trim();
              if (title && title.length > 3) {
                results.push({
                  title: title.substring(0, 200),
                  company: company || 'Tech Company',
                  location: 'Indonesia',
                  url: el.href
                });
              }
            });
          }
          
          if (source === 'linkedin') {
            // LinkedIn job cards
            document.querySelectorAll('a[href*="/jobs/view/"]').forEach(el => {
              const title = el.querySelector('h3, h2, [class*="title"]')?.textContent?.trim() || el.textContent?.trim();
              if (title && title.length > 3) {
                results.push({
                  title: title.substring(0, 200),
                  company: 'Various Companies',
                  location: 'Remote',
                  url: el.href
                });
              }
            });
          }
          
          return results;
        }, sourceName, config.country);

        for (const job of pageJobs) {
          jobs.push({
            source: sourceName,
            source_url: job.url,
            title: job.title,
            company: job.company,
            location: job.location,
            country: config.country,
            job_type: 'fulltime',
            salary_min: null,
            salary_max: null,
            salary_currency: config.country === 'Indonesia' ? 'IDR' : '',
            description: job.title,
            tags: JSON.stringify([sourceName, config.country.toLowerCase()]),
            posted_at: new Date().toISOString()
          });
        }
      } catch (e) {
        // Skip failed page
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
  const sourceName = process.argv[2] || 'all';
  const allJobs = [];
  
  if (sourceName === 'all') {
    for (const [name, config] of Object.entries(SOURCES)) {
      try {
        const jobs = await scrapeSource(name, config);
        allJobs.push(...jobs);
        process.stderr.write(`${name}: ${jobs.length} jobs\n`);
      } catch (e) {
        process.stderr.write(`${name}: ERROR ${e.message}\n`);
      }
    }
  } else if (SOURCES[sourceName]) {
    const jobs = await scrapeSource(sourceName, SOURCES[sourceName]);
    allJobs.push(...jobs);
    process.stderr.write(`${sourceName}: ${jobs.length} jobs\n`);
  } else {
    process.stderr.write(`Unknown source: ${sourceName}\n`);
  }
  
  // Output JSON to stdout
  console.log(JSON.stringify(allJobs));
}

main().catch(e => {
  process.stderr.write(`Fatal: ${e.message}\n`);
  process.exit(1);
});
