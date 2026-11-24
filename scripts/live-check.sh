#!/usr/bin/env bash
# Quick health check of the live site from ANY computer (read-only, sends no data).
# Usage:  bash scripts/live-check.sh https://pcbd.shafiqul.dev
BASE="${1:-https://pcbd.shafiqul.dev}"
pass=0; fail=0
ok()  { echo "  OK    $1"; pass=$((pass+1)); }
bad() { echo "  FAIL  $1"; fail=$((fail+1)); }
code() { curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$BASE$1"; }

echo "Pages"
for p in / /properties /projects /developers /locations /about /contact /faq /blog /sitemap.xml /robots.txt /up; do
  c=$(code "$p"); [ "$c" = "200" ] && ok "$p ($c)" || bad "$p ($c)"
done
c=$(code /definitely-not-here); [ "$c" = "404" ] && ok "unknown page gives a proper 404" || bad "unknown page returned $c"
c=$(code /admin/login); [ "$c" = "200" ] && ok "admin login reachable ($c)" || bad "admin login returned $c"

echo "Security headers"
H=$(curl -sI --max-time 20 "$BASE/")
for h in x-content-type-options x-frame-options referrer-policy permissions-policy strict-transport-security; do
  echo "$H" | grep -qi "^$h:" && ok "$h" || bad "$h missing"
done
echo "$H" | grep -qi "^x-powered-by" && bad "X-Powered-By is exposed" || ok "no X-Powered-By"

echo "Search engines"
if echo "$H" | grep -qi "^x-robots-tag:.*noindex"; then echo "  NOTE  site is hidden from Google (PCBD_NOINDEX=true) — fine before launch, turn off to go public"
else ok "site is indexable"; fi
curl -s --max-time 20 "$BASE/robots.txt" | grep -qi "Disallow: /admin" && ok "robots.txt blocks /admin" || echo "  NOTE  robots.txt does not list /admin (expected while NOINDEX is on)"

echo "Language"
curl -s --max-time 20 -c /tmp/pcbd-cookie.$$ -o /dev/null "$BASE/language/bn" -L
curl -s --max-time 20 -b /tmp/pcbd-cookie.$$ "$BASE/" | grep -q 'lang="bn"' && ok "Bangla switch works" || bad "Bangla switch did not change the page"
rm -f /tmp/pcbd-cookie.$$

echo "TLS"
curl -sI --max-time 20 "http://${BASE#https://}/" 2>/dev/null | head -1 | grep -qE "301|302|308" && ok "http redirects to https" || echo "  NOTE  http check skipped or not redirected"

echo; echo "Result: $pass passed, $fail failed"; [ "$fail" -eq 0 ]
