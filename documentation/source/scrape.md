# Scraping an Existing Site

> This is an experimental feature; use at your own risk.

1. Once you have the sitemap complete, that represents an existing website.
2. Compile using `--format=json` to get the compile file.
3. Pass that compiled file to _scrape_ to scrape the website based on the sitemap.
4. Follow the instructions in the output.

```bash
./vendor/bin/vismap website.json --format=json
./vendor/bin/scrape website.compile.json
```
