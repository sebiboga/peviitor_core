// render_page.js
const puppeteer = require('puppeteer');

async function main() {
  const url = process.argv[2];
  if (!url) {
    console.error('Usage: node render_page.js <url>');
    process.exit(1);
  }

  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const page = await browser.newPage();
  await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });

  // opțional: așteaptă extra timp dacă știi că pagina încarcă lent
  // await page.waitForTimeout(3000);

  const content = await page.content(); // HTML static după execuția JS
  console.log(content);

  await browser.close();
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
