const puppeteer = require('puppeteer');

async function scrapeJobStreet() {
  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
  });

  const jobs = [];
  const urls = [
    'https://www.jobstreet.co.id/id/find-jobs?keyword=developer&sortMode=ListedDate',
    'https://www.jobstreet.co.id/id/find-jobs?keyword=remote&sortMode=ListedDate',
    'https://www.jobstreet.co.id/id/find-jobs?keyword=designer&sortMode=ListedDate',
  ];

  try {
    for (const url of urls) {
      const page = await browser.newPage();
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');
      
      try {
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });
        await new Promise(r => setTimeout(r, 3000));

        // Extract JSON data from __NEXT_DATA__ or Apollo cache
        const pageJobs = await page.evaluate(() => {
          const results = [];
          
          // Try to find job data in script tags (Next.js/GraphQL)
          const scripts = document.querySelectorAll('script');
          for (const script of scripts) {
            const text = script.textContent;
            if (text.includes('JobSearchV7Job') && text.includes('title')) {
              try {
                // Extract JSON from the script
                const jsonMatch = text.match(/\{.*"JobSearchV7Job".*\}/s);
                if (jsonMatch) {
                  const data = JSON.parse(jsonMatch[0]);
                  // Find all job objects
                  for (const [key, value] of Object.entries(data)) {
                    if (value && value.__typename === 'JobSearchV7Job') {
                      results.push({
                        id: value.id,
                        title: value.title,
                        company: value.advertiser?.name || 'Unknown',
                        location: '', // Will be filled from location ref
                        salary: value.cjs?.salary?.displayValue || '',
                        salaryMin: value.cjs?.salary?.minimum ? value.cjs.salary.minimum / 10 : null,
                        salaryMax: value.cjs?.salary?.maximum ? value.cjs.salary.maximum / 10 : null,
                        url: `https://www.jobstreet.co.id/id/job/${value.id}`
                      });
                    }
                    // Extract location names
                    if (value && value.__typename === 'JobSearchV7JobLocation' && value.displayName?.text) {
                      const loc = value.displayName.text;
                      // Associate with jobs by location ID
                      for (const job of results) {
                        if (!job.location) job.location = loc;
                      }
                    }
                  }
                }
              } catch (e) {
                // Skip parse errors
              }
            }
          }
          
          // Fallback: try standard HTML extraction
          if (results.length === 0) {
            document.querySelectorAll('[data-testid="job-card"], [class*="job-card"], a[href*="/job/"]').forEach(el => {
              const title = el.querySelector('h2, h3, [data-testid="job-title"]')?.textContent?.trim();
              const company = el.querySelector('[data-testid="company-name"], [class*="company"]')?.textContent?.trim();
              const link = el.closest('a')?.href || el.querySelector('a')?.href;
              if (title && title.length > 3 && link) {
                results.push({
                  title, company: company || 'Various',
                  location: 'Indonesia', url: link
                });
              }
            });
          }
          
          return results;
        });

        for (const job of pageJobs) {
          jobs.push({
            source: 'jobstreet',
            source_url: job.url || `https://www.jobstreet.co.id/id/job/${job.id}`,
            title: job.title,
            company: job.company,
            location: job.location || 'Indonesia',
            country: 'Indonesia',
            job_type: 'fulltime',
            salary_min: job.salaryMin || null,
            salary_max: job.salaryMax || null,
            salary_currency: 'IDR',
            description: job.title + (job.salary ? ' - ' + job.salary : ''),
            tags: JSON.stringify(['jobstreet', 'indonesia']),
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
  const source = process.argv[2] || 'jobstreet';
  let jobs = [];
  
  if (source === 'jobstreet') {
    jobs = await scrapeJobStreet();
  }
  
  process.stderr.write(`${source}: ${jobs.length} jobs total\n`);
  console.log(JSON.stringify(jobs));
}

main().catch(e => {
  process.stderr.write(`Fatal: ${e.message}\n`);
  process.exit(1);
});
