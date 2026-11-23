#!/usr/bin/env bash
# READ-ONLY server inspection. Changes nothing, prints no secrets. Run as root: bash server-audit.sh
echo "== OS / resources"; lsb_release -ds; nproc; free -h | sed -n 2p; df -h / | tail -1
echo "== Listening ports"; ss -tlnp 2>/dev/null | awk 'NR>1{print $4, $6}' | sort -u
echo "== Web server"; for s in nginx apache2 caddy; do systemctl is-active $s 2>/dev/null | sed "s/^/$s: /"; done
echo "== Enabled nginx sites"; ls /etc/nginx/sites-enabled 2>/dev/null; grep -rhE "server_name|root " /etc/nginx/sites-enabled 2>/dev/null
echo "== PHP"; php -v 2>/dev/null | head -1; ls /etc/php 2>/dev/null; systemctl list-units --type=service 2>/dev/null | grep -i php-fpm
echo "== PHP extensions needed"; for e in pgsql pdo_pgsql gd intl mbstring zip bcmath xml curl; do php -m 2>/dev/null | grep -qix $e && echo "ok $e" || echo "MISSING $e"; done
echo "== PostgreSQL"; psql --version 2>/dev/null; systemctl is-active postgresql 2>/dev/null; sudo -u postgres psql -Atc "select datname from pg_database where not datistemplate" 2>/dev/null
echo "== Other DBs"; systemctl is-active mysql mariadb redis-server 2>/dev/null
echo "== Node / Composer / Git"; node -v 2>/dev/null; npm -v 2>/dev/null; composer --version 2>/dev/null | head -1; git --version
echo "== Firewall"; ufw status 2>/dev/null | head -8
echo "== Existing app dirs"; ls -d /var/www/* /home/deploy/* /srv/* 2>/dev/null
echo "== Existing runners / supervisor / cron"; ls -d /home/*/actions-runner* /opt/actions-runner* 2>/dev/null; systemctl list-units --type=service 2>/dev/null | grep -i actions.runner; supervisorctl status 2>/dev/null; crontab -u deploy -l 2>/dev/null | sed 's/=.*/=…/' | head
echo "== TLS certs"; ls /etc/letsencrypt/live 2>/dev/null
echo "== Pending reboot"; [ -f /var/run/reboot-required ] && echo "reboot required"
echo "== Sudo for deploy"; sudo -l -U deploy 2>/dev/null | tail -5
